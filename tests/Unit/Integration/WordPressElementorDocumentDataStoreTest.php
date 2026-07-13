<?php
/**
 * Tests for the WordPress-backed Elementor document-data store.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Integration;

require_once dirname( __DIR__ ) . '/Delivery/DeliveryTestWordPressShim.php';

use HyperWeb\LighthouseImageOptimizer\Integration\ElementorDocumentData;
use HyperWeb\LighthouseImageOptimizer\Integration\WordPressElementorDocumentDataStore;
use PHPUnit\Framework\TestCase;

/**
 * Verifies structured Elementor document data is decoded read-only and safely.
 */
final class WordPressElementorDocumentDataStoreTest extends TestCase {

	/**
	 * Reset test globals.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		unset( $GLOBALS['hwlio_test_post_meta'] );
	}

	/**
	 * Test valid JSON document data is decoded into normalized element arrays.
	 *
	 * @return void
	 */
	public function test_valid_json_document_data_is_decoded(): void {
		$GLOBALS['hwlio_test_post_meta'][77][ WordPressElementorDocumentDataStore::META_KEY ] = wp_json_encode(
			array(
				array(
					'id'         => 'hero-section',
					'elType'     => 'section',
					'widgetType' => '',
					'settings'   => array(
						'background_background' => 'classic',
					),
					'elements'   => array(
						array(
							'id'         => 'hero-widget',
							'elType'     => 'widget',
							'widgetType' => 'image',
							'settings'   => array(),
							'elements'   => array(),
						),
					),
				),
			)
		);

		$result = ( new WordPressElementorDocumentDataStore() )->read_document( 77 );

		self::assertTrue( $result->is_valid() );
		self::assertSame( ElementorDocumentData::STATE_VALID, $result->state() );
		self::assertCount( 1, $result->elements() );
		self::assertSame( 'hero-section', $result->elements()[0]['id'] );
		self::assertCount( 1, $result->elements()[0]['elements'] );
	}

	/**
	 * Test missing document data returns a safe missing result.
	 *
	 * @return void
	 */
	public function test_missing_document_data_returns_missing_result(): void {
		$result = ( new WordPressElementorDocumentDataStore() )->read_document( 99 );

		self::assertTrue( $result->is_missing() );
		self::assertSame( array(), $result->elements() );
	}

	/**
	 * Test malformed JSON document data returns an invalid result.
	 *
	 * @return void
	 */
	public function test_malformed_json_document_data_returns_invalid_result(): void {
		$GLOBALS['hwlio_test_post_meta'][88][ WordPressElementorDocumentDataStore::META_KEY ] = '{"bad":';

		$result = ( new WordPressElementorDocumentDataStore() )->read_document( 88 );

		self::assertTrue( $result->is_invalid() );
		self::assertSame( array(), $result->elements() );
	}
}
