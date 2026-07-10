<?php
/**
 * Derivative manifest sanitizer.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Attachment;

/**
 * Sanitizes stored derivative manifests before repository consumers trust them.
 */
final class DerivativeManifestSanitizer {

	/**
	 * Sanitize raw manifest data.
	 *
	 * @param mixed $raw Raw manifest.
	 * @return array{manifest:DerivativeManifest,codes:string[],messages:string[]}
	 */
	public function sanitize( $raw ): array {
		if ( null === $raw || false === $raw || '' === $raw ) {
			return array(
				'manifest' => DerivativeManifest::empty(),
				'codes'    => array( DerivativeRepositoryResult::CODE_EMPTY ),
				'messages' => array(),
			);
		}

		if ( ! is_array( $raw ) || (int) ( $raw['schema_version'] ?? 0 ) !== DerivativeManifest::SCHEMA_VERSION ) {
			return array(
				'manifest' => DerivativeManifest::empty(),
				'codes'    => array( DerivativeRepositoryResult::CODE_INVALID_METADATA_IGNORED ),
				'messages' => array( 'Stored derivative metadata was invalid and was ignored.' ),
			);
		}

		$codes       = array( DerivativeRepositoryResult::CODE_LOADED );
		$messages    = array();
		$fingerprint = isset( $raw['fingerprint'] ) && is_array( $raw['fingerprint'] )
			? AttachmentFingerprint::from_array( $raw['fingerprint'] )
			: null;
		$updated_at  = isset( $raw['updated_at'] ) && is_numeric( $raw['updated_at'] ) ? max( 0, (int) $raw['updated_at'] ) : 0;
		$sizes       = $this->sanitize_sizes( $raw['sizes'] ?? array(), $codes, $messages );

		if ( isset( $raw['fingerprint'] ) && null === $fingerprint ) {
			$codes[]    = DerivativeRepositoryResult::CODE_INVALID_METADATA_IGNORED;
			$messages[] = 'Stored derivative fingerprint was invalid and was ignored.';
		}

		return array(
			'manifest' => new DerivativeManifest( $fingerprint, $updated_at, $sizes ),
			'codes'    => array_values( array_unique( $codes ) ),
			'messages' => array_values( array_unique( $messages ) ),
		);
	}

	/**
	 * Sanitize size entries.
	 *
	 * @param mixed    $raw_sizes Raw sizes.
	 * @param string[] $codes Codes.
	 * @param string[] $messages Messages.
	 * @return array<string,array<string,mixed>>
	 */
	private function sanitize_sizes( $raw_sizes, array &$codes, array &$messages ): array {
		if ( ! is_array( $raw_sizes ) ) {
			if ( null !== $raw_sizes ) {
				$codes[]    = DerivativeRepositoryResult::CODE_INVALID_METADATA_IGNORED;
				$messages[] = 'Stored derivative sizes were invalid and were ignored.';
			}

			return array();
		}

		$sizes = array();

		foreach ( $raw_sizes as $size_name => $raw_size ) {
			if ( ! is_string( $size_name ) || ! is_array( $raw_size ) ) {
				$codes[]    = DerivativeRepositoryResult::CODE_INVALID_METADATA_IGNORED;
				$messages[] = 'A stored derivative size entry was invalid and was ignored.';
				continue;
			}

			$source = $this->sanitize_source( $raw_size['source'] ?? null );

			if ( null === $source ) {
				$codes[]    = DerivativeRepositoryResult::CODE_INVALID_METADATA_IGNORED;
				$messages[] = 'A stored derivative source entry was invalid and was ignored.';
				continue;
			}

			$formats = $this->sanitize_formats( $raw_size['formats'] ?? array(), $codes, $messages );

			if ( array() === $formats ) {
				continue;
			}

			$sizes[ substr( trim( $size_name ), 0, 64 ) ] = array(
				'source'  => $source,
				'formats' => $formats,
			);
		}

		return $sizes;
	}

	/**
	 * Sanitize source entry.
	 *
	 * @param mixed $raw_source Raw source.
	 * @return array<string,mixed>|null
	 */
	private function sanitize_source( $raw_source ): ?array {
		if ( ! is_array( $raw_source ) ) {
			return null;
		}

		$file = $this->safe_relative_path( $raw_source['file'] ?? '' );
		$mime = $this->safe_mime( $raw_source['mime'] ?? '' );

		if ( '' === $file || '' === $mime ) {
			return null;
		}

		return array(
			'file'   => $file,
			'width'  => max( 1, $this->int_value( $raw_source['width'] ?? 0 ) ),
			'height' => max( 1, $this->int_value( $raw_source['height'] ?? 0 ) ),
			'mime'   => $mime,
			'bytes'  => max( 0, $this->int_value( $raw_source['bytes'] ?? 0 ) ),
		);
	}

