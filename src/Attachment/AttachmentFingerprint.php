<?php
/**
 * Attachment fingerprint value object.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Attachment;

/**
 * Carries a cheap source and metadata fingerprint for one attachment state.
 */
final class AttachmentFingerprint {

	private const SIGNATURE_LENGTH = 20;

	/**
	 * Current full source path relative to uploads.
	 *
	 * @var string
	 */
	private $relative_file;

	/**
	 * Current full source byte size.
	 *
	 * @var int
	 */
	private $file_size;

	/**
	 * Current full source modified time.
	 *
	 * @var int
	 */
	private $modified_time;

	/**
	 * Hash of normalized attachment source metadata.
	 *
	 * @var string
	 */
	private $metadata_hash;

	/**
	 * Short queue-safe fingerprint signature.
	 *
	 * @var string
	 */
	private $signature;

	/**
	 * Create fingerprint.
	 *
	 * @param string      $relative_file Uploads-relative full source path.
	 * @param int         $file_size Full source bytes.
	 * @param int         $modified_time Full source modified time.
	 * @param string      $metadata_hash Metadata hash.
	 * @param string|null $signature Optional precomputed signature.
	 */
	public function __construct(
		string $relative_file,
		int $file_size,
		int $modified_time,
		string $metadata_hash,
		?string $signature = null
	) {
		$this->relative_file = $this->normalize_relative_path( $relative_file );
		$this->file_size     = max( 0, $file_size );
		$this->modified_time = max( 0, $modified_time );
		$this->metadata_hash = $this->normalize_metadata_hash( $metadata_hash );
		$this->signature     = $this->normalize_signature( $signature );
	}

	/**
	 * Build a fingerprint from stored metadata array shape.
	 *
	 * @param array<string,mixed> $fingerprint Stored fingerprint.
	 * @return self|null
	 */
	public static function from_array( array $fingerprint ): ?self {
		$relative_file = self::array_string( $fingerprint, 'relative_file' );
		$file_size     = self::array_int( $fingerprint, 'file_size' );
		$modified_time = self::array_int( $fingerprint, 'modified_time' );
		$metadata_hash = self::array_string( $fingerprint, 'metadata_hash' );
		$signature     = self::array_string( $fingerprint, 'signature' );

		if (
			'' === $relative_file ||
			null === $file_size ||
			null === $modified_time ||
			! self::is_safe_relative_path( $relative_file ) ||
			! self::is_valid_metadata_hash( $metadata_hash )
		) {
			return null;
		}

		if ( '' !== $signature && ! self::is_valid_signature( $signature ) ) {
			return null;
		}

		return new self(
			$relative_file,
			$file_size,
			$modified_time,
			$metadata_hash,
			'' === $signature ? null : $signature
		);
	}

	/**
	 * Get short signature length.
	 *
	 * @return int
	 */
	public static function signature_length(): int {
		return self::SIGNATURE_LENGTH;
	}

	/**
	 * Get full source relative file.
	 *
	 * @return string
	 */
	public function relative_file(): string {
		return $this->relative_file;
	}

	/**
	 * Get full source byte size.
	 *
	 * @return int
	 */
	public function file_size(): int {
		return $this->file_size;
	}

	/**
	 * Get full source modified time.
	 *
	 * @return int
	 */
	public function modified_time(): int {
		return $this->modified_time;
	}

	/**
	 * Get metadata hash.
	 *
	 * @return string
	 */
	public function metadata_hash(): string {
		return $this->metadata_hash;
	}

	/**
	 * Get short queue-safe signature.
	 *
	 * @return string
	 */
	public function signature(): string {
		return $this->signature;
	}

	/**
	 * Serialize public-safe fingerprint fields.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'relative_file' => $this->relative_file,
			'file_size'     => $this->file_size,
			'modified_time' => $this->modified_time,
			'metadata_hash' => $this->metadata_hash,
			'signature'     => $this->signature,
		);
	}

	/**
	 * Normalize relative path without exposing absolute paths.
	 *
	 * @param string $path Path.
	 * @return string
	 */
	private function normalize_relative_path( string $path ): string {
		$path = str_replace( '\\', '/', trim( $path ) );
		$path = ltrim( $path, '/' );

		return self::is_safe_relative_path( $path ) ? $path : '';
	}

	/**
	 * Normalize metadata hash.
	 *
	 * @param string $metadata_hash Metadata hash.
	 * @return string
	 */
	private function normalize_metadata_hash( string $metadata_hash ): string {
		$metadata_hash = strtolower( trim( $metadata_hash ) );

		if ( self::is_valid_metadata_hash( $metadata_hash ) ) {
			return $metadata_hash;
		}

		return hash( 'sha256', $metadata_hash );
	}

	/**
	 * Normalize short signature.
	 *
	 * @param string|null $signature Signature.
	 * @return string
	 */
	private function normalize_signature( ?string $signature ): string {
		$signature = null === $signature ? '' : strtolower( trim( $signature ) );

		if ( self::is_valid_signature( $signature ) ) {
			return $signature;
		}

		return substr(
			hash(
				'sha256',
				$this->relative_file . '|' . $this->file_size . '|' . $this->modified_time . '|' . $this->metadata_hash
			),
			0,
			self::SIGNATURE_LENGTH
		);
	}

	/**
	 * Read string from array.
	 *
	 * @param array<string,mixed> $values Values.
	 * @param string              $key Key.
	 * @return string
	 */
	private static function array_string( array $values, string $key ): string {
		return isset( $values[ $key ] ) && is_scalar( $values[ $key ] ) ? trim( (string) $values[ $key ] ) : '';
	}

	/**
	 * Read non-negative int from array.
	 *
	 * @param array<string,mixed> $values Values.
	 * @param string              $key Key.
	 * @return int|null
	 */
	private static function array_int( array $values, string $key ): ?int {
		if ( ! isset( $values[ $key ] ) || ! is_numeric( $values[ $key ] ) ) {
			return null;
		}

		$value = (int) $values[ $key ];

		return 0 <= $value ? $value : null;
	}

	/**
	 * Check metadata hash shape.
	 *
	 * @param string $metadata_hash Metadata hash.
	 * @return bool
	 */
	private static function is_valid_metadata_hash( string $metadata_hash ): bool {
		return 1 === preg_match( '/^[a-f0-9]{64}$/', strtolower( trim( $metadata_hash ) ) );
	}

	/**
	 * Check signature shape.
	 *
	 * @param string $signature Signature.
	 * @return bool
	 */
	private static function is_valid_signature( string $signature ): bool {
		return 1 === preg_match( '/^[a-f0-9]{20}$/', strtolower( trim( $signature ) ) );
	}

	/**
	 * Determine whether a relative path is safe to serialize.
	 *
	 * @param string $path Path.
	 * @return bool
	 */
	private static function is_safe_relative_path( string $path ): bool {
		if ( '' === $path || false !== strpos( $path, "\0" ) ) {
			return false;
		}

		if ( 1 === preg_match( '#^(?:[A-Za-z]:)?[\\\\/]#', $path ) || false !== strpos( $path, '://' ) ) {
			return false;
		}

		foreach ( explode( '/', str_replace( '\\', '/', $path ) ) as $segment ) {
			if ( '' === $segment || '.' === $segment || '..' === $segment ) {
				return false;
			}
		}

		return true;
	}
}
