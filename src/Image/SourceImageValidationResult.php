<?php
/**
 * Source image validation result.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Image;

/**
 * Carries the MIME and animation validation result for one source image.
 */
final class SourceImageValidationResult {

	public const STATUS_ELIGIBLE = 'eligible';
	public const STATUS_SKIPPED  = 'skipped';
	public const STATUS_INVALID  = 'invalid';

	public const CODE_ELIGIBLE                 = 'eligible';
	public const CODE_SKIPPED_UNSUPPORTED_MIME = 'skipped_unsupported_source_mime';
	public const CODE_SKIPPED_ANIMATED_IMAGE   = 'skipped_animated_image';
	public const CODE_SOURCE_INVALID_MIME      = 'source_invalid_mime';
	public const CODE_SOURCE_CORRUPT           = 'source_corrupt';
	public const CODE_SOURCE_ANIMATION_UNKNOWN = 'source_animation_unknown';
	public const CODE_SOURCE_MISSING           = 'source_missing';
	public const CODE_SOURCE_UNREADABLE        = 'source_unreadable';

	/**
	 * Source image.
	 *
	 * @var SourceImage
	 */
	private $source;

	/**
	 * Status.
	 *
	 * @var string
	 */
	private $status;

	/**
	 * Code.
	 *
	 * @var string
	 */
	private $code;

	/**
	 * Message.
	 *
	 * @var string
	 */
	private $message;

	/**
	 * Current detected MIME.
	 *
	 * @var string|null
	 */
	private $detected_mime;

	/**
	 * Collected MIME.
	 *
	 * @var string|null
	 */
	private $collected_mime;

	/**
	 * Animation status.
	 *
	 * @var AnimationStatus
	 */
	private $animation_status;

	/**
	 * Future target formats.
	 *
	 * @var string[]
	 */
	private $target_formats;

	/**
	 * Details.
	 *
	 * @var array<mixed>
	 */
	private $details;

	/**
	 * Create result.
	 *
	 * @param SourceImage     $source Source image.
	 * @param string          $status Status.
	 * @param string          $code Code.
	 * @param string          $message Message.
	 * @param string|null     $detected_mime Detected MIME.
	 * @param string|null     $collected_mime Collected MIME.
	 * @param AnimationStatus $animation_status Animation status.
	 * @param string[]        $target_formats Target formats.
	 * @param array<mixed>    $details Public-safe details.
	 */
	private function __construct(
		SourceImage $source,
		string $status,
		string $code,
		string $message,
		?string $detected_mime,
		?string $collected_mime,
		AnimationStatus $animation_status,
		array $target_formats = array(),
		array $details = array()
	) {
		$this->source           = $source;
		$this->status           = in_array( $status, self::statuses(), true ) ? $status : self::STATUS_INVALID;
		$this->code             = $this->normalize_code( $code );
		$this->message          = '' === trim( $message ) ? 'Source image validation result.' : $this->redact_absolute_paths( trim( $message ) );
		$this->detected_mime    = $this->normalize_nullable_string( $detected_mime );
		$this->collected_mime   = $this->normalize_nullable_string( $collected_mime );
		$this->animation_status = $animation_status;
		$this->target_formats   = $this->sanitize_target_formats( $target_formats );
		$this->details          = $this->sanitize_details( $details );
	}

	/**
	 * Build an eligible result.
	 *
	 * @param SourceImage     $source Source image.
	 * @param string          $detected_mime Detected MIME.
	 * @param string|null     $collected_mime Collected MIME.
	 * @param AnimationStatus $animation_status Animation status.
	 * @param string[]        $target_formats Target formats.
	 * @return self
	 */
	public static function eligible(
		SourceImage $source,
		string $detected_mime,
		?string $collected_mime,
		AnimationStatus $animation_status,
		array $target_formats
	): self {
		return new self(
			$source,
			self::STATUS_ELIGIBLE,
			self::CODE_ELIGIBLE,
			'The source image is eligible for later conversion policy checks.',
			$detected_mime,
			$collected_mime,
			$animation_status,
			$target_formats
		);
	}

	/**
	 * Build a skipped result.
	 *
	 * @param SourceImage     $source Source image.
	 * @param string          $code Code.
	 * @param string          $message Message.
	 * @param string|null     $detected_mime Detected MIME.
	 * @param string|null     $collected_mime Collected MIME.
	 * @param AnimationStatus $animation_status Animation status.
	 * @param array<mixed>    $details Details.
	 * @return self
	 */
	public static function skipped(
		SourceImage $source,
		string $code,
		string $message,
		?string $detected_mime,
		?string $collected_mime,
		AnimationStatus $animation_status,
		array $details = array()
	): self {
		return new self(
			$source,
			self::STATUS_SKIPPED,
			$code,
			$message,
			$detected_mime,
			$collected_mime,
			$animation_status,
			array(),
			$details
		);
	}

	/**
	 * Build an invalid result.
	 *
	 * @param SourceImage     $source Source image.
	 * @param string          $code Code.
	 * @param string          $message Message.
	 * @param string|null     $detected_mime Detected MIME.
	 * @param string|null     $collected_mime Collected MIME.
	 * @param AnimationStatus $animation_status Animation status.
	 * @param array<mixed>    $details Details.
	 * @return self
	 */
	public static function invalid(
		SourceImage $source,
		string $code,
		string $message,
		?string $detected_mime,
		?string $collected_mime,
		AnimationStatus $animation_status,
		array $details = array()
	): self {
		return new self(
			$source,
			self::STATUS_INVALID,
			$code,
			$message,
			$detected_mime,
			$collected_mime,
			$animation_status,
			array(),
			$details
		);
	}