	/**
	 * Sanitize format entries.
	 *
	 * @param mixed    $raw_formats Raw formats.
	 * @param string[] $codes Codes.
	 * @param string[] $messages Messages.
	 * @return array<string,array<string,mixed>>
	 */
	private function sanitize_formats( $raw_formats, array &$codes, array &$messages ): array {
		if ( ! is_array( $raw_formats ) ) {
			$codes[]    = DerivativeRepositoryResult::CODE_INVALID_METADATA_IGNORED;
			$messages[] = 'Stored derivative formats were invalid and were ignored.';
			return array();
		}

		$formats = array();

		foreach ( $raw_formats as $format => $raw_format ) {
			if ( ! is_string( $format ) || ! is_array( $raw_format ) ) {
				$codes[]    = DerivativeRepositoryResult::CODE_INVALID_METADATA_IGNORED;
				$messages[] = 'A stored derivative format entry was invalid and was ignored.';
				continue;
			}

			$format = strtolower( trim( $format ) );

			if ( ! in_array( $format, AttachmentStatus::formats(), true ) ) {
				$codes[]    = DerivativeRepositoryResult::CODE_INVALID_METADATA_IGNORED;
				$messages[] = 'An unsupported derivative format entry was ignored.';
				continue;
			}

			$entry = $this->sanitize_format( $format, $raw_format );

			if ( null === $entry ) {
				$codes[]    = DerivativeRepositoryResult::CODE_INVALID_METADATA_IGNORED;
				$messages[] = 'An invalid derivative format entry was ignored.';
				continue;
			}

			$formats[ $format ] = $entry;
		}

		uksort(
			$formats,
			static function ( string $left, string $right ): int {
				return array_search( $left, AttachmentStatus::formats(), true ) <=> array_search( $right, AttachmentStatus::formats(), true );
			}
		);

		return $formats;
	}

	/**
	 * Sanitize one format entry.
	 *
	 * @param string              $format Format key.
	 * @param array<string,mixed> $raw_format Raw format entry.
	 * @return array<string,mixed>|null
	 */
	private function sanitize_format( string $format, array $raw_format ): ?array {
		$file = $this->safe_relative_path( $raw_format['file'] ?? '' );
		$mime = $this->safe_mime( $raw_format['mime'] ?? '' );

		if (
			'' === $file ||
			$this->expected_mime( $format ) !== $mime ||
			DerivativeManifest::FORMAT_STATUS_READY !== strtolower( trim( (string) ( $raw_format['status'] ?? '' ) ) )
		) {
			return null;
		}

		return array(
			'file'            => $file,
			'mime'            => $mime,
			'bytes'           => max( 0, $this->int_value( $raw_format['bytes'] ?? 0 ) ),
			'quality'         => max( 1, min( 100, $this->int_value( $raw_format['quality'] ?? 1 ) ) ),
			'savings_bytes'   => $this->nullable_int( $raw_format['savings_bytes'] ?? null ),
			'savings_percent' => $this->nullable_float( $raw_format['savings_percent'] ?? null ),
			'status'          => DerivativeManifest::FORMAT_STATUS_READY,
			'generated_at'    => max( 0, $this->int_value( $raw_format['generated_at'] ?? 0 ) ),
		);
	}

	/**
	 * Get expected target MIME.
	 *
	 * @param string $format Format.
	 * @return string
	 */
	public function expected_mime( string $format ): string {
		return AttachmentStatus::FORMAT_AVIF === $format ? 'image/avif' : 'image/webp';
	}

	/**
	 * Normalize safe relative path.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	public function safe_relative_path( $value ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$path = str_replace( '\\', '/', trim( (string) $value ) );

		if ( '' === $path || false !== strpos( $path, "\0" ) ) {
			return '';
		}

		if (
			1 === preg_match( '#^(?:[A-Za-z]:)?/#', $path ) ||
			1 === preg_match( '#^[A-Za-z]:#', $path ) ||
			false !== strpos( $path, '://' )
		) {
			return '';
		}

		foreach ( explode( '/', $path ) as $segment ) {
			if ( '' === $segment || '.' === $segment || '..' === $segment ) {
				return '';
			}
		}

		return $path;
	}

	/**
	 * Normalize MIME value.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	private function safe_mime( $value ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$mime = strtolower( trim( (string) $value ) );

		return 1 === preg_match( '#^[a-z0-9.+-]+/[a-z0-9.+-]+$#', $mime ) ? $mime : '';
	}

	/**
	 * Normalize int value.
	 *
	 * @param mixed $value Value.
	 * @return int
	 */
	private function int_value( $value ): int {
		return is_numeric( $value ) ? (int) $value : 0;
	}

	/**
	 * Normalize nullable int.
	 *
	 * @param mixed $value Value.
	 * @return int|null
	 */
	private function nullable_int( $value ): ?int {
		return is_numeric( $value ) ? (int) $value : null;
	}

	/**
	 * Normalize nullable float.
	 *
	 * @param mixed $value Value.
	 * @return float|null
	 */
	private function nullable_float( $value ): ?float {
		return is_numeric( $value ) ? (float) $value : null;
	}
}
