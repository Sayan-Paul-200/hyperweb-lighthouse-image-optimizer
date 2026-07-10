<?php
/**
 * Tests for source image validation.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image;

use HyperWeb\LighthouseImageOptimizer\Image\AnimationStatus;
use HyperWeb\LighthouseImageOptimizer\Image\SourceImage;
use HyperWeb\LighthouseImageOptimizer\Image\SourceImageCollection;
use HyperWeb\LighthouseImageOptimizer\Image\SourceImageValidationResult;
use HyperWeb\LighthouseImageOptimizer\Image\SourceImageValidator;
use HyperWeb\LighthouseImageOptimizer\Image\SourceMimePolicy;
use PHPUnit\Framework\TestCase;

/**
 * Verifies MIME and animation validation behavior.
 */
final class SourceImageValidatorTest extends TestCase {

	private const UPLOADS = 'C:/site/wp-content/uploads';
	private const SOURCE  = self::UPLOADS . '/2026/07/hero.jpg';

	/**
	 * Test JPEG and PNG source eligibility.
	 *
	 * @return void
	 */
	public function test_jpeg_and_png_sources_are_eligible_for_future_webp_and_avif_targets(): void {
		$jpeg = $this->validate_source( SourceMimePolicy::MIME_JPEG );
		$png  = $this->validate_source( SourceMimePolicy::MIME_PNG );

		self::assertSame( SourceImageValidationResult::STATUS_ELIGIBLE, $jpeg->status() );
		self::assertSame( array( SourceMimePolicy::TARGET_WEBP, SourceMimePolicy::TARGET_AVIF ), $jpeg->target_formats() );
		self::assertSame( SourceImageValidationResult::STATUS_ELIGIBLE, $png->status() );
		self::assertSame( array( SourceMimePolicy::TARGET_WEBP, SourceMimePolicy::TARGET_AVIF ), $png->target_formats() );
	}

	/**
	 * Test non-animated WebP eligibility.
	 *
	 * @return void
	 */
	public function test_non_animated_webp_is_eligible_only_for_future_avif_target(): void {
		$animation = AnimationStatus::not_animated( SourceMimePolicy::MIME_WEBP );
		$result    = $this->validate_source( SourceMimePolicy::MIME_WEBP, SourceMimePolicy::MIME_WEBP, $animation );

		self::assertSame( SourceImageValidationResult::STATUS_ELIGIBLE, $result->status() );
		self::assertSame( array( SourceMimePolicy::TARGET_AVIF ), $result->target_formats() );
	}

	/**
	 * Test missing detected MIME rejects renamed non-images.
	 *
	 * @return void
	 */
	public function test_renamed_non_image_is_rejected_when_mime_cannot_be_detected(): void {
		$result = $this->validate_source( null, null );

		self::assertSame( SourceImageValidationResult::STATUS_INVALID, $result->status() );
		self::assertSame( SourceImageValidationResult::CODE_SOURCE_INVALID_MIME, $result->code() );
	}

	/**
	 * Test SVG and AVIF sources are unsupported.
	 *
	 * @return void
	 */
	public function test_svg_and_avif_sources_are_rejected_before_conversion(): void {
		$svg  = $this->validate_source( SourceMimePolicy::MIME_SVG );
		$avif = $this->validate_source( SourceMimePolicy::MIME_AVIF );

		self::assertSame( SourceImageValidationResult::STATUS_SKIPPED, $svg->status() );
		self::assertSame( SourceImageValidationResult::CODE_SKIPPED_UNSUPPORTED_MIME, $svg->code() );
		self::assertSame( SourceImageValidationResult::STATUS_SKIPPED, $avif->status() );
		self::assertSame( SourceImageValidationResult::CODE_SKIPPED_UNSUPPORTED_MIME, $avif->code() );
	}

	/**
	 * Test animated GIF sources are skipped clearly.
	 *
	 * @return void
	 */
	public function test_animated_gif_is_skipped_as_animated(): void {
		$result = $this->validate_source(
			SourceMimePolicy::MIME_GIF,
			SourceMimePolicy::MIME_GIF,
			AnimationStatus::animated( SourceMimePolicy::MIME_GIF, 'animated_gif' )
		);

		self::assertSame( SourceImageValidationResult::STATUS_SKIPPED, $result->status() );
		self::assertSame( SourceImageValidationResult::CODE_SKIPPED_ANIMATED_IMAGE, $result->code() );
	}

	/**
	 * Test non-animated GIF is still unsupported.
	 *
	 * @return void
	 */
	public function test_non_animated_gif_is_rejected_as_unsupported_source_mime(): void {
		$result = $this->validate_source(
			SourceMimePolicy::MIME_GIF,
			SourceMimePolicy::MIME_GIF,
			AnimationStatus::not_animated( SourceMimePolicy::MIME_GIF )
		);

		self::assertSame( SourceImageValidationResult::STATUS_SKIPPED, $result->status() );
		self::assertSame( SourceImageValidationResult::CODE_SKIPPED_UNSUPPORTED_MIME, $result->code() );
	}

