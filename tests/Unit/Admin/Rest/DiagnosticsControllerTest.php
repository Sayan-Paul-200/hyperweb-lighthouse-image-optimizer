<?php
/**
 * Tests for the diagnostics REST controller.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Admin\Rest;

use HyperWeb\LighthouseImageOptimizer\Admin\Rest\DiagnosticsController;
use HyperWeb\LighthouseImageOptimizer\Admin\Rest\DiagnosticsServiceInterface;
use HyperWeb\LighthouseImageOptimizer\Admin\Rest\RestErrorFactory;
use PHPUnit\Framework\TestCase;

/**
 * Verifies route registration and callback behavior for /diagnostics.
 */
final class DiagnosticsControllerTest extends TestCase {

	/**
	 * Test route registration registers only the diagnostics route.
	 *
	 * @return void
	 */
	public function test_register_routes_registers_diagnostics_route_only(): void {
		$runtime    = new FakeRestRuntime();
		$controller = $this->controller( $runtime );

		$controller->register_routes();

		self::assertCount( 1, $runtime->routes );
		self::assertSame( 'hwlio/v1', $runtime->routes[0]['namespace'] );
		self::assertSame( '/diagnostics', $runtime->routes[0]['route'] );
		self::assertSame( 'GET', $runtime->routes[0]['definitions'][0]['methods'] );
	}

	/**
	 * Test diagnostics payload is returned through the runtime seam.
	 *
	 * @return void
	 */
	public function test_get_diagnostics_returns_report_payload(): void {
		$runtime    = new FakeRestRuntime();
		$controller = $this->controller( $runtime );

		$response = $controller->get_diagnostics();

		self::assertSame( 'response', $response['type'] );
		self::assertSame( 200, $response['status'] );
		self::assertSame( 1, $response['data']['summary']['pass'] );
		self::assertSame( 'delivery_derivative_files', $response['data']['results'][0]['id'] );
	}

	/**
	 * Build the controller.
	 *
	 * @param FakeRestRuntime $runtime Fake runtime.
	 * @return DiagnosticsController
	 */
	private function controller( FakeRestRuntime $runtime ): DiagnosticsController {
		return new DiagnosticsController(
			$runtime,
			new RestErrorFactory( $runtime ),
			new class() implements DiagnosticsServiceInterface {
				/**
				 * Build the diagnostics report payload.
				 *
				 * @return array<string,mixed>
				 */
				public function report(): array {
					return array(
						'summary' => array(
							'total'   => 1,
							'pass'    => 1,
							'warning' => 0,
							'fail'    => 0,
							'info'    => 0,
						),
						'results' => array(
							array(
								'id'      => 'delivery_derivative_files',
								'status'  => 'pass',
								'code'    => 'delivery_derivatives_ok',
								'label'   => 'Delivery derivative files',
								'message' => 'Ready derivative files referenced by metadata are present.',
								'details' => array(),
							),
						),
					);
				}
			}
		);
	}
}
