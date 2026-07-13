<?php
/**
 * Tests for Elementor background discovery.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Integration;

use HyperWeb\LighthouseImageOptimizer\Integration\ElementorBackgroundDiscovery;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorDocumentData;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorUnsupportedBackgroundCase;
use PHPUnit\Framework\TestCase;

/**
 * Verifies structured Elementor background settings are discovered conservatively.
 */
final class ElementorBackgroundDiscoveryTest extends TestCase {

	/**
	 * Test classic desktop attachment-backed background is discovered.
	 *
	 * @return void
	 */
	public function test_classic_desktop_attachment_background_is_discovered(): void {
		$store           = new FakeElementorDocumentDataStore();
		$store->document = ElementorDocumentData::valid( $this->fixture_elements( 'background-classic-desktop.php' ) );

		$result = ( new ElementorBackgroundDiscovery( $store ) )->discover( 501 );

		self::assertSame( 501, $store->last_document_id );
		self::assertCount( 1, $result->supported_sources() );
		self::assertCount( 0, $result->unsupported_cases() );
		self::assertSame(
			array(
				'document_id'   => 501,
				'element_id'    => 'hero-section',
				'element_type'  => 'section',
				'widget_type'   => null,
				'setting_group' => 'background',
				'device'        => 'desktop',
				'attachment_id' => 901,
				'url'           => 'https://example.test/wp-content/uploads/2026/07/hero-desktop.jpg',
				'setting_key'   => 'background_image',
			),
			$result->supported_sources()[0]->to_array()
		);
	}

	/**
	 * Test explicit desktop, tablet, and mobile sources are recorded separately.
	 *
	 * @return void
	 */
	public function test_tablet_and_mobile_background_sources_are_recorded_separately(): void {
		$store           = new FakeElementorDocumentDataStore();
		$store->document = ElementorDocumentData::valid( $this->fixture_elements( 'background-classic-responsive.php' ) );

		$result  = ( new ElementorBackgroundDiscovery( $store ) )->discover( 502 );
		$sources = array_map(
			static function ( $source ): array {
				return $source->to_array();
			},
			$result->supported_sources()
		);

		self::assertCount( 3, $sources );
		self::assertSame( array( 'desktop', 'tablet', 'mobile' ), array_column( $sources, 'device' ) );
		self::assertSame( 1, $result->summary()['device_counts']['desktop'] );
		self::assertSame( 1, $result->summary()['device_counts']['tablet'] );
		self::assertSame( 1, $result->summary()['device_counts']['mobile'] );
	}

	/**
	 * Test overlay background image controls are discovered.
	 *
	 * @return void
	 */
	public function test_overlay_background_controls_are_discovered(): void {
		$store           = new FakeElementorDocumentDataStore();
		$store->document = ElementorDocumentData::valid( $this->fixture_elements( 'background-overlay-classic.php' ) );

		$result = ( new ElementorBackgroundDiscovery( $store ) )->discover( 503 );

		self::assertCount( 1, $result->supported_sources() );
		self::assertSame( 'background_overlay', $result->supported_sources()[0]->to_array()['setting_group'] );
	}

	/**
	 * Test URL-only background values are recorded as unsupported, not supported.
	 *
	 * @return void
	 */
	public function test_url_only_background_values_are_unsupported(): void {
		$store           = new FakeElementorDocumentDataStore();
		$store->document = ElementorDocumentData::valid( $this->fixture_elements( 'background-url-only.php' ) );

		$result = ( new ElementorBackgroundDiscovery( $store ) )->discover( 504 );

		self::assertCount( 0, $result->supported_sources() );
		self::assertCount( 1, $result->unsupported_cases() );
		self::assertSame( ElementorUnsupportedBackgroundCase::CODE_UNSUPPORTED_BACKGROUND_VALUE, $result->unsupported_cases()[0]->code() );
		self::assertSame(
			'https://example.test/wp-content/uploads/2026/07/url-only-background.jpg',
			$result->unsupported_cases()[0]->to_array()['value_hint']
		);
	}

	/**
	 * Test custom CSS url() values are recorded conservatively as unsupported.
	 *
	 * @return void
	 */
	public function test_custom_css_url_cases_are_recorded_as_unsupported(): void {
		$store           = new FakeElementorDocumentDataStore();
		$store->document = ElementorDocumentData::valid( $this->fixture_elements( 'background-custom-css-url.php' ) );

		$result = ( new ElementorBackgroundDiscovery( $store ) )->discover( 505 );

		self::assertCount( 0, $result->supported_sources() );
		self::assertCount( 1, $result->unsupported_cases() );
		self::assertSame( ElementorUnsupportedBackgroundCase::CODE_UNSUPPORTED_CSS_URL, $result->unsupported_cases()[0]->code() );
		self::assertSame( 'custom_css', $result->unsupported_cases()[0]->to_array()['setting_group'] );
	}

	/**
	 * Test unsupported background modes are reported conservatively.
	 *
	 * @return void
	 */
	public function test_gradient_video_and_slideshow_modes_are_unsupported(): void {
		$store           = new FakeElementorDocumentDataStore();
		$store->document = ElementorDocumentData::valid( $this->fixture_elements( 'background-unsupported-modes.php' ) );

		$result = ( new ElementorBackgroundDiscovery( $store ) )->discover( 506 );

		self::assertCount( 0, $result->supported_sources() );
		self::assertCount( 2, $result->unsupported_cases() );
		self::assertSame(
			array(
				ElementorUnsupportedBackgroundCase::CODE_UNSUPPORTED_BACKGROUND_MODE,
				ElementorUnsupportedBackgroundCase::CODE_UNSUPPORTED_BACKGROUND_MODE,
			),
			array_map(
				static function ( ElementorUnsupportedBackgroundCase $unsupported_case ): string {
					return $unsupported_case->code();
				},
				$result->unsupported_cases()
			)
		);
	}

	/**
	 * Test invalid document data returns a stable invalid-document case.
	 *
	 * @return void
	 */
	public function test_invalid_document_data_returns_invalid_document_case(): void {
		$store           = new FakeElementorDocumentDataStore();
		$store->document = ElementorDocumentData::invalid();

		$result = ( new ElementorBackgroundDiscovery( $store ) )->discover( 507 );

		self::assertCount( 0, $result->supported_sources() );
		self::assertCount( 1, $result->unsupported_cases() );
		self::assertSame( ElementorUnsupportedBackgroundCase::CODE_INVALID_DOCUMENT_DATA, $result->unsupported_cases()[0]->code() );
	}

	/**
	 * Test missing document data returns a safe empty result.
	 *
	 * @return void
	 */
	public function test_missing_document_data_returns_empty_result(): void {
		$store           = new FakeElementorDocumentDataStore();
		$store->document = ElementorDocumentData::missing();

		$result = ( new ElementorBackgroundDiscovery( $store ) )->discover( 508 );

		self::assertSame(
			array(
				'document_id'            => 508,
				'supported_source_count' => 0,
				'unsupported_case_count' => 0,
				'device_counts'          => array(
					'desktop' => 0,
					'tablet'  => 0,
					'mobile'  => 0,
				),
				'unsupported_codes'      => array(),
			),
			$result->summary()
		);
	}

	/**
	 * Load one structured background fixture.
	 *
	 * @param string $file Fixture filename.
	 * @return array<int,array<string,mixed>>
	 */
	private function fixture_elements( string $file ): array {
		$elements = require dirname( __DIR__, 2 ) . '/Fixtures/Elementor/BackgroundDiscovery/' . $file;

		self::assertIsArray( $elements );

		return $elements;
	}
}
