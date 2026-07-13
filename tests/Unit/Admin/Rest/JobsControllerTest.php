<?php
/**
 * Tests for the jobs REST controller.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Admin\Rest;

use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkQueueService;
use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkScanService;
use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\WordPressTransientBulkScanSessionStore;
use HyperWeb\LighthouseImageOptimizer\Admin\MediaLibrary\AttachmentStatusReader;
use HyperWeb\LighthouseImageOptimizer\Admin\Rest\JobsController;
use HyperWeb\LighthouseImageOptimizer\Admin\Rest\RestErrorFactory;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentFingerprintBuilder;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifestSanitizer;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeRepository;
use HyperWeb\LighthouseImageOptimizer\Image\SourceCollector;
use HyperWeb\LighthouseImageOptimizer\Queue\AttachmentQueueService;
use HyperWeb\LighthouseImageOptimizer\Queue\QueueControlService;
use HyperWeb\LighthouseImageOptimizer\Queue\QueueControlStateStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Admin\Bulk\FakeBulkScannerRuntime;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment\FakeAttachmentMetaStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment\FixedAttachmentClock;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeAttachmentSourceProvider;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeImageFileProbe;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeSettingsRepository;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure\FakeOptionStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure\FakeTransientStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Queue\FakeAttachmentJobControl;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Queue\FakeQueue;
use PHPUnit\Framework\TestCase;

/**
 * Verifies route registration and dry-run scan behavior.
 */
final class JobsControllerTest extends TestCase {

	/**
	 * Test route registration adds the expected bulk control routes.
	 *
	 * @return void
	 */
	public function test_register_routes_registers_scan_and_control_routes(): void {
		$runtime    = new FakeRestRuntime();
		$controller = $this->fixture( $runtime )['controller'];

		$controller->register_routes();

		self::assertCount( 6, $runtime->routes );
		self::assertSame(
			array(
				'/jobs/scan',
				'/jobs/queue',
				'/jobs/retry',
				'/jobs/pause',
				'/jobs/resume',
				'/jobs/pending',
			),
			array_map(
				static function ( array $route ): string {
					return $route['route'];
				},
				$runtime->routes
			)
		);
	}

	/**
	 * Test manage_options is required before bulk routes can run.
	 *
	 * @return void
	 */
	public function test_permission_callback_requires_manage_options(): void {
		$runtime                         = new FakeRestRuntime();
		$runtime->capabilities['manage_options'] = false;
		$controller                      = $this->fixture( $runtime )['controller'];

		$result = $controller->can_manage_options();

		self::assertSame( 'error', $result['type'] );
		self::assertSame( 'rest_forbidden', $result['code'] );
	}

	/**
	 * Test invalid filters are rejected cleanly.
	 *
	 * @return void
	 */
	public function test_scan_jobs_rejects_invalid_filters(): void {
		$runtime    = new FakeRestRuntime();
		$controller = $this->fixture( $runtime )['controller'];

		$response = $controller->scan_jobs(
			new FakeRestRequest(
				array(
					'scan_scope' => 'not-real',
				)
			)
		);

		self::assertSame( 'error', $response['type'] );
		self::assertSame( 'invalid_scan_scope', $response['code'] );
	}

	/**
	 * Test a new dry-run scan creates a token and returns progress.
	 *
	 * @return void
	 */
	public function test_scan_jobs_starts_new_scan_and_returns_token(): void {
		$runtime                   = new FakeRestRuntime();
		$fixture                   = $this->fixture( $runtime );
		$fixture['bulk']->pages[0] = array( 10, 11 );

		$response = $fixture['controller']->scan_jobs( new FakeRestRequest() );

		self::assertSame( 'response', $response['type'] );
		self::assertSame( 'scan', $response['data']['action'] );
		self::assertSame( 2, $response['data']['summary']['eligible'] );
		self::assertNotSame( '', $response['data']['scanToken'] );
	}

