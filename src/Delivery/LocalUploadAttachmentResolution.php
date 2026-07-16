<?php
/**
 * Local uploads URL attachment-resolution result.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Delivery;

/**
 * Carries one safe local uploads URL attachment-resolution outcome.
 */
final class LocalUploadAttachmentResolution {

	public const CODE_RESOLVED_TRUSTED_MARKER = 'resolved_trusted_marker';
	public const CODE_RESOLVED_UPLOAD_URL     = 'resolved_upload_url';
	public const CODE_NOT_LOCAL_UPLOAD        = 'not_local_upload';
	public const CODE_UNRESOLVED              = 'unresolved';

	/**
	 * Attachment ID.
	 *
	 * @var int
	 */
	private $attachment_id;

	/**
	 * Stable result code.
	 *
	 * @var string
	 */
	private $code;

	/**
	 * Safe uploads-relative path when known.
	 *
	 * @var string
	 */
	private $relative_path;

	/**
	 * Create result.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $code Stable code.
	 * @param string $relative_path Safe uploads-relative path.
	 */
	public function __construct( int $attachment_id, string $code, string $relative_path = '' ) {
		$allowed = array(
			self::CODE_RESOLVED_TRUSTED_MARKER,
			self::CODE_RESOLVED_UPLOAD_URL,
			self::CODE_NOT_LOCAL_UPLOAD,
			self::CODE_UNRESOLVED,
		);

		$this->attachment_id = max( 0, $attachment_id );
		$this->code          = in_array( $code, $allowed, true ) ? $code : self::CODE_UNRESOLVED;
		$this->relative_path = $this->safe_relative_path( $relative_path );

		if ( ! $this->is_resolved() ) {
			$this->attachment_id = 0;
		}
	}

	/**
	 * Build a resolved trusted-marker result.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $relative_path Safe uploads-relative path.
	 * @return self
	 */
	public static function resolved_trusted_marker( int $attachment_id, string $relative_path = '' ): self {
		return new self( $attachment_id, self::CODE_RESOLVED_TRUSTED_MARKER, $relative_path );
	}

	/**
	 * Build a resolved uploads URL result.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $relative_path Safe uploads-relative path.
	 * @return self
	 */
	public static function resolved_upload_url( int $attachment_id, string $relative_path = '' ): self {
		return new self( $attachment_id, self::CODE_RESOLVED_UPLOAD_URL, $relative_path );
	}

	/**
	 * Build a not-local result.
	 *
	 * @return self
	 */
	public static function not_local_upload(): self {
		return new self( 0, self::CODE_NOT_LOCAL_UPLOAD );
	}

	/**
	 * Build an unresolved result.
	 *
	 * @param string $relative_path Safe uploads-relative path.
	 * @return self
	 */
	public static function unresolved( string $relative_path = '' ): self {
		return new self( 0, self::CODE_UNRESOLVED, $relative_path );
	}

	/**
	 * Whether the result resolved an attachment.
	 *
	 * @return bool
	 */
	public function is_resolved(): bool {
		return $this->attachment_id > 0
			&& in_array(
				$this->code,
				array( self::CODE_RESOLVED_TRUSTED_MARKER, self::CODE_RESOLVED_UPLOAD_URL ),
				true
			);
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
	 * Get stable result code.
	 *
	 * @return string
	 */
	public function code(): string {
		return $this->code;
	}

	/**
	 * Get safe uploads-relative path.
	 *
	 * @return string
	 */
	public function relative_path(): string {
		return $this->relative_path;
	}

	/**
	 * Serialize result safely.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'attachment_id' => $this->attachment_id,
			'code'          => $this->code,
			'relative_path' => $this->relative_path,
		);
	}

	/**
	 * Normalize a safe relative path.
	 *
	 * @param string $path Candidate path.
	 * @return string
	 */
	private function safe_relative_path( string $path ): string {
		$path = trim( str_replace( '\\', '/', $path ) );

		if ( '' === $path || false !== strpos( $path, "\0" ) ) {
			return '';
		}

		if ( 0 === strpos( $path, '/' ) || preg_match( '#^[a-z][a-z0-9+.-]*://#i', $path ) ) {
			return '';
		}

		$segments = explode( '/', $path );

		foreach ( $segments as $segment ) {
			if ( '' === $segment || '.' === $segment || '..' === $segment ) {
				return '';
			}
		}

		return implode( '/', $segments );
	}
}
