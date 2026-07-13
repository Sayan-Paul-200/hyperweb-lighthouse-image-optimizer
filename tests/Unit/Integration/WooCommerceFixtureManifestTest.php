<?php
/**
 * Tests for WooCommerce compatibility fixture manifest completeness.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Verifies the WooCommerce baseline fixture inventory is complete and loadable.
 */
final class WooCommerceFixtureManifestTest extends TestCase {

	/**
	 * Required context IDs.
	 *
	 * @var string[]
	 */
	private const REQUIRED_CONTEXTS = array(
		'single_product_primary',
		'single_product_gallery_secondary',
		'cart_item_thumbnail',
		'checkout_review_thumbnail',
		'product_loop_thumbnail',
		'related_product_thumbnail',
		'upsell_product_thumbnail',
		'single_product_variation_image',
	);

	/**
	 * Test the baseline manifest includes all required contexts and fields.
	 *
	 * @return void
	 */
	public function test_manifest_is_complete_and_references_non_empty_fixture_files(): void {
		$manifest = require $this->fixtures_root() . '/baseline-manifest.php';

		self::assertIsArray( $manifest );
		self::assertCount( 8, $manifest );

		$contexts = array();

		foreach ( $manifest as $entry ) {
			self::assertIsArray( $entry );

			foreach ( $this->required_fields() as $field => $type ) {
				self::assertArrayHasKey( $field, $entry );
				$this->assert_field_type( $field, $type, $entry[ $field ] );
			}

			$contexts[] = $entry['context'];

			$fixture_path = $this->fixtures_root() . '/' . $entry['fixture_file'];

			self::assertFileExists( $fixture_path );

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading local fixture files during tests.
			$fixture_html = file_get_contents( $fixture_path );

			self::assertIsString( $fixture_html );
			self::assertNotSame( '', trim( $fixture_html ) );
			self::assertMatchesRegularExpression( '/<img\b/i', $fixture_html );
		}

		sort( $contexts );
		$required = self::REQUIRED_CONTEXTS;
		sort( $required );

		self::assertSame( $required, $contexts );
	}

	/**
	 * Get required manifest field definitions.
	 *
	 * @return array<string,string>
	 */
	private function required_fields(): array {
		return array(
			'context'                       => 'string',
			'label'                         => 'string',
			'fixture_file'                  => 'string',
			'surface'                       => 'string',
			'attachment_expected'           => 'bool',
			'critical_role'                 => 'string',
			'hook_or_template_candidates'   => 'array',
			'must_preserve_attributes'      => 'array',
			'must_preserve_classes'         => 'array',
			'must_preserve_data_attributes' => 'array',
			'notes'                         => 'array',
		);
	}

	/**
	 * Assert one manifest field has the expected type.
	 *
	 * @param string $field Field name.
	 * @param string $type Expected type.
	 * @param mixed  $value Field value.
	 * @return void
	 */
	private function assert_field_type( string $field, string $type, $value ): void {
		switch ( $type ) {
			case 'string':
				self::assertIsString( $value, $field );
				self::assertNotSame( '', trim( $value ), $field );
				break;

			case 'bool':
				self::assertIsBool( $value, $field );
				break;

			case 'array':
				self::assertIsArray( $value, $field );
				break;
		}
	}

	/**
	 * Get the WooCommerce fixtures root.
	 *
	 * @return string
	 */
	private function fixtures_root(): string {
		return dirname( __DIR__, 3 ) . '/tests/Fixtures/WooCommerce';
	}
}