	/**
	 * Test continuation advances an owned token.
	 *
	 * @return void
	 */
	public function test_scan_jobs_continues_owned_session(): void {
		$runtime                     = new FakeRestRuntime();
		$fixture                     = $this->fixture( $runtime );
		$fixture['bulk']->pages[0]   = range( 1, 100 );
		$fixture['bulk']->pages[100] = array( 101 );

		$first = $fixture['controller']->scan_jobs( new FakeRestRequest() );
		$next  = $fixture['controller']->scan_jobs(
			new FakeRestRequest(
				array(
					'scan_token' => $first['data']['scanToken'],
				)
			)
		);

		self::assertSame( 'response', $next['type'] );
		self::assertTrue( $next['data']['progress']['complete'] );
		self::assertSame( 101, $next['data']['progress']['last_processed_id'] );
	}

	/**
	 * Test foreign tokens are rejected safely.
	 *
	 * @return void
	 */
	public function test_scan_jobs_rejects_foreign_tokens(): void {
		$runtime                   = new FakeRestRuntime();
		$fixture                   = $this->fixture( $runtime );
		$fixture['bulk']->pages[0] = array( 55 );

		$first = $fixture['controller']->scan_jobs( new FakeRestRequest() );
		$runtime->current_user_id = 99;

		$response = $fixture['controller']->scan_jobs(
			new FakeRestRequest(
				array(
					'scan_token' => $first['data']['scanToken'],
				)
			)
		);

		self::assertSame( 'error', $response['type'] );
		self::assertSame( 'bulk_scan_session_forbidden', $response['code'] );
	}

	/**
	 * Test queue_jobs rejects incomplete scans.
	 *
	 * @return void
	 */
	public function test_queue_jobs_rejects_incomplete_scan_sessions(): void {
		$runtime                   = new FakeRestRuntime();
		$fixture                   = $this->fixture( $runtime );
		$fixture['bulk']->pages[0] = array( 10, 11 );

		$first = $fixture['controller']->scan_jobs( new FakeRestRequest() );
		$response = $fixture['controller']->queue_jobs(
			new FakeRestRequest(
				array(
					'scan_token' => $first['data']['scanToken'],
				)
			)
		);

		self::assertSame( 'error', $response['type'] );
		self::assertSame( 'bulk_scan_not_complete', $response['code'] );
	}

	/**
	 * Test pause and resume return queue control payloads.
	 *
	 * @return void
	 */
	public function test_pause_and_resume_jobs_return_queue_control_payloads(): void {
		$runtime   = new FakeRestRuntime();
		$fixture   = $this->fixture( $runtime );
		$paused    = $fixture['controller']->pause_jobs();
		$resumed   = $fixture['controller']->resume_jobs();

		self::assertSame( 'response', $paused['type'] );
		self::assertTrue( $paused['data']['queueControl']['paused'] );
		self::assertSame( 'response', $resumed['type'] );
		self::assertFalse( $resumed['data']['queueControl']['paused'] );
	}

	/**
	 * Test queue jobs continues a completed owned session and returns queue progress.
	 *
	 * @return void
	 */
	public function test_queue_jobs_returns_queue_progress_for_completed_scan(): void {
		$runtime                   = new FakeRestRuntime();
		$fixture                   = $this->fixture( $runtime );
		$fixture['bulk']->pages[0] = array( 10, 11 );

		$scan = $fixture['controller']->scan_jobs( new FakeRestRequest() );
		$queue = $fixture['controller']->queue_jobs(
			new FakeRestRequest(
				array(
					'scan_token' => $scan['data']['scanToken'],
				)
			)
		);

		self::assertSame( 'response', $queue['type'] );
		self::assertSame( 'queue', $queue['data']['action'] );
		self::assertTrue( $queue['data']['queueProgress']['complete'] );
		self::assertSame( 2, $queue['data']['queueSummary']['queued'] );
	}

