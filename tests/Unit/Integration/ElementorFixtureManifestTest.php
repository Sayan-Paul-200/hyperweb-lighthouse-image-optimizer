<?php
/**
 * Tests for Elementor compatibility fixture manifest completeness.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Verifies the Elementor baseline fixture inventory is complete and loadable.
 */
final class ElementorFixtureManifestTest extends TestCase {

	/**
	 * Required context IDs.
	 *
	 * @var string[]
	 */
	private const REQUIRED_CONTEXTS = array(
		'image_widget_attachment',
		'image_box_widget_attachment',
		'cta_widget_attachment',
		'image_widget_attachment_full_small_slot',
		'image_widget_attachment_full_near_full',
		'image_widget_attachment_full_uncertain',
		'gallery_widget_attachment',
		'carousel_widget_attachment',
	);

	/**
	 * Test the baseline manifest includes all required contexts and non-empty fixture files.
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
	 * Test the audit document records the same supported and fail-open contexts.
	 *
	 * @return void
	 */
	public function test_audit_document_records_supported_and_fail_open_contexts(): void {
		$audit_path = dirname( __DIR__, 3 ) . '/docs/elementor-compatibility-audit.md';

		self::assertFileExists( $audit_path );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading local audit document during tests.
		$audit = file_get_contents( $audit_path );

		self::assertIsString( $audit );

		foreach ( self::REQUIRED_CONTEXTS as $context ) {
			self::assertStringContainsString( $context, $audit );
		}

		self::assertStringContainsString( '10.1 delivery intent: supported', $audit );
		self::assertStringContainsString( '10.1 delivery intent: fail_open', $audit );
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
			'widget_type'                   => 'string',
			'fixture_file'                  => 'string',
			'attachment_expected'           => 'bool',
			'delivery_intent'               => 'string',
			'must_preserve_classes'         => 'array',
			'must_preserve_data_attributes' => 'array',
			'editor_preview_fail_open'      => 'bool',
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
	 * Get the Elementor fixtures root.
	 *
	 * @return string
	 */
	private function fixtures_root(): string {
		return dirname( __DIR__, 3 ) . '/tests/Fixtures/Elementor';
	}
}
