<?php
/**
 * Per-attachment offload support facts.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration\Offload;

/**
 * Describes whether one attachment can be processed and delivered safely under offload.
 */
final class OffloadAttachmentSupport {

	public const MODE_LOCAL_NATIVE          = 'local_native';
	public const MODE_OFFLOADED_KEEP_LOCAL  = 'offloaded_keep_local';
	public const MODE_OFFLOADED_REMOTE_ONLY = 'offloaded_remote_only';
	public const MODE_UNSUPPORTED           = 'unsupported';

	/**
	 * Attachment ID.
	 *
	 * @var int
	 */
	private $attachment_id;

	/**
	 * Safe-support flag.
	 *
	 * @var bool
	 */
	private $supported;

	/**
	 * Attachment mode.
	 *
	 * @var string
	 */
	private $mode;

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
	 * Attachment URL.
	 *
	 * @var string|null
	 */
	private $attachment_url;

	/**
	 * Remote base URL.
	 *
	 * @var string|null
	 */
	private $remote_base_url;

	/**
	 * Blocked operations.
	 *
	 * @var string[]
	 */
	private $blocked_operations;

	/**
	 * Create support facts.
	 *
	 * @param int         $attachment_id Attachment ID.
	 * @param bool        $supported Whether the attachment is safe to process/deliver.
	 * @param string      $mode Mode.
	 * @param string      $code Code.
	 * @param string      $message Message.
	 * @param string|null $attachment_url Attachment URL.
	 * @param string|null $remote_base_url Remote base URL.
	 * @param string[]    $blocked_operations Blocked operations.
	 */
	public function __construct(
		int $attachment_id,
		bool $supported,
		string $mode,
		string $code,
		string $message,
		?string $attachment_url = null,
		?string $remote_base_url = null,
		array $blocked_operations = array()
	) {
		$this->attachment_id      = max( 0, $attachment_id );
		$this->supported          = $supported;
		$this->mode               = self::normalize_mode( $mode );
		$this->code               = $this->normalize_key( $code, OffloadSiteSupport::CODE_UNSUPPORTED );
		$this->message            = '' === trim( $message ) ? 'Offload attachment support.' : trim( $message );
		$this->attachment_url     = $this->normalize_nullable_string( $attachment_url );
		$this->remote_base_url    = $this->normalize_nullable_string( $remote_base_url );
		$this->blocked_operations = $this->normalize_operations( $blocked_operations );
	}

	/**
	 * Build a local-native attachment state.
	 *
	 * @param int         $attachment_id Attachment ID.
	 * @param string|null $attachment_url Attachment URL.
	 * @return self
	 */
	public static function local_native( int $attachment_id, ?string $attachment_url = null ): self {
		return new self(
			$attachment_id,
			true,
			self::MODE_LOCAL_NATIVE,
			OffloadSiteSupport::CODE_SUPPORTED,
			'The attachment is served from the local uploads directory.',
			$attachment_url,
			null
		);
	}

	/**
	 * Build a supported offloaded attachment state.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $mode Supported offload mode.
	 * @param string $attachment_url Attachment URL.
	 * @param string $remote_base_url Remote base URL.
	 * @return self
	 */
	public static function offloaded_supported(
		int $attachment_id,
		string $mode,
		string $attachment_url,
		string $remote_base_url
	): self {
		return new self(
			$attachment_id,
			true,
			$mode,
			OffloadSiteSupport::CODE_SUPPORTED,
			'The attachment is offloaded and compatibility is supported.',
			$attachment_url,
			$remote_base_url
		);
	}

