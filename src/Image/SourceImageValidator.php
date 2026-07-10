<?php
/**
 * Source image validator.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Image;

/**
 * Validates source MIME, dimensions, and animation state without converting images.
 */
final class SourceImageValidator {

	/**
	 * File probe.
	 *
	 * @var ImageFileProbeInterface
	 */
	private $files;

	/**
	 * Animation detector.
	 *
	 * @var AnimationDetectorInterface
	 */
	private $animation_detector;

	/**
	 * MIME policy.
	 *
	 * @var SourceMimePolicy
	 */
	private $mime_policy;

	/**
	 * Create WordPress-backed validator.
	 *
	 * @return self
	 */
	public static function for_wordpress(): self {
		return new self(
			new WordPressImageFileProbe(),
			new FileAnimationDetector(),
			new SourceMimePolicy()
		);
	}

	/**
	 * Create validator.
	 *
	 * @param ImageFileProbeInterface    $files File probe.
	 * @param AnimationDetectorInterface $animation_detector Animation detector.
	 * @param SourceMimePolicy|null      $mime_policy MIME policy.
	 */
	public function __construct(
		ImageFileProbeInterface $files,
		AnimationDetectorInterface $animation_detector,
		?SourceMimePolicy $mime_policy = null
	) {
		$this->files              = $files;
		$this->animation_detector = $animation_detector;
		$this->mime_policy        = $mime_policy ?? new SourceMimePolicy();
	}

	/**
	 * Validate a collection of source images.
	 *
	 * @param SourceImageCollection $collection Source image collection.
	 * @return SourceImageValidationCollection
	 */
	public function validate_collection( SourceImageCollection $collection ): SourceImageValidationCollection {
		$results = array();

		foreach ( $collection->sources() as $source ) {
			$results[] = $this->validate( $source );
		}

		return new SourceImageValidationCollection( $results );
	}

	/**
	 * Validate one source image.
	 *
	 * @param SourceImage $source Source image.
	 * @return SourceImageValidationResult
	 */
	public function validate( SourceImage $source ): SourceImageValidationResult {
		$collected_mime = $this->mime_policy->normalize_mime( $source->mime_type() );
		$animation      = AnimationStatus::not_applicable( '' );

		if ( ! $this->files->exists( $source->absolute_path() ) ) {
			return SourceImageValidationResult::invalid(
				$source,
				SourceImageValidationResult::CODE_SOURCE_MISSING,
				'The source file no longer exists.',
				null,
				$collected_mime,
				$animation
			);
		}

		if ( ! $this->files->is_file( $source->absolute_path() ) || ! $this->files->is_readable( $source->absolute_path() ) ) {
			return SourceImageValidationResult::invalid(
				$source,
				SourceImageValidationResult::CODE_SOURCE_UNREADABLE,
				'The source file is not readable.',
				null,
				$collected_mime,
				$animation
			);
		}

		$detected_mime = $this->mime_policy->normalize_mime( $this->files->mime_type( $source->absolute_path() ) );

		if ( null === $detected_mime ) {
			return SourceImageValidationResult::invalid(
				$source,
				SourceImageValidationResult::CODE_SOURCE_INVALID_MIME,
				'The source MIME type could not be detected from file contents.',
				null,
				$collected_mime,
				$animation
			);
		}

		if ( null !== $collected_mime && $collected_mime !== $detected_mime ) {
			return SourceImageValidationResult::invalid(
				$source,
				SourceImageValidationResult::CODE_SOURCE_INVALID_MIME,
				'The source MIME type changed after collection.',
				$detected_mime,
				$collected_mime,
				$animation,
				array(
					'expected_mime' => $collected_mime,
					'actual_mime'   => $detected_mime,
				)
			);
		}

		$animation = $this->mime_policy->requires_animation_detection( $detected_mime )
			? $this->animation_detector->detect( $source->absolute_path(), $detected_mime )
			: AnimationStatus::not_applicable( $detected_mime );

		if ( $animation->is_animated() ) {
			return SourceImageValidationResult::skipped(
				$source,
				SourceImageValidationResult::CODE_SKIPPED_ANIMATED_IMAGE,
				'Animated source images are skipped to avoid flattening animation.',
				$detected_mime,
				$collected_mime,
				$animation
			);
		}

		if ( $animation->is_unknown() ) {
			return SourceImageValidationResult::invalid(
				$source,
				SourceImageValidationResult::CODE_SOURCE_ANIMATION_UNKNOWN,
				'The source animation status could not be determined safely.',
				$detected_mime,
				$collected_mime,
				$animation
			);
		}

		if ( ! $this->mime_policy->is_supported_source_mime( $detected_mime ) ) {
			return SourceImageValidationResult::skipped(
				$source,
				SourceImageValidationResult::CODE_SKIPPED_UNSUPPORTED_MIME,
				'The source MIME type is not supported for raster conversion.',
				$detected_mime,
				$collected_mime,
				$animation
			);
		}

		if ( null === $this->files->dimensions( $source->absolute_path() ) ) {
			return SourceImageValidationResult::invalid(
				$source,
				SourceImageValidationResult::CODE_SOURCE_CORRUPT,
				'The source image dimensions could not be read from file contents.',
				$detected_mime,
				$collected_mime,
				$animation
			);
		}

		return SourceImageValidationResult::eligible(
			$source,
			$detected_mime,
			$collected_mime,
			$animation,
			$this->mime_policy->target_formats_for_source_mime( $detected_mime )
		);
	}
}
