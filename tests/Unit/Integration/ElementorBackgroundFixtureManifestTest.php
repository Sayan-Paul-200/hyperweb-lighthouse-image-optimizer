<?php
/**
 * Tests for Elementor background-discovery fixtures.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Verifies the background-discovery fixture inventory is complete and documented.
 */
final class ElementorBackgroundFixtureManifestTest extends TestCase {

	/**
	 * Required background-discovery contexts.
	 *
	 * @var string[]
	 */
	private const REQUIRED_CONTEXTS = array(
		'background_classic_desktop',
		'background_classic_responsive',
		'background_overlay_classic',
		'background_url_only',
		'background_custom_css_url',
		'background_unsupported_modes',
		'background_invalid_document',
	);

	/**
	 * Test manifest completeness and fixture existence.
	 *
	 * @return void
	 */
	public function test_background_discovery_manifest_is_complete(): void {
		$manifest = require $this->fixtures_root() . '/background-discovery-manifest.php';

		self::assertIsArray( $manifest );
		self::assertCount( 7, $manifest );

		$contexts = array();

		foreach ( $manifest as $entry ) {
			self::assertIsArray( $entry );

			foreach ( $this->required_fields() as $field => $type ) {
				self::assertArrayHasKey( $field, $entry );
				$this->assert_field_type( $field, $type, $entry[ $field ] );
			}

			$contexts[]   = $entry['context'];
			$fixture_path = $this->fixtures_root() . '/BackgroundDiscovery/' . $entry['fixture_file'];

			self::assertFileExists( $fixture_path );

			$fixture = require $fixture_path;

			if ( 'invalid_document' === $entry['document_shape'] ) {
				self::assertIsString( $fixture );
				self::assertNotSame( '', trim( $fixture ) );
				continue;
			}

			self::assertIsArray( $fixture );
			self::assertNotSame( array(), $fixture );
		}

		sort( $contexts );
		$required = self::REQUIRED_CONTEXTS;
		sort( $required );

		self::assertSame( $required, $contexts );
	}

	/**
	 * Test the audit document records background discovery boundaries and deferred 10.4 work.
	 *
	 * @return void
	 */
	public function test_audit_document_records_background_discovery_policy(): void {
		$audit_path = dirname( __DIR__, 3 ) . '/docs/elementor-compatibility-audit.md';

		self::assertFileExists( $audit_path );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading local audit document during tests.
		$audit = file_get_contents( $audit_path );

		self::assertIsString( $audit );
		self::assertStringContainsString( 'Background Discovery Baseline', $audit );
		self::assertStringContainsString( 'supported structured control keys', $audit );
		self::assertStringContainsString( 'Background Delivery Strategy', $audit );
		self::assertStringContainsString( 'plugin-owned companion stylesheet strategy', $audit );
	}

	/**
	 * Get required manifest field definitions.
	 *
	 * @return array<string,string>
	 */
	private function required_fields(): array {
		return array(
			'context'                    => 'string',
			'label'                      => 'string',
			'fixture_file'               => 'string',
			'document_shape'             => 'string',
			'discovery_intent'           => 'string',
			'desktop_tablet_mobile_case' => 'bool',
			'notes'                      => 'array',
		);
	}

	/**
	 * Assert one manifest field type.
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