	/**
	 * Test queue jobs reject foreign owned scan tokens safely.
	 *
	 * @return void
	 */
	public function test_queue_jobs_reject_foreign_scan_tokens(): void {
		$runtime                   = new FakeRestRuntime();
		$fixture                   = $this->fixture( $runtime );
		$fixture['bulk']->pages[0] = array( 10 );

		$scan = $fixture['controller']->scan_jobs( new FakeRestRequest() );
		$runtime->current_user_id = 99;
		$queue = $fixture['controller']->queue_jobs(
			new FakeRestRequest(
				array(
					'scan_token' => $scan['data']['scanToken'],
				)
			)
		);

		self::assertSame( 'error', $queue['type'] );
		self::assertSame( 'bulk_scan_session_forbidden', $queue['code'] );
	}

	/**
	 * Test cancel pending jobs returns normalized queue-control payloads.
	 *
	 * @return void
	 */
	public function test_cancel_pending_jobs_returns_result_and_queue_control(): void {
		$runtime  = new FakeRestRuntime();
		$fixture  = $this->fixture( $runtime );
		$response = $fixture['controller']->cancel_pending_jobs();

		self::assertSame( 'response', $response['type'] );
		self::assertSame( 'cancel_pending', $response['data']['action'] );
		self::assertArrayHasKey( 'result', $response['data'] );
		self::assertArrayHasKey( 'queueControl', $response['data'] );
	}

	/**
	 * Build the controller fixture.
	 *
	 * @param FakeRestRuntime $runtime Fake runtime.
	 * @return array<string,mixed>
	 */
	private function fixture( FakeRestRuntime $runtime ): array {
		$bulk       = new FakeBulkScannerRuntime();
		$store      = new FakeAttachmentMetaStore();
		$clock      = new FixedAttachmentClock( 1783612800 );
		$settings   = new FakeSettingsRepository(
			array(
				'enabled_formats' => array( 'webp' ),
			)
		);
		$repository = new DerivativeRepository( $store, new DerivativeManifestSanitizer(), $clock );
		$sessions   = new WordPressTransientBulkScanSessionStore( new FakeTransientStore() );
		$service  = new BulkScanService(
			$bulk,
			$sessions,
			new AttachmentStatusReader( $store ),
			$settings,
			static function (): string {
				return '2026-07-12 00:00:00';
			},
			static function (): string {
				static $sequence = 0;
				++$sequence;

				return sprintf( 'feedfacefeedfacefeedfacefeedfa%02d', $sequence );
			}
		);
		$probe    = new FakeImageFileProbe( array( '/uploads', '/uploads/2026', '/uploads/2026/07' ) );
		$probe->add_file( '/uploads/2026/07/hero.jpg', 1000, 1783526400, 'image/jpeg', 2400, 1600 );
		$collector = new SourceCollector(
			new FakeAttachmentSourceProvider(
				'/uploads/2026/07/hero.jpg',
				array(
					'file'   => '2026/07/hero.jpg',
					'width'  => 2400,
					'height' => 1600,
					'sizes'  => array(),
				),
				'/uploads'
			),
			$probe
		);
		$job_control = new FakeAttachmentJobControl();
		$controls    = new QueueControlStateStore( new FakeOptionStore(), 'hwlio_queue_control_state', static function (): string {
			return '2026-07-12 00:00:00';
		} );
		$queue_service = new BulkQueueService(
			$sessions,
			$service,
			new AttachmentStatusReader( $store ),
			new AttachmentQueueService(
				new FakeQueue(),
				$store,
				$repository,
				$collector,
				new AttachmentFingerprintBuilder(),
				$clock,
				$controls
			),
			$settings,
			$controls,
			static function (): string {
				return '2026-07-12 00:00:00';
			}
		);

		return array(
			'controller' => new JobsController(
				$runtime,
				new RestErrorFactory( $runtime ),
				$service,
				$queue_service,
				new QueueControlService( $controls, $job_control )
			),
			'bulk'       => $bulk,
		);
	}
}
