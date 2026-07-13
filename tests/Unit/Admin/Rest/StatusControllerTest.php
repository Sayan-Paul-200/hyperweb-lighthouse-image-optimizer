<?php
/**
 * Tests for the status REST controller.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Admin\Rest;

use HyperWeb\LighthouseImageOptimizer\Admin\Rest\DashboardEnvironmentSummaryService;
use HyperWeb\LighthouseImageOptimizer\Admin\Rest\RestErrorFactory;
use HyperWeb\LighthouseImageOptimizer\Admin\Rest\StatisticsCacheReader;
use HyperWeb\LighthouseImageOptimizer\Admin\Rest\StatusController;
use HyperWeb\LighthouseImageOptimizer\Admin\Rest\StatusRefreshService;
use HyperWeb\LighthouseImageOptimizer\Admin\Rest\StatusSummaryService;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\EnvironmentInspector;
use HyperWeb\LighthouseImageOptimizer\Logging\RecentFailureLogReader;
use HyperWeb\LighthouseImageOptimizer\Queue\QueueControlService;
use HyperWeb\LighthouseImageOptimizer\Queue\QueueControlStateStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeSettingsRepository;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure\FakeEnvironmentProbe;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure\FakeOptionStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure\FakeSingleActionScheduler;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Logging\FakeLogReadDatabase;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Queue\FakeAttachmentJobControl;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Queue\FakeQueue;
use PHPUnit\Framework\TestCase;

/**
 * Verifies route registration and permission handling for /status.
 */
final class StatusControllerTest extends TestCase {

	/**
	 * Test route registration registers only the status route.
	 *
	 * @return void
	 */
	public function test_register_routes_registers_status_route_only(): void {
		$runtime    = new FakeRestRuntime();
		$controller = $this->controller( $runtime );

		$controller->register_routes();

		self::assertCount( 1, $runtime->routes );
		self::assertSame( 'hwlio/v1', $runtime->routes[0]['namespace'] );
		self::assertSame( '/status', $runtime->routes[0]['route'] );
		self::assertSame( 'GET', $runtime->routes[0]['definitions'][0]['methods'] );
		self::assertSame( 'POST', $runtime->routes[0]['definitions'][1]['methods'] );
	}

	/**
	 * Test manage_options is required.
	 *
	 * @return void
	 */
	public function test_permission_callback_requires_manage_options(): void {
		$runtime                                 = new FakeRestRuntime();
		$runtime->capabilities['manage_options'] = false;
		$controller                              = $this->controller( $runtime );

		$result = $controller->can_manage_options();

		self::assertSame( 'error', $result['type'] );
		self::assertSame( 'rest_forbidden', $result['code'] );
	}

	/**
	 * Test callback returns the normalized summary payload.
	 *
	 * @return void
	 */
	public function test_get_status_returns_summary_payload(): void {
		$runtime    = new FakeRestRuntime();
		$controller = $this->controller( $runtime );

		$response = $controller->get_status();

		self::assertSame( 'response', $response['type'] );
		self::assertSame( 200, $response['status'] );
		self::assertArrayHasKey( 'queue', $response['data'] );
		self::assertArrayHasKey( 'statistics', $response['data'] );
		self::assertArrayHasKey( 'settings', $response['data'] );
		self::assertArrayHasKey( 'environment', $response['data'] );
		self::assertArrayHasKey( 'recentFailures', $response['data'] );
		self::assertArrayHasKey( 'refresh', $response['data'] );
	}

	/**
	 * Test recalculate_status returns a queued response when scheduling succeeds.
	 *
	 * @return void
	 */
	public function test_recalculate_status_returns_queued_response(): void {
		$runtime    = new FakeRestRuntime();
		$scheduler  = new FakeSingleActionScheduler();
		$controller = $this->controller( $runtime, $scheduler );

		$response = $controller->recalculate_status();

		self::assertSame( 'response', $response['type'] );
		self::assertSame( 200, $response['status'] );
		self::assertSame( 'queued', $response['data']['result_code'] );
	}

	/**
	 * Test recalculate_status returns already_pending when one exists.
	 *
	 * @return void
	 */
	public function test_recalculate_status_returns_already_pending_when_scheduled(): void {
		$runtime              = new FakeRestRuntime();
		$scheduler            = new FakeSingleActionScheduler();
		$scheduler->scheduled = true;
		$controller           = $this->controller( $runtime, $scheduler );

		$response = $controller->recalculate_status();

		self::assertSame( 'already_pending', $response['data']['result_code'] );
	}

	/**
	 * Test recalculate_status returns an error when scheduling fails.
	 *
	 * @return void
	 */
	public function test_recalculate_status_returns_error_when_scheduling_fails(): void {
		$runtime                   = new FakeRestRuntime();
		$scheduler                 = new FakeSingleActionScheduler();
		$scheduler->enqueue_result = false;
		$controller                = $this->controller( $runtime, $scheduler );

		$response = $controller->recalculate_status();

		self::assertSame( 'error', $response['type'] );
		self::assertSame( 'status_recalculate_unavailable', $response['code'] );
	}

	/**
	 * Build the controller.
	 *
	 * @param FakeRestRuntime                $runtime Fake runtime.
	 * @param FakeSingleActionScheduler|null $scheduler Optional scheduler.
	 * @return StatusController
	 */
	private function controller( FakeRestRuntime $runtime, ?FakeSingleActionScheduler $scheduler = null ): StatusController {
		$scheduler = $scheduler ?? new FakeSingleActionScheduler();
		$settings  = new FakeSettingsRepository();

		return new StatusController(
			$runtime,
			new RestErrorFactory( $runtime ),
			new StatusSummaryService(
				new FakeQueue(),
				new StatisticsCacheReader( new FakeOptionStore() ),
				$settings,
				new DashboardEnvironmentSummaryService(
					new EnvironmentInspector( new FakeEnvironmentProbe(), '7.4', '6.5' ),
					$settings
				),
				new RecentFailureLogReader( new FakeLogReadDatabase(), 'wp_hwlio_logs' ),
				new StatusRefreshService( $scheduler ),
				new QueueControlService(
					new QueueControlStateStore(
						new FakeOptionStore(),
						'hwlio_queue_control_state',
						static function (): string {
							return '2026-07-12 00:00:00';
						}
					),
					new FakeAttachmentJobControl()
				)
			),
			new StatusRefreshService( $scheduler )
		);
	}
}