	/**
	 * Test animated WebP sources are skipped.
	 *
	 * @return void
	 */
	public function test_animated_webp_is_skipped(): void {
		$result = $this->validate_source(
			SourceMimePolicy::MIME_WEBP,
			SourceMimePolicy::MIME_WEBP,
			AnimationStatus::animated( SourceMimePolicy::MIME_WEBP, 'animated_webp' )
		);

		self::assertSame( SourceImageValidationResult::STATUS_SKIPPED, $result->status() );
		self::assertSame( SourceImageValidationResult::CODE_SKIPPED_ANIMATED_IMAGE, $result->code() );
	}

	/**
	 * Test unknown animation status is ineligible.
	 *
	 * @return void
	 */
	public function test_unknown_webp_animation_status_is_conservatively_invalid(): void {
		$result = $this->validate_source(
			SourceMimePolicy::MIME_WEBP,
			SourceMimePolicy::MIME_WEBP,
			AnimationStatus::unknown( SourceMimePolicy::MIME_WEBP, 'truncated_webp_chunk' )
		);

		self::assertSame( SourceImageValidationResult::STATUS_INVALID, $result->status() );
		self::assertSame( SourceImageValidationResult::CODE_SOURCE_ANIMATION_UNKNOWN, $result->code() );
	}

	/**
	 * Test corrupt supported image fails validation.
	 *
	 * @return void
	 */
	public function test_supported_image_with_unreadable_dimensions_is_corrupt(): void {
		$result = $this->validate_source( SourceMimePolicy::MIME_JPEG, SourceMimePolicy::MIME_JPEG, null, null, null );

		self::assertSame( SourceImageValidationResult::STATUS_INVALID, $result->status() );
		self::assertSame( SourceImageValidationResult::CODE_SOURCE_CORRUPT, $result->code() );
	}

	/**
	 * Test MIME mismatch after collection fails validation.
	 *
	 * @return void
	 */
	public function test_mime_mismatch_between_collection_and_validation_is_invalid(): void {
		$result = $this->validate_source( SourceMimePolicy::MIME_PNG, SourceMimePolicy::MIME_JPEG );

		self::assertSame( SourceImageValidationResult::STATUS_INVALID, $result->status() );
		self::assertSame( SourceImageValidationResult::CODE_SOURCE_INVALID_MIME, $result->code() );
		self::assertSame( SourceMimePolicy::MIME_PNG, $result->detected_mime() );
		self::assertSame( SourceMimePolicy::MIME_JPEG, $result->collected_mime() );
	}

	/**
	 * Test validation collection partitions results.
	 *
	 * @return void
	 */
	public function test_validates_source_collection(): void {
		$probe = $this->probe();
		$probe->add_file( self::SOURCE, 1000, 100, SourceMimePolicy::MIME_JPEG, 100, 100 );

		$collection = ( new SourceImageValidator( $probe, new FakeAnimationDetector() ) )
			->validate_collection( new SourceImageCollection( array( $this->source() ) ) );

		self::assertCount( 1, $collection->results() );
		self::assertCount( 1, $collection->eligible() );
		self::assertCount( 0, $collection->skipped() );
		self::assertCount( 0, $collection->invalid() );
	}

	/**
	 * Test validation serialization hides absolute paths.
	 *
	 * @return void
	 */
	public function test_validation_result_serialization_omits_absolute_paths(): void {
		$result     = $this->validate_source( SourceMimePolicy::MIME_PNG, SourceMimePolicy::MIME_JPEG );
		$serialized = $result->to_array();

		self::assertArrayNotHasKey( 'absolute_path', $serialized['source'] );
		self::assertStringNotContainsString( self::UPLOADS, $serialized['source']['relative_path'] );
		self::assertStringNotContainsString( self::UPLOADS, $serialized['message'] );
	}

	/**
	 * Validate one source.
	 *
	 * @param string|null          $detected_mime Detected MIME.
	 * @param string|null          $collected_mime Collected MIME.
	 * @param AnimationStatus|null $animation_status Animation status.
	 * @param int|null             $width Width.
	 * @param int|null             $height Height.
	 * @return SourceImageValidationResult
	 */
	private function validate_source(
		?string $detected_mime,
		?string $collected_mime = null,
		?AnimationStatus $animation_status = null,
		?int $width = 100,
		?int $height = 100
	): SourceImageValidationResult {
		$collected_mime = null === $collected_mime ? $detected_mime : $collected_mime;
		$probe          = $this->probe();
		$probe->add_file( self::SOURCE, 1000, 100, $detected_mime, $width, $height );

		$animation_detector = new FakeAnimationDetector();

		if ( null !== $animation_status ) {
			$animation_detector->set_status( self::SOURCE, $animation_status );
		}

		return ( new SourceImageValidator( $probe, $animation_detector ) )
			->validate( $this->source( $collected_mime ) );
	}

	/**
	 * Build source image.
	 *
	 * @param string|null $mime_type MIME type.
	 * @return SourceImage
	 */
	private function source( ?string $mime_type = SourceMimePolicy::MIME_JPEG ): SourceImage {
		return new SourceImage(
			123,
			'full',
			SourceImage::ROLE_FULL,
			'2026/07/hero.jpg',
			self::SOURCE,
			$mime_type,
			100,
			100,
			1000,
			100
		);
	}

	/**
	 * Build fake probe.
	 *
	 * @return FakeImageFileProbe
	 */
	private function probe(): FakeImageFileProbe {
		return new FakeImageFileProbe( array( self::UPLOADS ) );
	}
}
