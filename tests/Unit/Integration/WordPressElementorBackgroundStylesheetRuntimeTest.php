<?php
/**
 * Tests for the WordPress-backed Elementor background stylesheet runtime.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Integration;

use HyperWeb\LighthouseImageOptimizer\Integration\WordPressElementorBackgroundStylesheetRuntime;
use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__ ) . '/Delivery/DeliveryTestWordPressShim.php';

/**
 * Verifies the companion stylesheet runtime resolves request facts conservatively.
 */
final class WordPressElementorBackgroundStylesheetRuntimeTest extends TestCase {

	/**
	 * Reset globals after each test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		unset(
			$GLOBALS['hwlio_test_is_admin'],
			$GLOBALS['hwlio_test_is_feed'],
			$GLOBALS['hwlio_test_wp_doing_ajax'],
			$GLOBALS['hwlio_test_wp_is_json_request'],
			$GLOBALS['hwlio_test_is_singular'],
			$GLOBALS['hwlio_test_queried_object_id'],
			$GLOBALS['hwlio_test_enqueued_styles']
		);

		parent::tearDown();
	}

	/**
	 * Test non-frontend requests fail open and do not expose a document ID.
	 *
	 * @return void
	 */
	public function test_non_frontend_requests_are_ineligible(): void {
		$GLOBALS['hwlio_test_is_admin'] = true;

		$runtime = new WordPressElementorBackgroundStylesheetRuntime();

		self::assertFalse( $runtime->is_frontend_request() );
		self::assertSame( 0, $runtime->current_singular_document_id() );
	}

	/**
	 * Test the runtime resolves the current singular document and enqueues styles.
	 *
	 * @return void
	 */
	public function test_runtime_reads_current_singular_document_and_enqueues_styles(): void {
		$GLOBALS['hwlio_test_is_singular']       = true;
		$GLOBALS['hwlio_test_queried_object_id'] = 501;

		$runtime = new WordPressElementorBackgroundStylesheetRuntime();
		$runtime->enqueue_stylesheet( 'hwlio-test', 'https://example.test/wp-content/uploads/test.css', 'abc123' );

		self::assertTrue( $runtime->is_frontend_request() );
		self::assertSame( 501, $runtime->current_singular_document_id() );
		self::assertArrayHasKey( 'hwlio-test', $GLOBALS['hwlio_test_enqueued_styles'] );
		self::assertSame( 'abc123', $GLOBALS['hwlio_test_enqueued_styles']['hwlio-test']['ver'] );
	}

	/**
	 * Test the runtime resolves a reliable breakpoint map from the Elementor plugin seam.
	 *
	 * @return void
	 */
	public function test_runtime_resolves_a_breakpoint_map_when_elementor_exposes_one(): void {
		$plugin  = (object) array(
			'breakpoints' => new class() {
				/**
				 * Return active breakpoint configs for the test fixture.
				 *
				 * @return array<string,array<string,int>>
				 */
				public function get_active_breakpoints(): array {
					return array(
						'mobile' => array( 'value' => 767 ),
						'tablet' => array( 'value' => 1024 ),
					);
				}
			},
		);
		$runtime = new WordPressElementorBackgroundStylesheetRuntime(
			null,
			static function () use ( $plugin ) {
				return $plugin;
			}
		);

		$map = $runtime->breakpoint_map();

		self::assertNotNull( $map );
		self::assertSame(
			array(
				'desktop' => '(min-width: 1025px)',
				'tablet'  => '(min-width: 768px) and (max-width: 1024px)',
				'mobile'  => '(max-width: 767px)',
			),
			$map->to_array()
		);
	}
}
