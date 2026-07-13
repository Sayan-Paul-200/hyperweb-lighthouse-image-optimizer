<?php
/**
 * Tests for the WordPress-backed Elementor runtime seam.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Integration;

use HyperWeb\LighthouseImageOptimizer\Integration\WordPressElementorRuntime;
use PHPUnit\Framework\TestCase;

/**
 * Verifies Elementor request-mode helpers normalize safely.
 */
final class WordPressElementorRuntimeTest extends TestCase {

	/**
	 * Test unavailable runtime fails open safely.
	 *
	 * @return void
	 */
	public function test_unavailable_runtime_reports_all_modes_false(): void {
		$runtime = new WordPressElementorRuntime(
			static function () {
				return null;
			},
			static function (): array {
				return array();
			}
		);

		self::assertFalse( $runtime->is_available() );
		self::assertFalse( $runtime->is_editor_mode() );
		self::assertFalse( $runtime->is_preview_mode() );
	}

	/**
	 * Test supported editor-mode detection prefers the plugin runtime when available.
	 *
	 * @return void
	 */
	public function test_editor_mode_detection_prefers_supported_plugin_runtime(): void {
		$plugin  = (object) array(
			'editor'  => new class() {
				/**
				 * Report editor mode for the test fixture.
				 *
				 * @return bool
				 */
				public function is_edit_mode(): bool {
					return true;
				}
			},
			'preview' => new class() {
				/**
				 * Report preview mode for the test fixture.
				 *
				 * @return bool
				 */
				public function is_preview_mode(): bool {
					return false;
				}
			},
		);
		$runtime = new WordPressElementorRuntime(
			static function () use ( $plugin ) {
				return $plugin;
			},
			static function (): array {
				return array();
			}
		);

		self::assertTrue( $runtime->is_available() );
		self::assertTrue( $runtime->is_editor_mode() );
		self::assertFalse( $runtime->is_preview_mode() );
	}

	/**
	 * Test preview-mode detection prefers the plugin runtime when available.
	 *
	 * @return void
	 */
	public function test_preview_mode_detection_prefers_supported_plugin_runtime(): void {
		$plugin  = (object) array(
			'editor'  => new class() {
				/**
				 * Report editor mode for the test fixture.
				 *
				 * @return bool
				 */
				public function is_edit_mode(): bool {
					return false;
				}
			},
			'preview' => new class() {
				/**
				 * Report preview mode for the test fixture.
				 *
				 * @return bool
				 */
				public function is_preview_mode(): bool {
					return true;
				}
			},
		);
		$runtime = new WordPressElementorRuntime(
			static function () use ( $plugin ) {
				return $plugin;
			},
			static function (): array {
				return array();
			}
		);

		self::assertTrue( $runtime->is_available() );
		self::assertFalse( $runtime->is_editor_mode() );
		self::assertTrue( $runtime->is_preview_mode() );
	}
}
