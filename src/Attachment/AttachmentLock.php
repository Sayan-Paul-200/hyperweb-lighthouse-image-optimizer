<?php
/**
 * Attachment lock value object.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Attachment;

/**
 * Carries the plugin-owned `_hwlio_lock` payload.
 */
final class AttachmentLock {

	/**
	 * Lock token.
	 *
	 * @var string
	 */
	private $token;

	/**
	 * Created timestamp.
	 *
	 * @var int
	 */
	private $created_at;

	/**
	 * Expiration timestamp.
	 *
	 * @var int
	 */
	private $expires_at;

	/**
	 * Create a lock.
	 *
	 * @param string $token Token.
	 * @param int    $created_at Created timestamp.
	 * @param int    $expires_at Expiration timestamp.
	 */
	public function __construct( string $token, int $created_at, int $expires_at ) {
		$this->token      = trim( $token );
		$this->created_at = max( 0, $created_at );
		$this->expires_at = max( 0, $expires_at );
	}

	/**
	 * Build a lock from stored metadata.
	 *
	 * @param mixed $raw Raw stored value.
	 * @return self|null
	 */
	public static function from_stored( $raw ): ?self {
		if ( ! is_array( $raw ) ) {
			return null;
		}

		$token      = self::array_string( $raw, 'token' );
		$created_at = self::array_int( $raw, 'created_at' );
		$expires_at = self::array_int( $raw, 'expires_at' );

		if ( '' === $token || 191 < strlen( $token ) || 0 >= $expires_at || $expires_at <= $created_at ) {
			return null;
		}

		return new self( $token, $created_at, $expires_at );
	}

	/**
	 * Get token for internal comparisons.
	 *
	 * @return string
	 */
	public function token(): string {
		return $this->token;
	}

	/**
	 * Get created timestamp.
	 *
	 * @return int
	 */
	public function created_at(): int {
		return $this->created_at;
	}

	/**
	 * Get expiration timestamp.
	 *
	 * @return int
	 */
	public function expires_at(): int {
		return $this->expires_at;
	}

	/**
	 * Determine whether lock is expired.
	 *
	 * @param int $now Current timestamp.
	 * @return bool
	 */
	public function is_expired( int $now ): bool {
		return max( 0, $now ) >= $this->expires_at;
	}

	/**
	 * Determine whether a token matches this lock.
	 *
	 * @param string $token Token.
	 * @return bool
	 */
	public function token_matches( string $token ): bool {
		return hash_equals( $this->token, trim( $token ) );
	}

	/**
	 * Seconds until expiration.
	 *
	 * @param int $now Current timestamp.
	 * @return int
	 */
	public function seconds_remaining( int $now ): int {
		return max( 0, $this->expires_at - max( 0, $now ) );
	}

	/**
	 * Serialize for storage.
	 *
	 * @return array<string,mixed>
	 */
	public function to_storage_array(): array {
		return array(
			'token'      => $this->token,
			'created_at' => $this->created_at,
			'expires_at' => $this->expires_at,
		);
	}

	/**
	 * Serialize safely without exposing the token.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'created_at' => $this->created_at,
			'expires_at' => $this->expires_at,
		);
	}

	/**
	 * Get string value from array.
	 *
	 * @param array<mixed> $values Values.
	 * @param string       $key Key.
	 * @return string
	 */
	private static function array_string( array $values, string $key ): string {
		return isset( $values[ $key ] ) && is_scalar( $values[ $key ] ) ? trim( (string) $values[ $key ] ) : '';
	}

	/**
	 * Get integer value from array.
	 *
	 * @param array<mixed> $values Values.
	 * @param string       $key Key.
	 * @return int
	 */
	private static function array_int( array $values, string $key ): int {
		return isset( $values[ $key ] ) && is_numeric( $values[ $key ] ) ? max( 0, (int) $values[ $key ] ) : 0;
	}
}
