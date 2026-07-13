<?php
/**
 * Tests for the attachments REST controller.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Admin\Rest;

use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkPreviewService;
use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\WordPressTransientBulkScanSessionStore;
use HyperWeb\LighthouseImageOptimizer\Admin\Rest\AttachmentActionService;
use HyperWeb\LighthouseImageOptimizer\Admin\Rest\AttachmentDetailsService;
use HyperWeb\LighthouseImageOptimizer\Admin\Rest\AttachmentsController;
use HyperWeb\LighthouseImageOptimizer\Admin\MediaLibrary\AttachmentActionAvailability;
use HyperWeb\LighthouseImageOptimizer\Admin\MediaLibrary\AttachmentStatusReader;
use HyperWeb\LighthouseImageOptimizer\Admin\MediaLibrary\MediaAttachmentPresenter;
use HyperWeb\LighthouseImageOptimizer\Admin\Rest\RestErrorFactory;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentFingerprintBuilder;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifestSanitizer;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeRepository;
use HyperWeb\LighthouseImageOptimizer\Image\SourceCollector;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Admin\Bulk\FakeBulkScannerRuntime;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment\FakeAttachmentMetaStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment\FixedAttachmentClock;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeAttachmentSourceProvider;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeImageFileProbe;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeSettingsRepository;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure\FakeTransientStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Queue\FakeQueue;
use PHPUnit\Framework\TestCase;

/**
 * Verifies route registration, permissions, and attachment detail validation.
 */
final class AttachmentsControllerTest extends TestCase {

	private const NOW = 1783612800;

