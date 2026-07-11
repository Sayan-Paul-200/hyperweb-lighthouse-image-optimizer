<?php
/**
 * Lifecycle ownership policy.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Infrastructure;

/**
 * Defines plugin-owned lifecycle identifiers.
 */
final class LifecyclePolicy {

	public const ACTION_GROUP                      = 'hwlio';
	public const ACTION_OPTIMIZE_ATTACHMENT_FORMAT = 'hwlio_optimize_attachment_format';
	public const ACTION_CLEANUP_ATTACHMENT         = 'hwlio_cleanup_attachment';
	public const ACTION_RECONCILE_ATTACHMENT       = 'hwlio_reconcile_attachment';
	public const HOOK_ATTACHMENT_STATUS_REFRESH    = 'hwlio_attachment_status_refresh';

	public const META_DERIVATIVES = '_hwlio_derivatives';
	public const META_STATUS      = '_hwlio_status';
	public const META_EXCLUDED    = '_hwlio_excluded';
	public const META_LOCK        = '_hwlio_lock';

	public const OPTION_STATISTICS_CACHE = 'hwlio_statistics_cache';

	/**
	 * Get plugin-owned recurring maintenance hooks.
	 *
	 * @return string[]
	 */
	public static function maintenance_action_hooks(): array {
		return array(
			'hwlio_cleanup_logs',
			'hwlio_recover_stale_locks',
			'hwlio_reconcile_statistics',
		);
	}

	/**
	 * Get plugin-owned attachment job hooks.
	 *
	 * @return string[]
	 */
	public static function attachment_job_hooks(): array {
		return array(
			self::ACTION_OPTIMIZE_ATTACHMENT_FORMAT,
			self::ACTION_CLEANUP_ATTACHMENT,
			self::ACTION_RECONCILE_ATTACHMENT,
		);
	}

	/**
	 * Get plugin-owned option names.
	 *
	 * @return string[]
	 */
	public static function owned_options(): array {
		return array(
			Installer::OPTION_SETTINGS,
			Installer::OPTION_VERSION,
			Installer::OPTION_DB_VERSION,
			Installer::OPTION_ACTIVATION_STATE,
			self::OPTION_STATISTICS_CACHE,
		);
	}

	/**
	 * Get plugin-owned attachment meta keys.
	 *
	 * @return string[]
	 */
	public static function owned_attachment_meta_keys(): array {
		return array(
			self::META_DERIVATIVES,
			self::META_STATUS,
			self::META_EXCLUDED,
			self::META_LOCK,
		);
	}
}
