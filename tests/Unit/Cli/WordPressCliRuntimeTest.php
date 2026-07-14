<?php
/**
 * Tests for the WordPress CLI runtime adapter.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Cli;

require_once __DIR__ . '/CliTestWordPressShim.php';

use HyperWeb\LighthouseImageOptimizer\Cli\WordPressCliRuntime;
use PHPUnit\Framework\TestCase;

/**
 * Verifies the WP-CLI runtime adapter uses the expected shims.
 */
final class WordPressCliRuntimeTest extends TestCase {

	/**
	 * Reset shim state.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		\WP_CLI::$commands           = array();
		\WP_CLI::$lines              = array();
		\WP_CLI::$warnings           = array();
		\WP_CLI::$halts              = array();
		$GLOBALS['hwlio_test_cli_tables'] = array();
	}

	/**
	 * Test the runtime reports availability and registers commands.
	 *
	 * @return void
	 */
	public function test_register_command_uses_wp_cli(): void {
		$runtime = new WordPressCliRuntime();
		$command = new \stdClass();

		self::assertTrue( $runtime->available() );

		$runtime->register_command( 'hwlio', $command );

		self::assertSame( $command, \WP_CLI::$commands['hwlio'] );
	}

	/**
	 * Test line, warning, JSON, table, and halt use the underlying shims.
	 *
	 * @return void
	 */
	public function test_output_methods_use_wp_cli_shims(): void {
		$runtime = new WordPressCliRuntime();

		$runtime->line( 'Hello' );
		$runtime->warning( 'Careful' );
		$runtime->error( 'Broken' );
		$runtime->format_items( 'table', array( array( 'key' => 'value' ) ), array( 'key' ) );
		$runtime->json( array( 'ok' => true ) );
		$runtime->halt( 2 );

		self::assertSame( 'Hello', \WP_CLI::$lines[0] );
		self::assertSame( 'Careful', \WP_CLI::$warnings[0] );
		self::assertSame( 'Error: Broken', \WP_CLI::$lines[1] );
		self::assertSame( 'table', $GLOBALS['hwlio_test_cli_tables'][0]['format'] );
		self::assertSame( '{"ok":true}', \WP_CLI::$lines[2] );
		self::assertSame( 2, \WP_CLI::$halts[0] );
	}
}
