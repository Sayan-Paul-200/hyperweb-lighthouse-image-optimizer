<?php
/**
 * Animation detection status.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Image;

/**
 * Represents the animation state of a source image.
 */
final class AnimationStatus {

	public const STATUS_ANIMATED       = 'animated';
	public const STATUS_NOT_ANIMATED   = 'not_animated';
	public const STATUS_NOT_APPLICABLE = 'not_applicable';
	public const STATUS_UNKNOWN        = 'unknown';

	/**
	 * MIME type.
	 *
	 * @var string
	 */
	private $mime_type;

	/**
	 * Status.
	 *
	 * @var string
	 */
	private $status;

	/**
	 * Reason code.
	 *
	 * @var string
	 */
	private $code;

	/**
	 * Create status.
	 *
	 * @param string $mime_type MIME type.
	 * @param string $status Status.
	 * @param string $code Reason code.
	 */
	private function __construct( string $mime_type, string $status, string $code ) {
		$this->mime_type = strtolower( trim( $mime_type ) );
		$this->status    = in_array( $status, self::statuses(), true ) ? $status : self::STATUS_UNKNOWN;
		$this->code      = $this->normalize_code( $code );
	}

	/**
	 * Build animated status.
	 *
	 * @param string $mime_type MIME type.
	 * @param string $code Reason code.
	 * @return self
	 */
	public static function animated( string $mime_type, string $code = 'animated_image' ): self {
		return new self( $mime_type, self::STATUS_ANIMATED, $code );
	}

	/**
	 * Build not animated status.
	 *
	 * @param string $mime_type MIME type.
	 * @return self
	 */
	public static function not_animated( string $mime_type ): self {
		return new self( $mime_type, self::STATUS_NOT_ANIMATED, self::STATUS_NOT_ANIMATED );
	}

	/**
	 * Build not applicable status.
	 *
	 * @param string $mime_type MIME type.
	 * @return self
	 */
	public static function not_applicable( string $mime_type ): self {
		return new self( $mime_type, self::STATUS_NOT_APPLICABLE, self::STATUS_NOT_APPLICABLE );
	}

	/**
	 * Build unknown status.
	 *
	 * @param string $mime_type MIME type.
	 * @param string $code Reason code.
	 * @return self
	 */
	public static function unknown( string $mime_type, string $code = 'animation_unknown' ): self {
		return new self( $mime_type, self::STATUS_UNKNOWN, $code );
	}

	/**
	 * Get valid statuses.
	 *
	 * @return string[]
	 */
	public static function statuses(): array {
		return array(
			self::STATUS_ANIMATED,
			self::STATUS_NOT_ANIMATED,
			self::STATUS_NOT_APPLICABLE,
			self::STATUS_UNKNOWN,
		);
	}

	/**
	 * Get MIME type.
	 *
	 * @return string
	 */
	public function mime_type(): string {
		return $this->mime_type;
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
	 * Get reason code.
	 *
	 * @return string
	 */
	public function code(): string {
		return $this->code;
	}

	/**
	 * Whether image is animated.
	 *
	 * @return bool
	 */
	public function is_animated(): bool {
		return self::STATUS_ANIMATED === $this->status;
	}

	/**
	 * Whether animation status is unknown.
	 *
	 * @return bool
	 */
	public function is_unknown(): bool {
		return self::STATUS_UNKNOWN === $this->status;
	}

	/**
	 * Serialize status.
	 *
	 * @return array<string,string>
	 */
	public function to_array(): array {
		return array(
			'mime_type' => $this->mime_type,
			'status'    => $this->status,
			'code'      => $this->code,
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

		return '' === $code ? 'animation_unknown' : substr( $code, 0, 64 );
	}
}
