<?php
/**
 * Tests for the CLI command provider.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Cli;

use HyperWeb\LighthouseImageOptimizer\Cli\CliCommands;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;
use PHPUnit\Framework\TestCase;

/**
 * Verifies CLI hook registration and root command registration.
 */
final class CliCommandsTest extends TestCase {

	/**
	 * Test provider registers only cli_init.
	 *
	 * @return void
	 */
	public function test_register_hooks_adds_only_cli_init(): void {
		$hooks    = new HookRegistrar();
		$provider = new CliCommands( new FakeCliRuntime(), new \stdClass() );

		$provider->register_hooks( $hooks );

		self::assertCount( 1, $hooks->actions() );
		self::assertSame( 'cli_init', $hooks->actions()[0]['hook'] );
		self::assertSame( 10, $hooks->actions()[0]['priority'] );
		self::assertSame( 0, $hooks->actions()[0]['accepted_args'] );
		self::assertSame( array(), $hooks->filters() );
	}

	/**
	 * Test command registration occurs only when CLI is available.
	 *
	 * @return void
	 */
	public function test_register_command_skips_when_cli_is_unavailable(): void {
		$runtime               = new FakeCliRuntime();
		$runtime->is_available = false;
		$provider              = new CliCommands( $runtime, new \stdClass() );

		$provider->register_command();

		self::assertSame( array(), $runtime->commands );
	}

	/**
	 * Test the root command is registered with the expected subcommands.
	 *
	 * @return void
	 */
	public function test_register_command_registers_hwlio_root_command(): void {
		$runtime  = new FakeCliRuntime();
		$command  = new \stdClass();
		$provider = new CliCommands( $runtime, $command );

		$provider->register_command();

		self::assertArrayHasKey( 'hwlio', $runtime->commands );
		self::assertSame( $command, $runtime->commands['hwlio'] );
	}
}
