<?php
/**
 * Tests for the critical image registry.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Delivery;

require_once __DIR__ . '/DeliveryTestWordPressShim.php';

use HyperWeb\LighthouseImageOptimizer\Delivery\CriticalImageRegistry;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Integration\Multisite\FakeSiteContextRuntime;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeSettingsRepository;
use PHPUnit\Framework\TestCase;

/**
 * Verifies per-request critical-image selection resolution.
 */
final class CriticalImageRegistryTest extends TestCase {

	/**
	 * Clean up test filter state.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		unset( $GLOBALS['hwlio_test_filters'] );
	}

	/**
	 * Test empty state resolves to an empty selection.
	 *
	 * @return void
	 */
	public function test_registry_resolves_empty_selection_without_built_in_sources(): void {
		$selection = $this->registry()->resolve();

		self::assertNull( $selection->primary_attachment_id() );
		self::assertSame( array(), $selection->critical_attachment_ids() );
		self::assertSame( array(), $selection->critical_urls() );
		self::assertNull( $selection->preload_attachment_id() );
	}

	/**
	 * Test a valid post/page stored image becomes the primary critical attachment.
	 *
	 * @return void
	 */
	public function test_valid_post_page_meta_becomes_primary(): void {
		$runtime            = new FakeAttachmentImageRuntime();
		$runtime->post_id   = 55;
		$runtime->post_type = 'post';
		$store              = new FakeCriticalImagePostMetaStore();
		$store->values[55]  = 123;

		$selection = $this->registry( $runtime, new FakeSettingsRepository(), $store )->resolve();

		self::assertSame( 123, $selection->primary_attachment_id() );
		self::assertSame( array( 123 ), $selection->critical_attachment_ids() );
		self::assertSame( 123, $selection->preload_attachment_id() );
	}

	/**
	 * Test the enabled custom logo becomes a distinct secondary critical image.
	 *
	 * @return void
	 */
	public function test_enabled_custom_logo_becomes_distinct_secondary(): void {
		$runtime                            = new FakeAttachmentImageRuntime();
		$runtime->post_id                   = 55;
		$runtime->post_type                 = 'page';
		$runtime->custom_logo_attachment_id = 456;
		$store                              = new FakeCriticalImagePostMetaStore();
		$store->values[55]                  = 123;

		$selection = $this->registry(
			$runtime,
			new FakeSettingsRepository( array( 'critical_logo_enabled' => true ) ),
			$store
		)->resolve();

		self::assertSame( 123, $selection->primary_attachment_id() );
		self::assertSame( array( 123, 456 ), $selection->critical_attachment_ids() );
		self::assertSame( 123, $selection->preload_attachment_id() );
	}

	/**
	 * Test invalid filtered IDs and URLs are dropped during normalization.
	 *
	 * @return void
	 */
	public function test_invalid_filtered_ids_and_urls_are_dropped(): void {
		$GLOBALS['hwlio_test_filters'] = array(
			'hwlio_critical_image_candidates' => static function ( array $payload ): array {
				$payload['primary_attachment_id']   = 'not-an-id';
				$payload['critical_attachment_ids'] = array( 0, 222, 'x', 222 );
				$payload['critical_urls']           = array( '', 'notaurl', 'https://example.test/logo.png' );
				$payload['preload_attachment_id']   = 'bad';

				return $payload;
			},
		);

		$selection = $this->registry()->resolve();

		self::assertNull( $selection->primary_attachment_id() );
		self::assertSame( array( 222 ), $selection->critical_attachment_ids() );
		self::assertSame( array( 'https://example.test/logo.png' ), $selection->critical_urls() );
		self::assertNull( $selection->preload_attachment_id() );
	}

	/**
	 * Test the final selection filter can override normalized output.
	 *
	 * @return void
	 */
	public function test_final_selection_filter_can_override_normalized_output(): void {
		$GLOBALS['hwlio_test_filters'] = array(
			'hwlio_critical_image_selection' => static function ( array $selection ): array {
				unset( $selection );

				return array(
					'primary_attachment_id'   => 345,
					'critical_attachment_ids' => array( 345, 456, 345 ),
					'critical_urls'           => array( 'https://example.test/hero.jpg' ),
					'preload_attachment_id'   => 456,
				);
			},
		);

		$selection = $this->registry()->resolve();

		self::assertSame( 345, $selection->primary_attachment_id() );
		self::assertSame( array( 345, 456 ), $selection->critical_attachment_ids() );
		self::assertSame( 456, $selection->preload_attachment_id() );
		self::assertTrue( $selection->should_preload_attachment( 456 ) );
		self::assertTrue( $selection->matches_url( 'https://example.test/hero.jpg' ) );
	}

	/**
	 * Test cached selection is recomputed after the current site changes.
	 *
	 * @return void
	 */
	public function test_cached_selection_is_recomputed_after_site_switch(): void {
		$runtime                   = new FakeAttachmentImageRuntime();
		$runtime->post_id          = 55;
		$runtime->post_type        = 'post';
		$store                     = new FakeCriticalImagePostMetaStore();
		$store->values[55]         = 123;
		$site_context              = new FakeSiteContextRuntime();
		$registry                  = $this->registry( $runtime, new FakeSettingsRepository(), $store, $site_context );

		self::assertSame( 123, $registry->resolve()->primary_attachment_id() );

		$site_context->current_site_id = 2;
		$store->values[55]             = 456;

		self::assertSame( 456, $registry->resolve()->primary_attachment_id() );
	}

	/**
	 * Build registry fixture.
	 *
	 * @param FakeAttachmentImageRuntime|null     $runtime Runtime seam.
	 * @param FakeSettingsRepository|null         $settings Settings repository.
	 * @param FakeCriticalImagePostMetaStore|null                  $store Meta store.
	 * @param FakeSiteContextRuntime|null                          $site_context Site context.
	 * @return CriticalImageRegistry
	 */
	private function registry(
		?FakeAttachmentImageRuntime $runtime = null,
		?FakeSettingsRepository $settings = null,
		?FakeCriticalImagePostMetaStore $store = null,
		?FakeSiteContextRuntime $site_context = null
	): CriticalImageRegistry {
		$runtime  = $runtime ?? new FakeAttachmentImageRuntime();
		$settings = $settings ?? new FakeSettingsRepository();
		$store    = $store ?? new FakeCriticalImagePostMetaStore();

		return new CriticalImageRegistry( $runtime, $settings, $store, $site_context );
	}
}
