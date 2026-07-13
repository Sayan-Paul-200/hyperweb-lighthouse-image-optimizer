<?php
/**
 * Tests for the logs REST controller.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Admin\Rest;

use HyperWeb\LighthouseImageOptimizer\Admin\Rest\LogsController;
use HyperWeb\LighthouseImageOptimizer\Admin\Rest\RestErrorFactory;
use HyperWeb\LighthouseImageOptimizer\Logging\LogBrowserService;
use HyperWeb\LighthouseImageOptimizer\Logging\LogDeletionService;
use HyperWeb\LighthouseImageOptimizer\Logging\LogRetentionService;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsRepository;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure\FakeOptionStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Logging\FakeLogDatabase;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Logging\FakeLogReadDatabase;
use PHPUnit\Framework\TestCase;

/**
 * Verifies route registration and safe logs behavior.
 */
final class LogsControllerTest extends TestCase {

	/**
	 * Test route registration adds only logs and retention routes.
	 *
	 * @return void
	 */
	public function test_register_routes_registers_logs_routes_only(): void {
		$runtime    = new FakeRestRuntime();
		$controller = $this->fixture( $runtime )['controller'];

		$controller->register_routes();

		self::assertCount( 2, $runtime->routes );
		self::assertSame( '/logs', $runtime->routes[0]['route'] );
		self::assertSame( '/logs/retention', $runtime->routes[1]['route'] );
	}

	/**
	 * Test manage_options is required for logs routes.
	 *
	 * @return void
	 */
	public function test_permission_callback_requires_manage_options(): void {
		$runtime                                 = new FakeRestRuntime();
		$runtime->capabilities['manage_options'] = false;
		$controller                              = $this->fixture( $runtime )['controller'];

		$result = $controller->can_manage_options();

		self::assertSame( 'error', $result['type'] );
		self::assertSame( 'rest_forbidden', $result['code'] );
	}

	/**
	 * Test invalid filters are rejected cleanly.
	 *
	 * @return void
	 */
	public function test_get_logs_rejects_invalid_filters(): void {
		$runtime    = new FakeRestRuntime();
		$controller = $this->fixture( $runtime )['controller'];

		$response = $controller->get_logs(
			new FakeRestRequest(
				array(
					'level' => 'not-real',
				)
			)
		);

		self::assertSame( 'error', $response['type'] );
		self::assertSame( 'invalid_log_level', $response['code'] );
	}

	/**
	 * Test paginated log rows are returned without context leakage.
	 *
	 * @return void
	 */
	public function test_get_logs_returns_safe_paginated_payload(): void {
		$runtime                = new FakeRestRuntime();
		$fixture                = $this->fixture( $runtime );
		$fixture['read']->rows  = array(
			array(
				'created_at_gmt' => '2026-07-12 12:00:00',
				'level'          => 'error',
				'code'           => 'worker_unexpected_error',
				'message'        => 'Unexpected worker failure.',
				'attachment_id'  => 17,
				'job_id'         => 'job-17',
				'context_json'   => '{"path":"D:\\\\secret.txt"}',
			),
		);
		$fixture['read']->count = 1;

		$response = $fixture['controller']->get_logs(
			new FakeRestRequest(
				array(
					'level' => 'error',
					'page'  => 1,
				)
			)
		);

		self::assertSame( 'response', $response['type'] );
		self::assertSame( 1, $response['data']['totalItems'] );
		self::assertSame( 'worker_unexpected_error', $response['data']['items'][0]['code'] );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Test assertion for serialized payload safety.
		$json = json_encode( $response['data'] );
		self::assertStringNotContainsString( 'context_json', is_string( $json ) ? $json : '' );
	}

	/**
	 * Test retention saves return normalized saved values.
	 *
	 * @return void
	 */
	public function test_update_retention_returns_normalized_saved_value(): void {
		$runtime  = new FakeRestRuntime();
		$fixture  = $this->fixture( $runtime );
		$response = $fixture['controller']->update_retention(
			new FakeRestRequest(
				array(
					'retention_days' => 9000,
				)
			)
		);

		self::assertSame( 'response', $response['type'] );
		self::assertSame( 3650, $response['data']['result']['retentionDays'] );
	}

	/**
	 * Test delete_logs returns bounded batch progress.
	 *
	 * @return void
	 */
	public function test_delete_logs_returns_bounded_batch_progress(): void {
		$runtime                                  = new FakeRestRuntime();
		$fixture                                  = $this->fixture( $runtime );
		$fixture['database']->delete_batch_result = LogDeletionService::BATCH_SIZE;

		$response = $fixture['controller']->delete_logs();

		self::assertSame( 'response', $response['type'] );
		self::assertSame( 'clear_all', $response['data']['action'] );
		self::assertFalse( $response['data']['result']['complete'] );
		self::assertSame( LogDeletionService::BATCH_SIZE, $response['data']['result']['deletedCount'] );
	}

	/**
	 * Build a controller fixture.
	 *
	 * @param FakeRestRuntime $runtime Fake runtime.
	 * @return array<string,mixed>
	 */
	private function fixture( FakeRestRuntime $runtime ): array {
		$read     = new FakeLogReadDatabase();
		$database = new FakeLogDatabase();
		$options  = new FakeOptionStore(
			array(
				SettingsRepository::OPTION_NAME => array(
					'log_retention_days' => 30,
				),
			)
		);

		return array(
			'read'       => $read,
			'database'   => $database,
			'controller' => new LogsController(
				$runtime,
				new RestErrorFactory( $runtime ),
				new LogBrowserService( $read, 'wp_hwlio_logs' ),
				new LogDeletionService( $database, 'wp_hwlio_logs' ),
				new LogRetentionService( SettingsRepository::for_options( $options ) )
			),
		);
	}
}
