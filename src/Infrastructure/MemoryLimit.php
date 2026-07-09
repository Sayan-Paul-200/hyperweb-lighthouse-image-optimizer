<?php
/**
 * PHP memory limit value object.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Infrastructure;

/**
 * Parses a PHP memory limit without changing runtime limits.
 */
final class MemoryLimit {

	/**
	 * Raw memory limit value.
	 *
	 * @var string
	 */
	private $raw;

	/**
	 * Parsed byte count.
	 *
	 * @var int|null
	 */
	private $bytes;

	/**
	 * Whether memory is unlimited.
	 *
	 * @var bool
	 */
	private $unlimited;

	/**
	 * Whether the value could not be parsed.
	 *
	 * @var bool
	 */
	private $unknown;

	/**
	 * Create a parsed memory limit.
	 *
	 * @param string   $raw Raw value.
	 * @param int|null $bytes Parsed bytes.
	 * @param bool     $unlimited Whether memory is unlimited.
	 * @param bool     $unknown Whether the value is unknown.
	 */
	public function __construct( string $raw, ?int $bytes, bool $unlimited, bool $unknown ) {
		$this->raw       = $raw;
		$this->bytes     = $bytes;
		$this->unlimited = $unlimited;
		$this->unknown   = $unknown;
	}

	/**
	 * Parse a raw PHP memory-limit value.
	 *
	 * @param string $raw Raw value.
	 * @return self
	 */
	public static function from_raw( string $raw ): self {
		$value = trim( $raw );

		if ( '' === $value ) {
			return new self( $raw, null, false, true );
		}

		if ( '-1' === $value ) {
			return new self( $raw, null, true, false );
		}

		if ( 1 !== preg_match( '/^(\d+)([kmg])?$/i', $value, $matches ) ) {
			return new self( $raw, null, false, true );
		}

		$bytes  = (int) $matches[1];
		$suffix = isset( $matches[2] ) ? strtolower( $matches[2] ) : '';

		if ( 'g' === $suffix ) {
			$bytes *= 1024 * 1024 * 1024;
		} elseif ( 'm' === $suffix ) {
			$bytes *= 1024 * 1024;
		} elseif ( 'k' === $suffix ) {
			$bytes *= 1024;
		}

		return new self( $raw, $bytes, false, false );
	}

	/**
	 * Get the raw value.
	 *
	 * @return string
	 */
	public function raw(): string {
		return $this->raw;
	}

	/**
	 * Get parsed bytes.
	 *
	 * @return int|null
	 */
	public function bytes(): ?int {
		return $this->bytes;
	}

	/**
	 * Whether memory is unlimited.
	 *
	 * @return bool
	 */
	public function is_unlimited(): bool {
		return $this->unlimited;
	}

	/**
	 * Whether the value could not be parsed.
	 *
	 * @return bool
	 */
	public function is_unknown(): bool {
		return $this->unknown;
	}
}
