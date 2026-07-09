<?php
/**
 * Installer and upgrade routines.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Infrastructure;

use HyperWeb\LighthouseImageOptimizer\Settings\SettingsRepository;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsRepositoryInterface;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsResult;

/**
 * Initializes persistent plugin state and schema-owned storage.
 */
final class Installer {

	public const OPTION_SETTINGS         = SettingsRepository::OPTION_NAME;
	public const OPTION_VERSION          = 'hwlio_version';
	public const OPTION_DB_VERSION       = 'hwlio_db_version';
	public const OPTION_ACTIVATION_STATE = 'hwlio_activation_state';

	/**
	 * Option store.
	 *
	 * @var OptionStoreInterface
	 */
	private $options;

	/**
	 * Log table installer.
	 *
	 * @var LogTableInstallerInterface
	 */
	private $log_table_installer;

	/**
	 * Settings repository.
	 *
	 * @var SettingsRepositoryInterface
	 */
	private $settings_repository;

	/**
	 * Current plugin version.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Current database schema version.
	 *
	 * @var string
	 */
	private $db_version;

	/**
	 * Current settings/setup schema version.
	 *
	 * @var int
	 */
	private $schema_version;

	/**
	 * Clock callback for deterministic tests.
	 *
	 * @var callable|null
	 */
	private $clock;

	/**
	 * Build an installer backed by WordPress APIs.
	 *
	 * @param string $version Current plugin version.
	 * @param string $db_version Current database schema version.
	 * @param int    $schema_version Current settings/setup schema version.
	 * @return self
	 */
	public static function for_wordpress( string $version, string $db_version, int $schema_version ): self {
		$options = new WordPressOptionStore();

		return new self(
			$options,
			new DbDeltaLogTableInstaller(),
			$version,
			$db_version,
			$schema_version,
			null,
			SettingsRepository::for_options( $options )
		);
	}

	/**
	 * Create the installer.
	 *
	 * @param OptionStoreInterface             $options Option store.
	 * @param LogTableInstallerInterface       $log_table_installer Log table installer.
	 * @param string                           $version Current plugin version.
	 * @param string                           $db_version Current database schema version.
	 * @param int                              $schema_version Current settings/setup schema version.
	 * @param callable|null                    $clock Optional clock callback returning GMT datetime text.
	 * @param SettingsRepositoryInterface|null $settings_repository Optional settings repository.
	 */
	public function __construct(
		OptionStoreInterface $options,
		LogTableInstallerInterface $log_table_installer,
		string $version,
		string $db_version,
		int $schema_version,
		?callable $clock = null,
		?SettingsRepositoryInterface $settings_repository = null
	) {
		$this->options             = $options;
		$this->log_table_installer = $log_table_installer;
		$this->settings_repository = $settings_repository ?? SettingsRepository::for_options( $options );
		$this->version             = $version;
		$this->db_version          = $db_version;
		$this->schema_version      = $schema_version;
		$this->clock               = $clock;
	}

	/**
	 * Install or upgrade persistent plugin state.
	 *
	 * @return InstallerResult
	 */
	public function install(): InstallerResult {
		$settings_result = $this->install_settings();
		$version_result  = $this->install_versions();
		$table_result    = $this->log_table_installer->install();

		$setup_result = InstallerResult::combine(
			$settings_result,
			$version_result,
			$this->normalize_log_table_result( $table_result )
		);

		$state_result = $this->save_activation_state( $setup_result );

		return InstallerResult::combine( $setup_result, $state_result );
	}

	/**
	 * Determine whether stored state needs installation or upgrade.
	 *
	 * @return bool
	 */
	public function needs_upgrade(): bool {
		if ( $this->version !== (string) $this->options->get( self::OPTION_VERSION, '' ) ) {
			return true;
		}

		if ( $this->db_version !== (string) $this->options->get( self::OPTION_DB_VERSION, '' ) ) {
			return true;
		}

		$settings = $this->settings_repository->read();

		if ( ! $settings->is_valid() || $settings->has_changes() ) {
			return true;
		}

		$activation_state = $this->options->get( self::OPTION_ACTIVATION_STATE, null );

		if ( ! is_array( $activation_state ) ) {
			return true;
		}

		return (int) ( $activation_state['schema_version'] ?? 0 ) !== $this->schema_version;
	}

	/**
	 * Initialize or merge settings.
	 *
	 * @return InstallerResult
	 */
	private function install_settings(): InstallerResult {
		return $this->installer_result_from_settings( $this->settings_repository->ensure() );
	}

	/**
	 * Store plugin and DB versions.
	 *
	 * @return InstallerResult
	 */
	private function install_versions(): InstallerResult {
		$codes = array();

		if ( $this->set_option( self::OPTION_VERSION, $this->version, true ) ) {
			$codes[] = InstallerResult::CODE_VERSION_STORED;
		}

		if ( $this->set_option( self::OPTION_DB_VERSION, $this->db_version, true ) ) {
			$codes[] = InstallerResult::CODE_DB_VERSION_STORED;
		}

		if ( array() === $codes ) {
			$codes[] = InstallerResult::CODE_ALREADY_CURRENT;
		}

		return InstallerResult::success( $codes );
	}

