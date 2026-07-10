<?php
/**
 * Destination resolution result.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Image;

/**
 * Represents the outcome of resolving a derivative destination.
 */
final class DestinationResolutionResult {

	public const STATUS_RESOLVED = 'resolved';
	public const STATUS_INVALID  = 'invalid';

	public const CODE_RESOLVED                             = 'resolved';
	public const CODE_INVALID_TARGET_FORMAT                = 'invalid_target_format';
	public const CODE_UPLOADS_UNAVAILABLE                  = 'uploads_unavailable';
	public const CODE_UNSAFE_SOURCE_PATH                   = 'unsafe_source_path';
	public const CODE_SOURCE_OUTSIDE_UPLOADS               = 'source_outside_uploads';
	public const CODE_DESTINATION_OUTSIDE_UPLOADS          = 'destination_outside_uploads';
	public const CODE_TEMPORARY_OUTSIDE_UPLOADS            = 'temporary_outside_uploads';
	public const CODE_DESTINATION_COLLISION                = 'destination_collision';
	public const CODE_TEMPORARY_COLLISION                  = 'temporary_collision';
	public const CODE_DESTINATION_REALPATH_OUTSIDE_UPLOADS = 'destination_realpath_outside_uploads';
	public const CODE_TEMPORARY_REALPATH_OUTSIDE_UPLOADS   = 'temporary_realpath_outside_uploads';

	/**
	 * Source image.
	 *
	 * @var SourceImage|null
	 */
	private $source;

	/**
	 * Status.
	 *
	 * @var string
	 */
	private $status;

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
	 * Destination path.
	 *
	 * @var DestinationPath|null
	 */
	private $destination;

	/**
	 * Details.
	 *
	 * @var array<mixed>
	 */
	private $details;

	/**
	 * Create result.
	 *
	 * @param SourceImage|null     $source Source image.
	 * @param string               $status Status.
	 * @param string               $code Code.
	 * @param string               $message Message.
	 * @param DestinationPath|null $destination Destination path.
	 * @param array<mixed>         $details Details.
	 */
	private function __construct(
		?SourceImage $source,
		string $status,
		string $code,
		string $message,
		?DestinationPath $destination = null,
		array $details = array()
	) {
		$this->source      = $source;
		$this->status      = in_array( $status, self::statuses(), true ) ? $status : self::STATUS_INVALID;
		$this->code        = $this->normalize_code( $code );
		$this->message     = '' === trim( $message ) ? 'Destination resolution result.' : $this->redact_absolute_paths( trim( $message ) );
		$this->destination = $destination;
		$this->details     = $this->sanitize_details( $details );
	}

	/**
	 * Build resolved result.
	 *
	 * @param SourceImage     $source Source image.
	 * @param DestinationPath $destination Destination path.
	 * @return self
	 */
	public static function resolved( SourceImage $source, DestinationPath $destination ): self {
		return new self(
			$source,
			self::STATUS_RESOLVED,
			self::CODE_RESOLVED,
			'The derivative destination was resolved.',
			$destination
		);
	}

	/**
	 * Build invalid result.
	 *
	 * @param SourceImage|null $source Source image.
	 * @param string           $code Code.
	 * @param string           $message Message.
	 * @param array<mixed>     $details Details.
	 * @return self
	 */
	public static function invalid( ?SourceImage $source, string $code, string $message, array $details = array() ): self {
		return new self(
			$source,
			self::STATUS_INVALID,
			$code,
			$message,
			null,
			$details
		);
	}

	/**
	 * Get valid statuses.
	 *
	 * @return string[]
	 */
	public static function statuses(): array {
		return array(
			self::STATUS_RESOLVED,
			self::STATUS_INVALID,
		);
	}

	/**
	 * Get source image.
	 *
	 * @return SourceImage|null
	 */
	public function source(): ?SourceImage {
		return $this->source;
	}

	/**
	 * Get status.
	 *
	 * @return string
	 */
	public function status(): string {
		return $this->status;
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
	 * Get destination path.
	 *
	 * @return DestinationPath|null
	 */
	public function destination(): ?DestinationPath {
		return $this->destination;
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
	 * Whether resolution succeeded.
	 *
	 * @return bool
	 */
	public function is_resolved(): bool {
		return self::STATUS_RESOLVED === $this->status && $this->destination instanceof DestinationPath;
	}

	/**
	 * Serialize without exposing absolute paths.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'source'      => $this->source instanceof SourceImage ? $this->source->to_array() : null,
			'status'      => $this->status,
			'code'        => $this->code,
			'message'     => $this->message,
			'destination' => $this->destination instanceof DestinationPath ? $this->destination->to_array() : null,
			'details'     => $this->details,
		);
	}

	/**
	 * Normalize a code.
	 *
	 * @param string $code Code.
	 * @return string
	 */
	private function normalize_code( string $code ): string {
		$code = strtolower( trim( $code ) );
		$code = (string) preg_replace( '/[^a-z0-9_]/', '_', $code );

		return '' === $code ? self::CODE_DESTINATION_OUTSIDE_UPLOADS : substr( $code, 0, 64 );
	}

	/**
	 * Sanitize details.
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
	 * Sanitize detail key.
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
