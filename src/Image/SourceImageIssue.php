<?php
/**
 * Source image collection issue.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Image;

/**
 * Represents a non-fatal source collection issue.
 */
final class SourceImageIssue {

	public const CODE_UPLOADS_UNAVAILABLE = 'uploads_unavailable';
	public const CODE_SOURCE_MISSING      = 'source_missing';
	public const CODE_SOURCE_UNREADABLE   = 'source_unreadable';
	public const CODE_OUTSIDE_UPLOADS     = 'skipped_outside_uploads';
	public const CODE_MALFORMED_METADATA  = 'malformed_metadata';
	public const CODE_UNSAFE_SOURCE_PATH  = 'unsafe_source_path';
	public const CODE_DUPLICATE_SOURCE    = 'duplicate_source';

	/**
	 * Attachment ID.
	 *
	 * @var int
	 */
	private $attachment_id;

	/**
	 * Size name.
	 *
	 * @var string
	 */
	private $size_name;

	/**
	 * Source role.
	 *
	 * @var string
	 */
	private $role;

	/**
	 * Issue code.
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
	 * Public-safe details.
	 *
	 * @var array<mixed>
	 */
	private $details;

	/**
	 * Create issue.
	 *
	 * @param int          $attachment_id Attachment ID.
	 * @param string       $size_name Size name.
	 * @param string       $role Source role.
	 * @param string       $code Issue code.
	 * @param string       $message Message.
	 * @param array<mixed> $details Public-safe details.
	 */
	public function __construct(
		int $attachment_id,
		string $size_name,
		string $role,
		string $code,
		string $message,
		array $details = array()
	) {
		$this->attachment_id = max( 0, $attachment_id );
		$this->size_name     = '' === trim( $size_name ) ? 'unknown' : trim( $size_name );
		$this->role          = in_array( $role, SourceImage::roles(), true ) ? $role : SourceImage::ROLE_SUBSIZE;
		$this->code          = $this->normalize_code( $code );
		$this->message       = '' === trim( $message ) ? 'Source image issue.' : $this->redact_absolute_paths( trim( $message ) );
		$this->details       = $this->sanitize_details( $details );
	}

	/**
	 * Get attachment ID.
	 *
	 * @return int
	 */
	public function attachment_id(): int {
		return $this->attachment_id;
	}

	/**
	 * Get size name.
	 *
	 * @return string
	 */
	public function size_name(): string {
		return $this->size_name;
	}

	/**
	 * Get role.
	 *
	 * @return string
	 */
	public function role(): string {
		return $this->role;
	}

	/**
	 * Get issue code.
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
	 * Get details.
	 *
	 * @return array<mixed>
	 */
	public function details(): array {
		return $this->details;
	}

	/**
	 * Serialize issue.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'attachment_id' => $this->attachment_id,
			'size_name'     => $this->size_name,
			'role'          => $this->role,
			'code'          => $this->code,
			'message'       => $this->message,
			'details'       => $this->details,
		);
	}

	/**
	 * Normalize code.
	 *
	 * @param string $code Code.
	 * @return string
	 */
	private function normalize_code( string $code ): string {
		$code = strtolower( trim( $code ) );
		$code = (string) preg_replace( '/[^a-z0-9_]/', '_', $code );

		return '' === $code ? self::CODE_MALFORMED_METADATA : substr( $code, 0, 64 );
	}

	/**
	 * Sanitize public issue details.
	 *
	 * @param array<mixed> $details Details.
	 * @return array<mixed>
	 */
	private function sanitize_details( array $details ): array {
		$sanitized = array();

		foreach ( $details as $key => $value ) {
			$detail_key = is_int( $key ) ? $key : $this->sanitize_key( (string) $key );

			if ( is_string( $value ) ) {
				$sanitized[ $detail_key ] = $this->redact_absolute_paths( $value );
			} elseif ( is_int( $value ) || is_float( $value ) || is_bool( $value ) || null === $value ) {
				$sanitized[ $detail_key ] = $value;
			}
		}

		return $sanitized;
	}

	/**
	 * Sanitize a detail key.
	 *
	 * @param string $key Key.
	 * @return string
	 */
	private function sanitize_key( string $key ): string {
		$key = strtolower( trim( $key ) );
		$key = (string) preg_replace( '/[^a-z0-9_\-]/', '_', $key );

		return '' === $key ? 'detail' : substr( $key, 0, 64 );
	}

	/**
	 * Redact absolute paths from a string.
	 *
	 * @param string $value Value.
	 * @return string
	 */
	private function redact_absolute_paths( string $value ): string {
		$value = (string) preg_replace(
			'/(^|[\s({\[=:])(?:[A-Za-z]:[\\\\\/][^\s<>"\')\]}]+)/',
			'$1[redacted_path]',
			$value
		);

		return (string) preg_replace(
			'/(^|[\s({\[=:])\/(?:[A-Za-z0-9._-]+\/?)+[^\s<>"\')\]}]*/',
			'$1[redacted_path]',
			$value
		);
	}
}
