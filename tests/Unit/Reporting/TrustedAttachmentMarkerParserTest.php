<?php
/**
 * Tests for the trusted attachment marker parser.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Reporting;

use HyperWeb\LighthouseImageOptimizer\Reporting\TrustedAttachmentMarkerParser;
use PHPUnit\Framework\TestCase;

/**
 * Verifies trusted attachment IDs are read conservatively from IMG markup.
 */
final class TrustedAttachmentMarkerParserTest extends TestCase {

	/**
	 * Test data-id wins as a trusted marker.
	 *
	 * @return void
	 */
	public function test_data_id_marker_is_read(): void {
		$parser = new TrustedAttachmentMarkerParser();

		self::assertSame( 123, $parser->parse_attachment_id( '<img data-id="123" src="https://example.test/uploads/hero.jpg">' ) );
	}

	/**
	 * Test wp-image class markers are supported.
	 *
	 * @return void
	 */
	public function test_wp_image_class_marker_is_read(): void {
		$parser = new TrustedAttachmentMarkerParser();

		self::assertSame( 456, $parser->parse_attachment_id( '<img class="alignnone wp-image-456 size-full" src="https://example.test/uploads/hero.jpg">' ) );
	}

	/**
	 * Test unsupported fragments return zero.
	 *
	 * @return void
	 */
	public function test_unknown_markers_return_zero(): void {
		$parser = new TrustedAttachmentMarkerParser();

		self::assertSame( 0, $parser->parse_attachment_id( '<img src="https://example.test/uploads/hero.jpg">' ) );
	}
}