	/**
	 * Test only the attachment-first routes are registered.
	 *
	 * @return void
	 */
	public function test_register_routes_registers_attachment_first_routes_only(): void {
		$runtime    = new FakeRestRuntime();
		$fixture    = $this->controller_fixture( $runtime );
		$controller = $fixture['controller'];

		$controller->register_routes();

		self::assertCount( 7, $runtime->routes );
		self::assertSame(
			array(
				'/attachments',
				'/attachments/(?P<id>[\d]+)',
				'/attachments/(?P<id>[\d]+)/optimize',
				'/attachments/(?P<id>[\d]+)/retry',
				'/attachments/(?P<id>[\d]+)/reconcile',
				'/attachments/(?P<id>[\d]+)/exclude',
				'/attachments/(?P<id>[\d]+)/include',
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
	 * Test upload_files is required before attachment routes can run.
	 *
	 * @return void
	 */
	public function test_permission_callback_requires_upload_files(): void {
		$runtime                               = new FakeRestRuntime();
		$runtime->capabilities['upload_files'] = false;
		$fixture                               = $this->controller_fixture( $runtime );

		$result = $fixture['controller']->can_use_attachment_routes();

		self::assertSame( 'error', $result['type'] );
		self::assertSame( 'rest_forbidden', $result['code'] );
	}

	/**
	 * Test valid image attachments return sanitized details.
	 *
	 * @return void
	 */
	public function test_get_attachment_returns_sanitized_attachment_details(): void {
		$runtime                                = new FakeRestRuntime();
		$runtime->capabilities['edit_post:123'] = true;
		$fixture                                = $this->controller_fixture( $runtime );
		$fixture['store']->meta[123][ LifecyclePolicy::META_DERIVATIVES ] = array(
			'schema_version' => 1,
			'fingerprint'    => null,
			'updated_at'     => self::NOW,
			'sizes'          => array(
				'full' => array(
					'source'  => array(
						'file'   => 'C:/secret/hero.jpg',
						'mime'   => 'image/jpeg',
						'width'  => 2400,
						'height' => 1600,
						'bytes'  => 5000,
					),
					'formats' => array(
						'webp' => array(
							'file'            => 'C:/secret/hero.webp',
							'mime'            => 'image/webp',
							'bytes'           => 3200,
							'quality'         => 82,
							'savings_bytes'   => 1800,
							'savings_percent' => 36,
							'status'          => 'ready',
							'generated_at'    => self::NOW,
						),
					),
				),
			),
		);

		$response = $fixture['controller']->get_attachment( new FakeRestRequest( array( 'id' => 123 ) ) );

		self::assertSame( 'response', $response['type'] );
		self::assertTrue( $response['data']['warnings'] );
		self::assertContains( 'invalid_metadata_ignored', $response['data']['codes'] );
		self::assertSame( array(), $response['data']['manifest']['sizes'] );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Test assertion for serialized payload safety.
		$json = json_encode( $response['data'] );
		self::assertStringNotContainsString( 'C:/secret', is_string( $json ) ? $json : '' );
	}

	/**
	 * Test non-image attachments are rejected.
	 *
	 * @return void
	 */
	public function test_get_attachment_rejects_non_image_attachments(): void {
		$runtime                                = new FakeRestRuntime();
		$runtime->capabilities['edit_post:123'] = true;
		$runtime->images[123]                   = false;
		$fixture                                = $this->controller_fixture( $runtime );

		$response = $fixture['controller']->get_attachment( new FakeRestRequest( array( 'id' => 123 ) ) );

		self::assertSame( 'error', $response['type'] );
		self::assertSame( 'attachment_not_image', $response['code'] );
	}

	/**
	 * Test the collection route returns session-scoped lightweight preview rows.
	 *
	 * @return void
	 */
	public function test_list_attachments_returns_session_scoped_preview_rows(): void {
		$runtime                               = new FakeRestRuntime();
		$fixture                               = $this->controller_fixture( $runtime );
		$fixture['bulk_runtime']->preview[123] = array(
			'attachment_id'   => 123,
			'title'           => 'Hero',
			'filename'        => 'hero.jpg',
			'uploaded_at_gmt' => '2026-07-11 10:00:00',
		);
		$fixture['store']->meta[123][ LifecyclePolicy::META_STATUS ] = array(
			'state'   => 'tampered-state',
			'formats' => array( 'webp' ),
		);
		$fixture['session_store']->save(
			$fixture['session_store']->append_candidate_ids(
				$fixture['session'],
				array( 123 )
			)
		);

		$response = $fixture['controller']->list_attachments(
			new FakeRestRequest(
				array(
					'scan_token' => $fixture['session']->token(),
					'page'       => 1,
					'per_page'   => 20,
				)
			)
		);

		self::assertSame( 'response', $response['type'] );
		self::assertSame( 'Hero', $response['data']['items'][0]['title'] );
		self::assertSame( 'unprocessed', $response['data']['items'][0]['state'] );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Test assertion for serialized payload safety.
		$json = json_encode( $response['data'] );
		self::assertStringNotContainsString( 'C:/', is_string( $json ) ? $json : '' );
	}

	/**
	 * Test missing scan tokens are rejected cleanly on the collection route.
	 *
	 * @return void
	 */
	public function test_list_attachments_rejects_missing_scan_token(): void {
		$runtime  = new FakeRestRuntime();
		$fixture  = $this->controller_fixture( $runtime );
		$response = $fixture['controller']->list_attachments( new FakeRestRequest() );

		self::assertSame( 'error', $response['type'] );
		self::assertSame( 'invalid_scan_token', $response['code'] );
	}

	/**
	 * Test edit_post is required on the resolved attachment.
	 *
	 * @return void
	 */
	public function test_attachment_callbacks_require_edit_post(): void {
		$runtime                                = new FakeRestRuntime();
		$runtime->capabilities['edit_post:123'] = false;
		$fixture                                = $this->controller_fixture( $runtime );

		$response = $fixture['controller']->get_attachment( new FakeRestRequest( array( 'id' => 123 ) ) );

		self::assertSame( 'error', $response['type'] );
		self::assertSame( 'rest_forbidden', $response['code'] );
	}

	/**
	 * Test invalid force values are rejected cleanly.
	 *
	 * @return void
	 */
	public function test_optimize_rejects_invalid_force_flag(): void {
		$runtime                                = new FakeRestRuntime();
		$runtime->capabilities['edit_post:123'] = true;
		$fixture                                = $this->controller_fixture( $runtime );

		$response = $fixture['controller']->optimize_attachment(
			new FakeRestRequest(
				array(
					'id'    => 123,
					'force' => 'maybe',
				)
			)
		);

		self::assertSame( 'error', $response['type'] );
		self::assertSame( 'invalid_force_flag', $response['code'] );
	}

	/**
	 * Build the controller fixture.
	 *
	 * @param FakeRestRuntime $runtime Fake runtime.
	 * @return array<string,mixed>
	 */
	private function controller_fixture( FakeRestRuntime $runtime ): array {
		$store         = new FakeAttachmentMetaStore();
		$clock         = new FixedAttachmentClock( self::NOW );
		$repository    = new DerivativeRepository( $store, new DerivativeManifestSanitizer(), $clock );
		$details       = new AttachmentDetailsService( $repository );
		$bulk_runtime  = new FakeBulkScannerRuntime();
		$session_store = new WordPressTransientBulkScanSessionStore( new FakeTransientStore() );
		$session       = \HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkScanSession::start(
			'12341234123412341234123412341234',
			$runtime->current_user_id,
			'2026-07-12 00:00:00',
			new \HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkScanFilters()
		);
		$probe         = new FakeImageFileProbe( array( 'C:/site/wp-content/uploads' ) );
		$probe->add_file( 'C:/site/wp-content/uploads/2026/07/hero.jpg', 5000, 1783526400, 'image/jpeg', 2400, 1600 );
		$service = new AttachmentActionService(
			new FakeQueue(),
			new FakeSettingsRepository(),
			$store,
			$repository,
			new SourceCollector(
				new FakeAttachmentSourceProvider(
					'C:/site/wp-content/uploads/2026/07/hero.jpg',
					array(
						'file'   => '2026/07/hero.jpg',
						'width'  => 2400,
						'height' => 1600,
						'sizes'  => array(),
					),
					'C:/site/wp-content/uploads'
				),
				$probe
			),
			new AttachmentFingerprintBuilder(),
			$clock,
			$details
		);

		return array(
			'controller'    => new AttachmentsController(
				$runtime,
				new RestErrorFactory( $runtime ),
				$details,
				$service,
				new BulkPreviewService(
					$session_store,
					$bulk_runtime,
					new AttachmentStatusReader( $store ),
					new MediaAttachmentPresenter( new AttachmentActionAvailability() ),
					new FakeSettingsRepository()
				)
			),
			'store'         => $store,
			'session'       => $session,
			'session_store' => $session_store,
			'bulk_runtime'  => $bulk_runtime,
		);
	}
}