	/**
	 * Save bounded setup diagnostics.
	 *
	 * @param InstallerResult $result Installer result.
	 * @return InstallerResult
	 */
	private function save_activation_state( InstallerResult $result ): InstallerResult {
		$state = array(
			'schema_version'       => $this->schema_version,
			'installed_version'    => $this->version,
			'installed_db_version' => $this->db_version,
			'setup_completed'      => false,
			'notice_pending'       => $result->has_warnings(),
			'status'               => $result->has_warnings() ? 'warning' : 'ok',
			'last_run_at_gmt'      => $this->now(),
			'notices'              => $this->notices_from_result( $result ),
		);

		$this->set_option( self::OPTION_ACTIVATION_STATE, $state, false );

		return InstallerResult::success( array( InstallerResult::CODE_ACTIVATION_STATE_SAVED ) );
	}

	/**
	 * Convert log table failures into non-fatal installer warnings.
	 *
	 * @param InstallerResult $table_result Log table installer result.
	 * @return InstallerResult
	 */
	private function normalize_log_table_result( InstallerResult $table_result ): InstallerResult {
		if ( $table_result->is_successful() ) {
			return $table_result;
		}

		return InstallerResult::warning(
			array( InstallerResult::CODE_LOG_TABLE_UNAVAILABLE ),
			$table_result->messages()
		);
	}

	/**
	 * Convert a settings repository result into installer result codes.
	 *
	 * @param SettingsResult $settings_result Settings result.
	 * @return InstallerResult
	 */
	private function installer_result_from_settings( SettingsResult $settings_result ): InstallerResult {
		if ( ! $settings_result->is_valid() ) {
			return InstallerResult::warning(
				array( InstallerResult::CODE_SETTINGS_REPAIRED ),
				array( 'Invalid settings were replaced with defaults.' )
			);
		}

		if ( $settings_result->has_code( SettingsResult::CODE_INITIALIZED ) ) {
			return InstallerResult::success( array( InstallerResult::CODE_SETTINGS_INITIALIZED ) );
		}

		if ( $settings_result->has_changes() ) {
			return InstallerResult::success( array( InstallerResult::CODE_SETTINGS_MERGED ) );
		}

		return InstallerResult::success( array( InstallerResult::CODE_ALREADY_CURRENT ) );
	}

	/**
	 * Build bounded notices from warning result messages.
	 *
	 * @param InstallerResult $result Installer result.
	 * @return array<int,array{code:string,message:string}>
	 */
	private function notices_from_result( InstallerResult $result ): array {
		if ( ! $result->has_warnings() ) {
			return array();
		}

		$notices  = array();
		$messages = $result->messages();

		foreach ( $result->codes() as $code ) {
			if ( InstallerResult::CODE_SETTINGS_REPAIRED !== $code && InstallerResult::CODE_LOG_TABLE_UNAVAILABLE !== $code ) {
				continue;
			}

			$notices[] = array(
				'code'    => $code,
				'message' => $this->notice_message( $code, $messages ),
			);
		}

		return array_slice( $notices, -5 );
	}

	/**
	 * Get a fallback diagnostic message for a code.
	 *
	 * @param string $code Stable result code.
	 * @return string
	 */
	private function message_for_code( string $code ): string {
		if ( InstallerResult::CODE_SETTINGS_REPAIRED === $code ) {
			return 'Invalid settings were replaced with defaults.';
		}

		if ( InstallerResult::CODE_LOG_TABLE_UNAVAILABLE === $code ) {
			return 'The log table is unavailable; setup will continue with minimal diagnostics.';
		}

		return 'Installer warning.';
	}

	/**
	 * Get a diagnostic message for a notice code.
	 *
	 * @param string   $code Stable result code.
	 * @param string[] $messages Result messages.
	 * @return string
	 */
	private function notice_message( string $code, array $messages ): string {
		if ( InstallerResult::CODE_LOG_TABLE_UNAVAILABLE === $code && array() !== $messages ) {
			return (string) end( $messages );
		}

		return $this->message_for_code( $code );
	}

	/**
	 * Add or update an option if needed.
	 *
	 * @param string $option Option name.
	 * @param mixed  $value Option value.
	 * @param bool   $autoload Whether WordPress should autoload the option.
	 * @return bool
	 */
	private function set_option( string $option, $value, bool $autoload ): bool {
		$current = $this->options->get( $option, null );

		if ( null === $current ) {
			if ( $this->options->add( $option, $value, $autoload ) ) {
				return true;
			}

			$this->options->update( $option, $value, $autoload );
			return true;
		}

		if ( $current !== $value ) {
			$this->options->update( $option, $value, $autoload );
			return true;
		}

		return false;
	}

	/**
	 * Get current GMT datetime text.
	 *
	 * @return string
	 */
	private function now(): string {
		if ( null !== $this->clock ) {
			return (string) call_user_func( $this->clock );
		}

		return gmdate( 'Y-m-d H:i:s' );
	}
}
