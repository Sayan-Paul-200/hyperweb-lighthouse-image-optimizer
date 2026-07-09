<?php
/**
 * Settings defaults.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Settings;

/**
 * Provides the initial settings shape and validation metadata.
 */
final class SettingsSchema {

	public const TYPE_BOOLEAN     = 'boolean';
	public const TYPE_INTEGER     = 'integer';
	public const TYPE_FORMAT_LIST = 'format_list';

	public const GROUP_GENERAL  = 'general';
	public const GROUP_FORMATS  = 'formats_quality';
	public const GROUP_PROCESS  = 'processing';
	public const GROUP_DELIVERY = 'delivery';
	public const GROUP_LOGGING  = 'logging_cleanup';
	public const GROUP_ADVANCED = 'advanced_exclusions';
	public const GROUP_INTERNAL = 'internal';

	public const CAPABILITY_MANAGE_OPTIONS = 'manage_options';

	public const FORMAT_WEBP = 'webp';
	public const FORMAT_AVIF = 'avif';

	/**
	 * Current settings schema version.
	 *
	 * @var int
	 */
	public const SCHEMA_VERSION = 1;

	/**
	 * Get schema definitions for all initial settings.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function definitions(): array {
		return array(
			'schema_version'                  => self::integer_definition(
				self::SCHEMA_VERSION,
				self::GROUP_INTERNAL,
				'Settings schema version.',
				self::SCHEMA_VERSION,
				self::SCHEMA_VERSION,
				true
			),
			'setup_completed'                 => self::boolean_definition(
				false,
				self::GROUP_INTERNAL,
				'Whether initial setup has been completed.',
				true
			),
			'automatic_optimization'          => self::boolean_definition(
				false,
				self::GROUP_GENERAL,
				'Automatically queue new uploads for optimization.'
			),
			'media_library_controls'          => self::boolean_definition(
				true,
				self::GROUP_GENERAL,
				'Show per-attachment Media Library controls.'
			),
			'allow_attachment_exclusion'      => self::boolean_definition(
				true,
				self::GROUP_ADVANCED,
				'Allow administrators to exclude individual attachments.'
			),
			'delivery_enabled'                => self::boolean_definition(
				false,
				self::GROUP_DELIVERY,
				'Serve generated modern-image derivatives on the frontend.'
			),
			'enabled_formats'                 => self::format_list_definition(
				array( self::FORMAT_WEBP ),
				self::GROUP_FORMATS,
				'Modern image formats that may be generated.'
			),
			'format_preference'               => self::format_list_definition(
				array( self::FORMAT_AVIF, self::FORMAT_WEBP ),
				self::GROUP_FORMATS,
				'Preferred modern image format order.'
			),
			'webp_quality'                    => self::integer_definition(
				82,
				self::GROUP_FORMATS,
				'WebP conversion quality.',
				1,
				100
			),
			'avif_quality'                    => self::integer_definition(
				60,
				self::GROUP_FORMATS,
				'AVIF conversion quality.',
				1,
				100
			),
			'minimum_savings_percent'         => self::integer_definition(
				5,
				self::GROUP_FORMATS,
				'Minimum byte savings required to keep a generated derivative.',
				0,
				100
			),
			'optimize_full_size'              => self::boolean_definition(
				true,
				self::GROUP_PROCESS,
				'Optimize the full-size attachment file.'
			),
			'optimize_subsizes'               => self::boolean_definition(
				true,
				self::GROUP_PROCESS,
				'Optimize generated attachment subsizes.'
			),
			'skip_animated_gif'               => self::boolean_definition(
				true,
				self::GROUP_PROCESS,
				'Skip animated GIF sources.'
			),
			'max_retries'                     => self::integer_definition(
				3,
				self::GROUP_PROCESS,
				'Maximum retry attempts for transient failures.',
				0,
				10
			),
			'worker_time_budget'              => self::integer_definition(
				20,
				self::GROUP_PROCESS,
				'Worker time budget in seconds before continuation.',
				1,
				120
			),
			'queue_concurrency'               => self::integer_definition(
				1,
				self::GROUP_PROCESS,
				'Maximum optimization worker concurrency.',
				1,
				5
			),
			'log_retention_days'              => self::integer_definition(
				30,
				self::GROUP_LOGGING,
				'Number of days to retain plugin log rows.',
				1,
				3650
			),
			'delete_data_on_uninstall'        => self::boolean_definition(
				false,
				self::GROUP_LOGGING,
				'Delete plugin-owned data during uninstall.'
			),
			'delete_derivatives_on_uninstall' => self::boolean_definition(
				false,
				self::GROUP_LOGGING,
				'Delete plugin-owned derivative files during uninstall.'
			),
		);
	}

	/**
	 * Get unfiltered default settings from schema definitions.
	 *
	 * @return array<string,mixed>
	 */
	public static function base_defaults(): array {
		$defaults = array();

		foreach ( self::definitions() as $key => $definition ) {
			$defaults[ $key ] = $definition['default'];
		}

		return $defaults;
	}

