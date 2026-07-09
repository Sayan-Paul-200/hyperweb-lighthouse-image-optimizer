<?php
/**
 * Uploads directory status.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Infrastructure;

/**
 * Captures WordPress uploads directory availability.
 */
final class UploadsStatus {

	public const STATUS_AVAILABLE    = 'available';
	public const STATUS_ERROR        = 'error';
	public const STATUS_MISSING      = 'missing';
	public const STATUS_NOT_WRITABLE = 'not_writable';
	public const STATUS_UNKNOWN      = 'unknown';

	/**
	 * Uploads base directory.
	 *
	 * @var string|null
	 */
	private $basedir;

	/**
	 * Uploads error from WordPress.
	 *
	 * @var string|null
	 */
	private $error;

	/**
	 * Whether the base directory is writable.
	 *
	 * @var bool|null
	 */
	private $writable;

	/**
	 * Status value.
	 *
	 * @var string
	 */
	private $status;

	/**
	 * Create status.
	 *
	 * @param string|null $basedir Uploads base directory.
	 * @param string|null $error Uploads error.
	 * @param bool|null   $writable Whether the directory is writable.
	 * @param string      $status Status value.
	 */
	public function __construct( ?string $basedir, ?string $error, ?bool $writable, string $status ) {
		$this->basedir  = $basedir;
		$this->error    = $error;
		$this->writable = $writable;
		$this->status   = in_array( $status, self::statuses(), true ) ? $status : self::STATUS_UNKNOWN;
	}

	/**
	 * Build status from WordPress upload data.
	 *
	 * @param array<string,mixed>|null $uploads Upload data.
	 * @param bool|null                $writable Writability result.
	 * @return self
	 */
	public static function from_uploads( ?array $uploads, ?bool $writable ): self {
		if ( null === $uploads ) {
			return new self( null, null, null, self::STATUS_UNKNOWN );
		}

		$basedir = isset( $uploads['basedir'] ) && is_string( $uploads['basedir'] )
			? $uploads['basedir']
			: null;
		$error   = isset( $uploads['error'] ) && is_string( $uploads['error'] ) && '' !== $uploads['error']
			? $uploads['error']
			: null;

		if ( null !== $error ) {
			return new self( $basedir, $error, $writable, self::STATUS_ERROR );
		}

		if ( null === $basedir || '' === trim( $basedir ) ) {
			return new self( $basedir, null, $writable, self::STATUS_MISSING );
		}

		if ( false === $writable ) {
			return new self( $basedir, null, false, self::STATUS_NOT_WRITABLE );
		}

		if ( true === $writable ) {
			return new self( $basedir, null, true, self::STATUS_AVAILABLE );
		}

		return new self( $basedir, null, null, self::STATUS_UNKNOWN );
	}

	/**
	 * Get valid statuses.
	 *
	 * @return string[]
	 */
	public static function statuses(): array {
		return array(
			self::STATUS_AVAILABLE,
			self::STATUS_ERROR,
			self::STATUS_MISSING,
			self::STATUS_NOT_WRITABLE,
			self::STATUS_UNKNOWN,
		);
	}

	/**
	 * Get uploads base directory.
	 *
	 * @return string|null
	 */
	public function basedir(): ?string {
		return $this->basedir;
	}

	/**
	 * Get uploads error.
	 *
	 * @return string|null
	 */
	public function error(): ?string {
		return $this->error;
	}

	/**
	 * Whether uploads are writable.
	 *
	 * @return bool|null
	 */
	public function is_writable(): ?bool {
		return $this->writable;
	}

	/**
	 * Get status.
	 *
	 * @return string
	 */
	public function status(): string {
		return $this->status;
	}
}
