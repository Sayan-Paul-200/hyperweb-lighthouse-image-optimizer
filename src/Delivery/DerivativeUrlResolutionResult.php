<?php
/**
 * Derivative URL resolution result.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Delivery;

/**
 * Reports the outcome of one derivative URL resolution.
 */
final class DerivativeUrlResolutionResult {

	public const CODE_RESOLVED                = 'resolved';
	public const CODE_INVALID_RELATIVE_PATH   = 'invalid_relative_path';
	public const CODE_UPLOADS_URL_UNAVAILABLE = 'uploads_url_unavailable';

	/**
	 * Whether the resolution succeeded.
	 *
	 * @var bool
	 */
	private $success;

	/**
	 * Resolved URL.
	 *
	 * @var string|null
	 */
	private $url;

	/**
	 * Relative path.
	 *
	 * @var string
	 */
	private $relative_path;

	/**
	 * Result code.
	 *
	 * @var string
	 */
	private $code;

	/**
	 * Attachment ID.
	 *
	 * @var int|null
	 */
	private $attachment_id;

	/**
	 * Size name.
	 *
	 * @var string|null
	 */
	private $size_name;

	/**
	 * Format.
	 *
	 * @var string|null
	 */
	private $format;

	/**
	 * Create result.
	 *
	 * @param bool        $success Whether resolution succeeded.
	 * @param string|null $url Resolved URL.
	 * @param string      $relative_path Relative path.
	 * @param string      $code Result code.
	 * @param int|null    $attachment_id Attachment ID.
	 * @param string|null $size_name Size name.
	 * @param string|null $format Format.
	 */
	public function __construct(
		bool $success,
		?string $url,
		string $relative_path,
		string $code,
		?int $attachment_id = null,
		?string $size_name = null,
		?string $format = null
	) {
		$this->success       = $success;
		$this->url           = $url;
		$this->relative_path = $relative_path;
		$this->code          = $code;
		$this->attachment_id = $attachment_id;
		$this->size_name     = $size_name;
		$this->format        = $format;
	}

	/**
	 * Build a successful resolution result.
	 *
	 * @param DerivativeUrlRequest $request Request.
	 * @param string               $url Resolved URL.
	 * @return self
	 */
	public static function resolved( DerivativeUrlRequest $request, string $url ): self {
		return new self(
			true,
			$url,
			$request->relative_path(),
			self::CODE_RESOLVED,
			$request->attachment_id(),
			$request->size_name(),
			$request->format()
		);
	}

	/**
	 * Build an invalid resolution result.
	 *
	 * @param DerivativeUrlRequest $request Request.
	 * @param string               $code Result code.
	 * @return self
	 */
	public static function invalid( DerivativeUrlRequest $request, string $code ): self {
		return new self(
			false,
			null,
			$request->relative_path(),
			$code,
			$request->attachment_id(),
			$request->size_name(),
			$request->format()
		);
	}

	/**
	 * Whether the resolution succeeded.
	 *
	 * @return bool
	 */
	public function is_successful(): bool {
		return $this->success;
	}

	/**
	 * Get URL.
	 *
	 * @return string|null
	 */
	public function url(): ?string {
		return $this->url;
	}

	/**
	 * Get relative path.
	 *
	 * @return string
	 */
	public function relative_path(): string {
		return $this->relative_path;
	}

	/**
	 * Get result code.
	 *
	 * @return string
	 */
	public function code(): string {
		return $this->code;
	}

	/**
	 * Get attachment ID.
	 *
	 * @return int|null
	 */
	public function attachment_id(): ?int {
		return $this->attachment_id;
	}

	/**
	 * Get size name.
	 *
	 * @return string|null
	 */
	public function size_name(): ?string {
		return $this->size_name;
	}

	/**
	 * Get format.
	 *
	 * @return string|null
	 */
	public function format(): ?string {
		return $this->format;
	}

	/**
	 * Serialize result.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'success'       => $this->success,
			'url'           => $this->url,
			'relative_path' => $this->relative_path,
			'code'          => $this->code,
			'attachment_id' => $this->attachment_id,
			'size_name'     => $this->size_name,
			'format'        => $this->format,
		);
	}
}
