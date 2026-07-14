<?php
/**
 * Local source resolution result.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration\Offload;

use HyperWeb\LighthouseImageOptimizer\Image\SourceImage;

/**
 * Carries the resolved local source and temporary-file lease outcome.
 */
final class LocalSourceResolutionResult {

	/**
	 * Success flag.
	 *
	 * @var bool
	 */
	private $successful;

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
	 * Source image.
	 *
	 * @var SourceImage|null
	 */
	private $source;

	/**
	 * Temporary lease.
	 *
	 * @var TemporarySourceLease|null
	 */
	private $lease;

	/**
	 * Create result.
	 *
	 * @param bool                     $successful Success flag.
	 * @param string                   $code Code.
	 * @param string                   $message Message.
	 * @param SourceImage|null         $source Source image.
	 * @param TemporarySourceLease|null $lease Temporary lease.
	 */
	public function __construct(
		bool $successful,
		string $code,
		string $message,
		?SourceImage $source = null,
		?TemporarySourceLease $lease = null
	) {
		$this->successful = $successful;
		$this->code       = strtolower( trim( $code ) );
		$this->message    = trim( $message );
		$this->source     = $source;
		$this->lease      = $lease;
	}

	/**
	 * Build success result.
	 *
	 * @param SourceImage         $source Source image.
	 * @param TemporarySourceLease $lease Temporary lease.
	 * @return self
	 */
	public static function success( SourceImage $source, TemporarySourceLease $lease ): self {
		return new self( true, OffloadSiteSupport::CODE_SUPPORTED, 'A temporary local source was materialized successfully.', $source, $lease );
	}

	/**
	 * Build failure result.
	 *
	 * @param string $code Code.
	 * @param string $message Message.
	 * @return self
	 */
	public static function failure( string $code, string $message ): self {
		return new self( false, $code, $message );
	}

	/**
	 * Whether successful.
	 *
	 * @return bool
	 */
	public function is_successful(): bool {
		return $this->successful;
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
	 * Get source.
	 *
	 * @return SourceImage|null
	 */
	public function source(): ?SourceImage {
		return $this->source;
	}

	/**
	 * Get temporary lease.
	 *
	 * @return TemporarySourceLease|null
	 */
	public function lease(): ?TemporarySourceLease {
		return $this->lease;
	}
}
