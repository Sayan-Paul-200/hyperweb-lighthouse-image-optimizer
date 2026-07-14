<?php
/**
 * Tests for site-local settings persistence in multisite-aware option flows.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Settings;

require_once dirname( __DIR__ ) . '/Integration/Multisite/MultisiteTestWordPressShim.php';
require_once __DIR__ . '/SettingsTestFilterShim.php';

use HyperWeb\LighthouseImageOptimizer\Infrastructure\WordPressOptionStore;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsRepository;
use PHPUnit\Framework\TestCase;

/**
 * Verifies settings remain site-local across blog switches.
 */
final class SettingsRepositoryMultisiteTest extends TestCase {

	/**
	 * Clean up shim globals.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		unset(
			$GLOBALS['hwlio_test_current_blog_id'],
			$GLOBALS['hwlio_test_blog_stack'],
			$GLOBALS['hwlio_test_options_by_site'],
			$GLOBALS['hwlio_test_autoload_by_site'],
			$GLOBALS['hwlio_test_default_settings_filter'],
			$GLOBALS['hwlio_test_filters']
		);
	}

	/**
	 * Test site settings persist independently per site.
	 *
	 * @return void
	 */
	public function test_settings_persist_independently_per_site(): void {
		$GLOBALS['hwlio_test_current_blog_id'] = 1;

		$repository = SettingsRepository::for_options( new WordPressOptionStore() );
		$repository->save( array( 'automatic_optimization' => true ) );

		\switch_to_blog( 2 );
		$repository->save( array( 'automatic_optimization' => false ) );

		self::assertFalse( $repository->automatic_optimization_enabled() );

		\restore_current_blog();

		self::assertTrue( $repository->automatic_optimization_enabled() );
		self::assertNotSame(
			$GLOBALS['hwlio_test_options_by_site'][1][ SettingsRepository::OPTION_NAME ],
			$GLOBALS['hwlio_test_options_by_site'][2][ SettingsRepository::OPTION_NAME ]
		);
	}
}