	/**
	 * Get valid statuses.
	 *
	 * @return string[]
	 */
	public static function statuses(): array {
		return array(
			self::STATUS_ELIGIBLE,
			self::STATUS_SKIPPED,
			self::STATUS_INVALID,
		);
	}

	/**
	 * Get source image.
	 *
	 * @return SourceImage
	 */
	public function source(): SourceImage {
		return $this->source;
	}

	/**
	 * Get status.
	 *
	 * @return string
	 */
	public function status(): string {
		return $this->status;
	}

	/**
	 * Get code.
	 *
	 * @return string
	 */
	public function code(): string {
		return $this->code;
	}

	/**
	 * Get message.
	 *
	 * @return string
	 */
	public function message(): string {
		return $this->message;
	}

	/**
	 * Get detected MIME.
	 *
	 * @return string|null
	 */
	public function detected_mime(): ?string {
		return $this->detected_mime;
	}

	/**
	 * Get collected MIME.
	 *
	 * @return string|null
	 */
	public function collected_mime(): ?string {
		return $this->collected_mime;
	}

	/**
	 * Get animation status.
	 *
	 * @return AnimationStatus
	 */
	public function animation_status(): AnimationStatus {
		return $this->animation_status;
	}

	/**
	 * Get target formats.
	 *
	 * @return string[]
	 */
	public function target_formats(): array {
		return $this->target_formats;
	}

	/**
	 * Get details.
	 *
	 * @return array<mixed>
	 */
	public function details(): array {
		return $this->details;
	}

	/**
	 * Whether result is eligible.
	 *
	 * @return bool
	 */
	public function is_eligible(): bool {
		return self::STATUS_ELIGIBLE === $this->status;
	}

	/**
	 * Serialize result without exposing absolute paths.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'source'           => $this->source->to_array(),
			'status'           => $this->status,
			'code'             => $this->code,
			'message'          => $this->message,
			'detected_mime'    => $this->detected_mime,
			'collected_mime'   => $this->collected_mime,
			'animation_status' => $this->animation_status->to_array(),
			'target_formats'   => $this->target_formats,
			'details'          => $this->details,
		);
	}

	/**
	 * Normalize a code.
	 *
	 * @param string $code Code.
	 * @return string
	 */
	private function normalize_code( string $code ): string {
		$code = strtolower( trim( $code ) );
		$code = (string) preg_replace( '/[^a-z0-9_]/', '_', $code );

		return '' === $code ? self::CODE_SOURCE_INVALID_MIME : substr( $code, 0, 64 );
	}

	/**
	 * Normalize nullable string.
	 *
	 * @param string|null $value Value.
	 * @return string|null
	 */
	private function normalize_nullable_string( ?string $value ): ?string {
		return null === $value || '' === trim( $value ) ? null : strtolower( trim( $value ) );
	}

	/**
	 * Sanitize target formats.
	 *
	 * @param string[] $target_formats Target formats.
	 * @return string[]
	 */
	private function sanitize_target_formats( array $target_formats ): array {
		$sanitized = array();

		foreach ( $target_formats as $format ) {
			if ( ! is_string( $format ) ) {
				continue;
			}

			$format = strtolower( trim( $format ) );

			if ( in_array( $format, array( SourceMimePolicy::TARGET_WEBP, SourceMimePolicy::TARGET_AVIF ), true ) && ! in_array( $format, $sanitized, true ) ) {
				$sanitized[] = $format;
			}
		}

		return $sanitized;
	}

	/**
	 * Sanitize public details.
	 *
	 * @param array<mixed> $details Details.
	 * @return array<mixed>
	 */
	private function sanitize_details( array $details ): array {
		$sanitized = array();

		foreach ( $details as $key => $value ) {
			$detail_key = is_int( $key ) ? $key : $this->sanitize_key( (string) $key );

			if ( is_string( $value ) ) {
				$sanitized[ $detail_key ] = $this->redact_absolute_paths( $value );
			} elseif ( is_int( $value ) || is_float( $value ) || is_bool( $value ) || null === $value ) {
				$sanitized[ $detail_key ] = $value;
			}
		}

		return $sanitized;
	}

	/**
	 * Sanitize detail key.
	 *
	 * @param string $key Key.
	 * @return string
	 */
	private function sanitize_key( string $key ): string {
		$key = strtolower( trim( $key ) );
		$key = (string) preg_replace( '/[^a-z0-9_\-]/', '_', $key );

		return '' === $key ? 'detail' : substr( $key, 0, 64 );
	}

	/**
	 * Redact absolute paths.
	 *
	 * @param string $value Value.
	 * @return string
	 */
	private function redact_absolute_paths( string $value ): string {
		$value = (string) preg_replace(
			'/(^|[\s({\[=:])(?:[A-Za-z]:[\\\\\/][^\s<>"\')\]}]+)/',
			'$1[redacted_path]',
			$value
		);

		return (string) preg_replace(
			'/(^|[\s({\[=:])\/(?:[A-Za-z0-9._-]+\/?)+[^\s<>"\')\]}]*/',
			'$1[redacted_path]',
			$value
		);
	}
}
