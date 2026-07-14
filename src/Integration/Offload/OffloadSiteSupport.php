<?php
/**
 * Offload site support summary.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration\Offload;

/**
 * Describes whether the current site offload environment is active and safely supported.
 */
final class OffloadSiteSupport {

	public const CODE_INACTIVE    = 'offload_inactive';
	public const CODE_SUPPORTED   = 'offload_supported';
	public const CODE_UNSUPPORTED = 'offload_unsupported';

	/**
	 * Plugin-active flag.
	 *
	 * @var bool
	 */
	private $plugin_active;

	/**
	 * Supported flag.
	 *
	 * @var bool
	 */
	private $supported;

	/**
	 * Machine-readable code.
	 *
	 * @var string
	 */
	private $code;

	/**
	 * User-safe message.
	 *
	 * @var string
	 */
	private $message;

	/**
	 * Plugin basename.
	 *
	 * @var string
	 */
	private $plugin_basename;

	/**
	 * Plugin name.
	 *
	 * @var string
	 */
	private $plugin_name;

	/**
	 * Blocked operations.
	 *
	 * @var string[]
	 */
	private $blocked_operations;

	/**
	 * Create summary.
	 *
	 * @param bool     $plugin_active Whether the plugin is active.
	 * @param bool     $supported Whether compatibility is supported.
	 * @param string   $code Machine-readable code.
	 * @param string   $message User-safe message.
	 * @param string   $plugin_basename Plugin basename.
	 * @param string   $plugin_name Plugin display name.
	 * @param string[] $blocked_operations Blocked operations.
	 */
	public function __construct(
		bool $plugin_active,
		bool $supported,
		string $code,
		string $message,
		string $plugin_basename,
		string $plugin_name,
		array $blocked_operations = array()
	) {
		$this->plugin_active       = $plugin_active;
		$this->supported           = $supported;
		$this->code                = $this->normalize_code( $code );
		$this->message             = '' === trim( $message ) ? 'Offload support summary.' : trim( $message );
		$this->plugin_basename     = trim( $plugin_basename );
		$this->plugin_name         = trim( $plugin_name );
		$this->blocked_operations  = $this->normalize_operations( $blocked_operations );
	}

	/**
	 * Build the inactive summary.
	 *
	 * @return self
	 */
	public static function inactive(): self {
		return new self(
			false,
			false,
			self::CODE_INACTIVE,
			'No supported media offload plugin is active on this site.',
			WpOffloadMediaAdapter::PLUGIN_BASENAME,
			WpOffloadMediaAdapter::PLUGIN_NAME
		);
	}

	/**
	 * Build a supported summary.
	 *
	 * @return self
	 */
	public static function supported(): self {
		return new self(
			true,
			true,
			self::CODE_SUPPORTED,
			'WP Offload Media compatibility is active.',
			WpOffloadMediaAdapter::PLUGIN_BASENAME,
			WpOffloadMediaAdapter::PLUGIN_NAME
		);
	}

	/**
	 * Build an unsupported summary.
	 *
	 * @param string[] $blocked_operations Blocked operations.
	 * @param string   $message Optional message.
	 * @return self
	 */
	public static function unsupported(
		array $blocked_operations = array( 'automatic_optimization', 'bulk_queue', 'delivery_offloaded_attachments' ),
		string $message = 'WP Offload Media is active but offload write/delete compatibility could not be verified safely.'
	): self {
		return new self(
			true,
			false,
			self::CODE_UNSUPPORTED,
			$message,
			WpOffloadMediaAdapter::PLUGIN_BASENAME,
			WpOffloadMediaAdapter::PLUGIN_NAME,
			$blocked_operations
		);
	}

	/**
	 * Whether the offload plugin is active.
	 *
	 * @return bool
	 */
	public function plugin_active(): bool {
		return $this->plugin_active;
	}

	/**
	 * Whether compatibility is supported.
	 *
	 * @return bool
	 */
	public function supported(): bool {
		return $this->supported;
	}

	/**
	 * Whether bulk/offloaded work should be blocked.
	 *
	 * @return bool
	 */
	public function blocks_operations(): bool {
		return $this->plugin_active() && ! $this->supported();
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
			'pluginActive'       => $this->plugin_active(),
			'supported'          => $this->supported(),
			'code'               => $this->code(),
			'message'            => $this->message(),
			'pluginBasename'     => $this->plugin_basename,
			'pluginName'         => $this->plugin_name,
			'blockedOperations'  => $this->blocked_operations(),
		);
	}

	/**
	 * Normalize one code.
	 *
	 * @param string $code Code.
	 * @return string
	 */
	private function normalize_code( string $code ): string {
		$code = strtolower( trim( $code ) );
		$code = (string) preg_replace( '/[^a-z0-9_]/', '_', $code );
		$code = trim( $code, '_' );

		return '' === $code ? self::CODE_UNSUPPORTED : substr( $code, 0, 64 );
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
