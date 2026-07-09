<?php
/**
 * Image format support result.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Infrastructure;

/**
 * Describes environment support for one modern image output format.
 */
final class FormatSupportResult {

	public const FORMAT_WEBP = 'webp';
	public const FORMAT_AVIF = 'avif';

	public const STATUS_SUPPORTED     = 'supported';
	public const STATUS_UNSUPPORTED   = 'unsupported';
	public const STATUS_MISCONFIGURED = 'misconfigured';
	public const STATUS_UNKNOWN       = 'unknown';

	/**
	 * Target format.
	 *
	 * @var string
	 */
	private $format;

	/**
	 * MIME type for the target format.
	 *
	 * @var string|null
	 */
	private $mime_type;

	/**
	 * Support status.
	 *
	 * @var string
	 */
	private $status;

	/**
	 * Whether WordPress recognizes the MIME type.
	 *
	 * @var bool|null
	 */
	private $mime_recognized;

	/**
	 * Whether WordPress image editors report encode support.
	 *
	 * @var bool|null
	 */
	private $encoding_supported;

	/**
	 * Stable reason code.
	 *
	 * @var string
	 */
	private $reason;

	/**
	 * Create the result.
	 *
	 * @param string      $format Target format.
	 * @param string|null $mime_type MIME type.
	 * @param string      $status Support status.
	 * @param bool|null   $mime_recognized Whether WordPress recognizes the MIME type.
	 * @param bool|null   $encoding_supported Whether encoding is supported.
	 * @param string      $reason Stable reason code.
	 */
	public function __construct(
		string $format,
		?string $mime_type,
		string $status,
		?bool $mime_recognized,
		?bool $encoding_supported,
		string $reason
	) {
		$this->format             = strtolower( trim( $format ) );
		$this->mime_type          = $mime_type;
		$this->status             = in_array( $status, self::statuses(), true ) ? $status : self::STATUS_UNKNOWN;
		$this->mime_recognized    = $mime_recognized;
		$this->encoding_supported = $encoding_supported;
		$this->reason             = '' === $reason ? self::STATUS_UNKNOWN : $reason;
	}

	/**
	 * Build a supported result.
	 *
	 * @param string $format Target format.
	 * @param string $mime_type MIME type.
	 * @return self
	 */
	public static function supported( string $format, string $mime_type ): self {
		return new self( $format, $mime_type, self::STATUS_SUPPORTED, true, true, self::STATUS_SUPPORTED );
	}

	/**
	 * Build an unsupported result.
	 *
	 * @param string      $format Target format.
	 * @param string|null $mime_type MIME type.
	 * @param bool|null   $mime_recognized Whether WordPress recognizes the MIME type.
	 * @param bool|null   $encoding_supported Whether encoding is supported.
	 * @param string      $reason Stable reason code.
	 * @return self
	 */
	public static function unsupported(
		string $format,
		?string $mime_type,
		?bool $mime_recognized,
		?bool $encoding_supported,
		string $reason
	): self {
		return new self(
			$format,
			$mime_type,
			self::STATUS_UNSUPPORTED,
			$mime_recognized,
			$encoding_supported,
			$reason
		);
	}

	/**
	 * Build a misconfigured result.
	 *
	 * @param string      $format Target format.
	 * @param string|null $mime_type MIME type.
	 * @param bool|null   $mime_recognized Whether WordPress recognizes the MIME type.
	 * @param bool|null   $encoding_supported Whether encoding is supported.
	 * @param string      $reason Stable reason code.
	 * @return self
	 */
	public static function misconfigured(
		string $format,
		?string $mime_type,
		?bool $mime_recognized,
		?bool $encoding_supported,
		string $reason
	): self {
		return new self(
			$format,
			$mime_type,
			self::STATUS_MISCONFIGURED,
			$mime_recognized,
			$encoding_supported,
			$reason
		);
	}

	/**
	 * Build an unknown result.
	 *
	 * @param string      $format Target format.
	 * @param string|null $mime_type MIME type.
	 * @param bool|null   $mime_recognized Whether WordPress recognizes the MIME type.
	 * @param bool|null   $encoding_supported Whether encoding is supported.
	 * @param string      $reason Stable reason code.
	 * @return self
	 */
	public static function unknown(
		string $format,
		?string $mime_type,
		?bool $mime_recognized,
		?bool $encoding_supported,
		string $reason
	): self {
		return new self(
			$format,
			$mime_type,
			self::STATUS_UNKNOWN,
			$mime_recognized,
			$encoding_supported,
			$reason
		);
	}

	/**
	 * Get all supported status values.
	 *
	 * @return string[]
	 */
	public static function statuses(): array {
		return array(
			self::STATUS_SUPPORTED,
			self::STATUS_UNSUPPORTED,
			self::STATUS_MISCONFIGURED,
			self::STATUS_UNKNOWN,
		);
	}

	/**
	 * Get the target format.
	 *
	 * @return string
	 */
	public function format(): string {
		return $this->format;
	}

	/**
	 * Get the MIME type.
	 *
	 * @return string|null
	 */
	public function mime_type(): ?string {
		return $this->mime_type;
	}

	/**
	 * Get the support status.
	 *
	 * @return string
	 */
	public function status(): string {
		return $this->status;
	}

	/**
	 * Whether the format is supported.
	 *
	 * @return bool
	 */
	public function is_supported(): bool {
		return self::STATUS_SUPPORTED === $this->status;
	}

	/**
	 * Whether the result should block newly enabling a format.
	 *
	 * @return bool
	 */
	public function blocks_enablement(): bool {
		return in_array(
			$this->status,
			array( self::STATUS_UNSUPPORTED, self::STATUS_MISCONFIGURED ),
			true
		);
	}

	/**
	 * Whether WordPress recognizes the MIME type.
	 *
	 * @return bool|null
	 */
	public function mime_recognized(): ?bool {
		return $this->mime_recognized;
	}

	/**
	 * Whether WordPress image editors report encode support.
	 *
	 * @return bool|null
	 */
	public function encoding_supported(): ?bool {
		return $this->encoding_supported;
	}

	/**
	 * Get the stable reason code.
	 *
	 * @return string
	 */
	public function reason(): string {
		return $this->reason;
	}
}
