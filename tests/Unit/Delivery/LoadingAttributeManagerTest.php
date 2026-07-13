<?php
/**
 * Tests for the loading attribute manager.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Delivery;

require_once __DIR__ . '/DeliveryTestWordPressShim.php';

use HyperWeb\LighthouseImageOptimizer\Delivery\CriticalImageRegistry;
use HyperWeb\LighthouseImageOptimizer\Delivery\LoadingAttributeManager;
use HyperWeb\LighthouseImageOptimizer\Delivery\WordPressImageMarkupAnalyzer;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeSettingsRepository;
use PHPUnit\Framework\TestCase;

/**
 * Verifies explicit critical-image loading overrides.
 */
final class LoadingAttributeManagerTest extends TestCase {

	/**
	 * Clean up test filter state.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		unset( $GLOBALS['hwlio_test_filters'] );
	}

	/**
	 * Test hook registration adds only the loading optimization filter.
	 *
	 * @return void
	 */
	public function test_register_hooks_adds_only_loading_optimization_filter(): void {
		$hooks = new HookRegistrar();

		$this->manager()->register_hooks( $hooks );

		self::assertSame( array(), $hooks->actions() );
		self::assertCount( 1, $hooks->filters() );
		self::assertSame( 'wp_get_loading_optimization_attributes', $hooks->filters()[0]['hook'] );
		self::assertSame( 4, $hooks->filters()[0]['accepted_args'] );
	}

	/**
	 * Test non-critical images preserve core attributes unchanged.
	 *
	 * @return void
	 */
	public function test_non_critical_images_preserve_core_attributes(): void {
		$result = $this->manager()->filter_loading_optimization_attributes(
			array( 'loading' => 'lazy' ),
			'img',
			array(
				'class' => 'wp-image-999',
				'src'   => 'https://example.test/hero.jpg',
			),
			'the_content'
		);

		self::assertSame( array( 'loading' => 'lazy' ), $result );
	}

	/**
	 * Test the primary critical image removes lazy, becomes eager, and gets high priority once.
	 *
	 * @return void
	 */
	public function test_primary_critical_image_becomes_eager_and_gets_high_priority_once(): void {
		$manager = $this->manager_with_primary( 123 );

		$first  = $manager->filter_loading_optimization_attributes(
			array( 'loading' => 'lazy' ),
			'img',
			array(
				'class' => 'wp-image-123',
				'src'   => 'https://example.test/uploads/hero.jpg',
			),
			'the_content'
		);
		$second = $manager->filter_loading_optimization_attributes(
			array( 'loading' => 'lazy' ),
			'img',
			array(
				'class' => 'wp-image-123',
				'src'   => 'https://example.test/uploads/hero.jpg',
			),
			'the_content'
		);

		self::assertSame( 'eager', $first['loading'] );
		self::assertSame( 'high', $first['fetchpriority'] );
		self::assertSame( 'eager', $second['loading'] );
		self::assertArrayNotHasKey( 'fetchpriority', $second );
	}

	/**
	 * Test secondary critical images are de-lazied but do not receive high priority.
	 *
	 * @return void
	 */
	public function test_secondary_critical_images_are_de_lazied_without_high_priority(): void {
		$GLOBALS['hwlio_test_filters'] = array(
			'hwlio_critical_image_selection' => static function ( array $selection ): array {
				unset( $selection );

				return array(
					'primary_attachment_id'   => 123,
					'critical_attachment_ids' => array( 123, 456 ),
					'critical_urls'           => array(),
				);
			},
		);

		$result = $this->manager()->filter_loading_optimization_attributes(
			array( 'loading' => 'lazy' ),
			'img',
			array(
				'class' => 'wp-image-456',
				'src'   => 'https://example.test/uploads/logo.jpg',
			),
			'the_content'
		);

		self::assertArrayNotHasKey( 'loading', $result );
		self::assertArrayNotHasKey( 'fetchpriority', $result );
	}

	/**
	 * Test fallback markup rewrite applies the same explicit overrides.
	 *
	 * @return void
	 */
	public function test_markup_rewrite_applies_same_overrides_without_touching_unrelated_attributes(): void {
		$html    = '<img class="wp-image-123" src="https://example.test/uploads/hero.jpg" alt="Hero" loading="lazy" decoding="async">';
		$manager = $this->manager_with_primary( 123 );
		$result  = $manager->apply_to_fallback_markup( $html, 123 );

		self::assertStringContainsString( 'loading="eager"', $result );
		self::assertStringContainsString( 'fetchpriority="high"', $result );
		self::assertStringContainsString( 'decoding="async"', $result );
	}

	/**
	 * Test malformed markup fails open to the original HTML.
	 *
	 * @return void
	 */
	public function test_markup_rewrite_fails_open_on_malformed_markup(): void {
		$html = '<span>broken</span><img src="https://example.test/uploads/hero.jpg">';

		self::assertSame( $html, $this->manager_with_primary( 123 )->apply_to_fallback_markup( $html, 123 ) );
	}

	/**
	 * Test the manager fully no-ops when loading overrides are disabled.
	 *
	 * @return void
	 */
	public function test_manager_no_ops_when_loading_overrides_are_disabled(): void {
		$manager = $this->manager(
			new FakeSettingsRepository(
				array(
					'loading_attribute_overrides_enabled' => false,
				)
			)
		);

		self::assertSame(
			array( 'loading' => 'lazy' ),
			$manager->filter_loading_optimization_attributes(
				array( 'loading' => 'lazy' ),
				'img',
				array(
					'class' => 'wp-image-123',
					'src'   => 'https://example.test/uploads/hero.jpg',
				),
				'the_content'
			)
		);
		self::assertSame(
			'<img class="wp-image-123" src="https://example.test/uploads/hero.jpg" loading="lazy">',
			$manager->apply_to_fallback_markup(
				'<img class="wp-image-123" src="https://example.test/uploads/hero.jpg" loading="lazy">',
				123
			)
		);
	}

	/**
	 * Build manager fixture.
	 *
	 * @param FakeSettingsRepository|null $settings Settings.
	 * @return LoadingAttributeManager
	 */
	private function manager( ?FakeSettingsRepository $settings = null ): LoadingAttributeManager {
		$runtime  = new FakeAttachmentImageRuntime();
		$settings = $settings ?? new FakeSettingsRepository();
		$store    = new FakeCriticalImagePostMetaStore();

		return new LoadingAttributeManager(
			$settings,
			new CriticalImageRegistry( $runtime, $settings, $store ),
			$runtime,
			new WordPressImageMarkupAnalyzer()
		);
	}

	/**
	 * Build manager fixture with one primary image selected by filter.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return LoadingAttributeManager
	 */
	private function manager_with_primary( int $attachment_id ): LoadingAttributeManager {
		$GLOBALS['hwlio_test_filters'] = array(
			'hwlio_critical_image_selection' => static function ( array $selection ) use ( $attachment_id ): array {
				unset( $selection );

				return array(
					'primary_attachment_id'   => $attachment_id,
					'critical_attachment_ids' => array( $attachment_id ),
					'critical_urls'           => array(),
				);
			},
		);

		return $this->manager();
	}
}
