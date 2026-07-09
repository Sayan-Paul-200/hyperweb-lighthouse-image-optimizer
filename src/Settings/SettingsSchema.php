<?php
/**
 * Settings defaults.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Settings;

/**
 * Provides the initial settings shape used during installation.
 */
final class SettingsSchema {

	/**
	 * Current settings schema version.
	 *
	 * @var int
	 */
	public const SCHEMA_VERSION = 1;

	/**
	 * Get default settings.
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults(): array {
		return array(
			'schema_version'                  => self::SCHEMA_VERSION,
			'setup_completed'                 => false,
			'automatic_optimization'          => false,
			'media_library_controls'          => true,
			'allow_attachment_exclusion'      => true,
			'delivery_enabled'                => false,
			'enabled_formats'                 => array( 'webp' ),
			'format_preference'               => array( 'avif', 'webp' ),
			'webp_quality'                    => 82,
			'avif_quality'                    => 60,
			'minimum_savings_percent'         => 5,
			'optimize_full_size'              => true,
			'optimize_subsizes'               => true,
			'skip_animated_gif'               => true,
			'max_retries'                     => 3,
			'worker_time_budget'              => 20,
			'queue_concurrency'               => 1,
			'log_retention_days'              => 30,
			'delete_data_on_uninstall'        => false,
			'delete_derivatives_on_uninstall' => false,
		);
	}
}