	/**
	 * Build an unsupported attachment state.
	 *
	 * @param int         $attachment_id Attachment ID.
	 * @param string      $code Code.
	 * @param string      $message Message.
	 * @param string|null $attachment_url Attachment URL.
	 * @param string|null $remote_base_url Remote base URL.
	 * @param string[]    $blocked_operations Blocked operations.
	 * @return self
	 */
	public static function unsupported(
		int $attachment_id,
		string $code,
		string $message,
		?string $attachment_url = null,
		?string $remote_base_url = null,
		array $blocked_operations = array( 'optimize', 'retry', 'reconcile', 'delivery' )
	): self {
		return new self(
			$attachment_id,
			false,
			self::MODE_UNSUPPORTED,
			$code,
			$message,
			$attachment_url,
			$remote_base_url,
			$blocked_operations
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
	 * Whether this attachment is safely supported.
	 *
	 * @return bool
	 */
	public function is_supported(): bool {
		return $this->supported;
	}

	/**
	 * Whether the attachment is offloaded.
	 *
	 * @return bool
	 */
	public function is_offloaded(): bool {
		return in_array(
			$this->mode,
			array( self::MODE_OFFLOADED_KEEP_LOCAL, self::MODE_OFFLOADED_REMOTE_ONLY ),
			true
		);
	}

	/**
	 * Whether the attachment is remote-only.
	 *
	 * @return bool
	 */
	public function is_remote_only(): bool {
		return self::MODE_OFFLOADED_REMOTE_ONLY === $this->mode;
	}

	/**
	 * Get mode.
	 *
	 * @return string
	 */
	public function mode(): string {
		return $this->mode;
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
	 * Get attachment URL.
	 *
	 * @return string|null
	 */
	public function attachment_url(): ?string {
		return $this->attachment_url;
	}

	/**
	 * Get remote base URL.
	 *
	 * @return string|null
	 */
	public function remote_base_url(): ?string {
		return $this->remote_base_url;
	}

	/**
	 * Get blocked operations.
	 *
	 * @return string[]
	 */
	public function blocked_operations(): array {
		return $this->blocked_operations;
	}

	/**
	 * Serialize safely.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'attachmentId'      => $this->attachment_id(),
			'supported'         => $this->is_supported(),
			'mode'              => $this->mode(),
			'code'              => $this->code(),
			'message'           => $this->message(),
			'attachmentUrl'     => $this->attachment_url(),
			'remoteBaseUrl'     => $this->remote_base_url(),
			'blockedOperations' => $this->blocked_operations(),
		);
	}

	/**
	 * Normalize one mode.
	 *
	 * @param string $mode Mode.
	 * @return string
	 */
	public static function normalize_mode( string $mode ): string {
		$mode = strtolower( trim( $mode ) );

		return in_array(
			$mode,
			array(
				self::MODE_LOCAL_NATIVE,
				self::MODE_OFFLOADED_KEEP_LOCAL,
				self::MODE_OFFLOADED_REMOTE_ONLY,
				self::MODE_UNSUPPORTED,
			),
			true
		) ? $mode : self::MODE_UNSUPPORTED;
	}

	/**
	 * Normalize one key.
	 *
	 * @param string $value Value.
	 * @param string $fallback Fallback.
	 * @return string
	 */
	private function normalize_key( string $value, string $fallback ): string {
		$value = strtolower( trim( $value ) );
		$value = (string) preg_replace( '/[^a-z0-9_]/', '_', $value );
		$value = trim( $value, '_' );

		return '' === $value ? $fallback : substr( $value, 0, 64 );
	}

	/**
	 * Normalize one nullable string.
	 *
	 * @param string|null $value Value.
	 * @return string|null
	 */
	private function normalize_nullable_string( ?string $value ): ?string {
		return null === $value || '' === trim( $value ) ? null : trim( $value );
	}

	/**
	 * Normalize blocked operation keys.
	 *
	 * @param string[] $operations Operations.
	 * @return string[]
	 */
	private function normalize_operations( array $operations ): array {
		$normalized = array();

		foreach ( $operations as $operation ) {
			if ( ! is_scalar( $operation ) ) {
				continue;
			}

			$operation = strtolower( trim( (string) $operation ) );
			$operation = (string) preg_replace( '/[^a-z0-9_]/', '_', $operation );
			$operation = trim( $operation, '_' );

			if ( '' !== $operation ) {
				$normalized[] = substr( $operation, 0, 64 );
			}
		}

		return array_values( array_unique( $normalized ) );
	}
}
