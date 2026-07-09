<?php
/**
 * Settings operation result.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Settings;

/**
 * Captures sanitized settings and stable operation metadata.
 */
final class SettingsResult {

	public const CODE_LOADED               = 'settings_loaded';
	public const CODE_INITIALIZED          = 'settings_initialized';
	public const CODE_SAVED                = 'settings_saved';
	public const CODE_REPAIRED             = 'settings_repaired';
	public const CODE_MERGED               = 'settings_merged';
	public const CODE_ALREADY_CURRENT      = 'already_current';
	public const CODE_INVALID_REPAIRED     = 'invalid_settings_repaired';
	public const CODE_SANITIZED            = 'settings_sanitized';
	public const CODE_UNKNOWN_KEYS_DROPPED = 'unknown_keys_dropped';

	/**
	 * Sanitized settings.
	 *
	 * @var array<string,mixed>
	 */
	private $settings;

	/**
	 * Whether source settings were structurally valid.
	 *
	 * @var bool
	 */
	private $valid;

	/**
	 * Whether source settings differ from sanitized settings.
	 *
	 * @var bool
	 */
	private $changed;

	/**
	 * Stable result codes.
	 *
	 * @var string[]
	 */
	private $codes;

	/**
	 * Human-readable messages.
	 *
	 * @var string[]
	 */
	private $messages;

	/**
	 * Create a settings result.
	 *
	 * @param array<string,mixed> $settings Sanitized settings.
	 * @param bool                $valid Whether source settings were structurally valid.
	 * @param bool                $changed Whether sanitized settings changed the source.
	 * @param string[]            $codes Stable result codes.
	 * @param string[]            $messages Human-readable messages.
	 */
	public function __construct(
		array $settings,
		bool $valid,
		bool $changed,
		array $codes = array(),
		array $messages = array()
	) {
		$this->settings = $settings;
		$this->valid    = $valid;
		$this->changed  = $changed;
		$this->codes    = array_values( array_unique( $codes ) );
		$this->messages = array_values( $messages );
	}

	/**
	 * Get sanitized settings.
	 *
	 * @return array<string,mixed>
	 */
	public function settings(): array {
		return $this->settings;
	}

	/**
	 * Determine whether source settings were structurally valid.
	 *
	 * @return bool
	 */
	public function is_valid(): bool {
		return $this->valid;
	}

	/**
	 * Determine whether sanitized settings changed the source.
	 *
	 * @return bool
	 */
	public function has_changes(): bool {
		return $this->changed;
	}

	/**
	 * Get result codes.
	 *
	 * @return string[]
	 */
	public function codes(): array {
		return $this->codes;
	}

	/**
	 * Get messages.
	 *
	 * @return string[]
	 */
	public function messages(): array {
		return $this->messages;
	}

	/**
	 * Determine whether a code is present.
	 *
	 * @param string $code Stable result code.
	 * @return bool
	 */
	public function has_code( string $code ): bool {
		return in_array( $code, $this->codes, true );
	}

	/**
	 * Return a copy with additional metadata.
	 *
	 * @param bool     $valid Whether source settings were structurally valid.
	 * @param bool     $changed Whether sanitized settings changed the source.
	 * @param string[] $codes Stable result codes.
	 * @param string[] $messages Human-readable messages.
	 * @return self
	 */
	public function with_metadata( bool $valid, bool $changed, array $codes, array $messages = array() ): self {
		return new self(
			$this->settings,
			$valid,
			$changed,
			array_merge( $this->codes, $codes ),
			array_merge( $this->messages, $messages )
		);
	}
}
