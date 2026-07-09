<?php
/**
 * Tests for the hook registrar.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;
use PHPUnit\Framework\TestCase;

/**
 * Verifies that hook definitions can be composed without WordPress loaded.
 */
final class HookRegistrarTest extends TestCase {

	/**
	 * Test action definitions are collected.
	 *
	 * @return void
	 */
	public function test_add_action_collects_action_definition(): void {
		$registrar = new HookRegistrar();
		$callback  = static function (): void {
		};

		$registrar->add_action( 'plugins_loaded', $callback, 9, 2 );

		self::assertCount( 1, $registrar->actions() );
		self::assertSame( 'plugins_loaded', $registrar->actions()[0]['hook'] );
		self::assertSame( $callback, $registrar->actions()[0]['callback'] );
		self::assertSame( 9, $registrar->actions()[0]['priority'] );
		self::assertSame( 2, $registrar->actions()[0]['accepted_args'] );
		self::assertSame( array(), $registrar->filters() );
		self::assertFalse( $registrar->is_registered() );
	}

	/**
	 * Test filter definitions are collected.
	 *
	 * @return void
	 */
	public function test_add_filter_collects_filter_definition(): void {
		$registrar = new HookRegistrar();
		$callback  = static function ( string $value ): string {
			return $value;
		};

		$registrar->add_filter( 'the_content', $callback, 12, 1 );

		self::assertCount( 1, $registrar->filters() );
		self::assertSame( 'the_content', $registrar->filters()[0]['hook'] );
		self::assertSame( $callback, $registrar->filters()[0]['callback'] );
		self::assertSame( 12, $registrar->filters()[0]['priority'] );
		self::assertSame( 1, $registrar->filters()[0]['accepted_args'] );
		self::assertSame( array(), $registrar->actions() );
	}
}