	/**
	 * Get filtered and normalized default settings.
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults(): array {
		$defaults = self::base_defaults();

		if ( function_exists( 'apply_filters' ) ) {
			$filtered = \apply_filters( 'hwlio_default_settings', $defaults );

			if ( is_array( $filtered ) ) {
				$defaults = $filtered;
			}
		}

		return ( new SettingsSanitizer( self::definitions(), self::base_defaults() ) )
			->sanitize( $defaults )
			->settings();
	}

	/**
	 * Build a boolean setting definition.
	 *
	 * @param bool   $default_value Default value.
	 * @param string $group Settings group.
	 * @param string $description Description.
	 * @param bool   $internal Whether the setting is internal-only.
	 * @return array<string,mixed>
	 */
	private static function boolean_definition( bool $default_value, string $group, string $description, bool $internal = false ): array {
		return array(
			'type'        => self::TYPE_BOOLEAN,
			'default'     => $default_value,
			'group'       => $group,
			'capability'  => self::CAPABILITY_MANAGE_OPTIONS,
			'description' => $description,
			'sanitizer'   => 'boolean',
			'validation'  => 'boolean',
			'internal'    => $internal,
		);
	}

	/**
	 * Build an integer setting definition.
	 *
	 * @param int    $default_value Default value.
	 * @param string $group Settings group.
	 * @param string $description Description.
	 * @param int    $minimum Minimum value.
	 * @param int    $maximum Maximum value.
	 * @param bool   $internal Whether the setting is internal-only.
	 * @return array<string,mixed>
	 */
	private static function integer_definition(
		int $default_value,
		string $group,
		string $description,
		int $minimum,
		int $maximum,
		bool $internal = false
	): array {
		return array(
			'type'        => self::TYPE_INTEGER,
			'default'     => $default_value,
			'group'       => $group,
			'capability'  => self::CAPABILITY_MANAGE_OPTIONS,
			'description' => $description,
			'sanitizer'   => 'integer',
			'validation'  => 'integer_range',
			'minimum'     => $minimum,
			'maximum'     => $maximum,
			'internal'    => $internal,
		);
	}

	/**
	 * Build a format-list setting definition.
	 *
	 * @param string[] $default_value Default formats.
	 * @param string   $group Settings group.
	 * @param string   $description Description.
	 * @return array<string,mixed>
	 */
	private static function format_list_definition( array $default_value, string $group, string $description ): array {
		return array(
			'type'           => self::TYPE_FORMAT_LIST,
			'default'        => $default_value,
			'group'          => $group,
			'capability'     => self::CAPABILITY_MANAGE_OPTIONS,
			'description'    => $description,
			'sanitizer'      => 'format_list',
			'validation'     => 'allowed_values',
			'allowed_values' => array( self::FORMAT_WEBP, self::FORMAT_AVIF ),
			'internal'       => false,
		);
	}
}
