<?php
/**
 * Resource guard result value object.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Image;

/**
 * Carries the outcome of a pre-allocation resource check.
 */
final class ResourceGuardResult {

	/**
	 * Whether the source is allowed.
	 *
	 * @var bool
	 */
	private $allowed;

	/**
	 * Error code if denied.
	 *
	 * @var string|null
	 */
	private $code;

	/**
	 * Machine-readable reason.
	 *
	 * @var string|null
	 */
	private $reason;

	/**
	 * Source pixel count.
	 *
	 * @var int
	 */
	private $pixel_count;

	/**
	 * Maximum pixel count allowed.
	 *
	 * @var int
	 */
	private $max_pixel_count;

	/**
	 * Estimated memory bytes.
	 *
	 * @var int
	 */
	private $estimated_memory_bytes;

	/**
	 * Available memory bytes.
	 *
	 * @var int|null
	 */
	private $available_memory_bytes;

	/**
	 * Human-readable message.
	 *
	 * @var string
	 */
	private $message;

	/**
	 * Create a new result.
	 *
	 * @param bool        $allowed Whether the source is allowed.
	 * @param string|null $code Error code if denied.
	 * @param string|null $reason Machine-readable reason.
	 * @param int         $pixel_count Source pixel count.
	 * @param int         $max_pixel_count Maximum pixel count allowed.
	 * @param int         $estimated_memory_bytes Estimated memory bytes.
	 * @param int|null    $available_memory_bytes Available memory bytes.
	 * @param string      $message Human-readable message.
	 */
	private function __construct(
		bool $allowed,
		?string $code,
		?string $reason,
		int $pixel_count,
		int $max_pixel_count,
		int $estimated_memory_bytes,
		?int $available_memory_bytes,
		string $message
	) {
		$this->allowed                = $allowed;
		$this->code                   = $code;
		$this->reason                 = $reason;
		$this->pixel_count            = $pixel_count;
		$this->max_pixel_count        = $max_pixel_count;
		$this->estimated_memory_bytes = $estimated_memory_bytes;
		$this->available_memory_bytes = $available_memory_bytes;
		$this->message                = $message;
	}

	/**
	 * Build an allowed result.
	 *
	 * @param int      $pixel_count Source pixel count.
	 * @param int      $max_pixel_count Maximum pixel count allowed.
	 * @param int      $estimated_memory_bytes Estimated memory bytes.
	 * @param int|null $available_memory_bytes Available memory bytes.
	 * @return self
	 */
	public static function allowed(
		int $pixel_count,
		int $max_pixel_count,
		int $estimated_memory_bytes,
		?int $available_memory_bytes
	): self {
		return new self(
			true,
			null,
			null,
			$pixel_count,
			$max_pixel_count,
			$estimated_memory_bytes,
			$available_memory_bytes,
			'Resource limits are satisfied.'
		);
	}

	/**
	 * Build a denied result.
	 *
	 * @param string   $reason Machine-readable reason.
	 * @param string   $message Human-readable message.
	 * @param int      $pixel_count Source pixel count.
	 * @param int      $max_pixel_count Maximum pixel count allowed.
	 * @param int      $estimated_memory_bytes Estimated memory bytes.
	 * @param int|null $available_memory_bytes Available memory bytes.
	 * @return self
	 */
	public static function denied(
		string $reason,
		string $message,
		int $pixel_count,
		int $max_pixel_count,
		int $estimated_memory_bytes,
		?int $available_memory_bytes
	): self {
		return new self(
			false,
			ConversionResultCode::SKIPPED_RESOURCE_LIMIT,
			$reason,
			$pixel_count,
			$max_pixel_count,
			$estimated_memory_bytes,
			$available_memory_bytes,
			$message
		);
	}

	/**
	 * Whether the source is allowed.
	 *
	 * @return bool
	 */
	public function is_allowed(): bool {
		return $this->allowed;
	}

	/**
	 * Whether the source is denied.
	 *
	 * @return bool
	 */
	public function is_denied(): bool {
		return ! $this->allowed;
	}

	/**
	 * Get the error code if denied.
	 *
	 * @return string|null
	 */
	public function code(): ?string {
		return $this->code;
	}

	/**
	 * Get the machine-readable reason.
	 *
	 * @return string|null
	 */
	public function reason(): ?string {
		return $this->reason;
	}

	/**
	 * Get the source pixel count.
	 *
	 * @return int
	 */
	public function pixel_count(): int {
		return $this->pixel_count;
	}

	/**
	 * Get the maximum pixel count allowed.
	 *
	 * @return int
	 */
	public function max_pixel_count(): int {
		return $this->max_pixel_count;
	}

	/**
	 * Get the estimated memory bytes.
	 *
	 * @return int
	 */
	public function estimated_memory_bytes(): int {
		return $this->estimated_memory_bytes;
	}

	/**
	 * Get the available memory bytes.
	 *
	 * @return int|null
	 */
	public function available_memory_bytes(): ?int {
		return $this->available_memory_bytes;
	}

	/**
	 * Get the human-readable message.
	 *
	 * @return string
	 */
	public function message(): string {
		return $this->message;
	}

	/**
	 * Serialize without exposing internal structure unnecessarily.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'allowed'                => $this->allowed,
			'code'                   => $this->code,
			'reason'                 => $this->reason,
			'pixel_count'            => $this->pixel_count,
			'max_pixel_count'        => $this->max_pixel_count,
			'estimated_memory_bytes' => $this->estimated_memory_bytes,
			'available_memory_bytes' => $this->available_memory_bytes,
			'message'                => $this->message,
		);
	}
}
