<?php
/**
 * Bootstrap requirement checks.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Infrastructure;

/**
 * Provides pure bootstrap requirement checks.
 */
final class Requirements {

	/**
	 * Determine whether a PHP version satisfies the minimum requirement.
	 *
	 * @param string $current_version Current PHP version.
	 * @param string $minimum_version Minimum supported PHP version.
	 * @return bool
	 */
	public static function supports_php( string $current_version, string $minimum_version ): bool {
		return version_compare( $current_version, $minimum_version, '>=' );
	}

	/**
	 * Determine whether a WordPress version satisfies the minimum requirement.
	 *
	 * @param string|null $current_version Current WordPress version.
	 * @param string      $minimum_version Minimum supported WordPress version.
	 * @return bool
	 */
	public static function supports_wordpress( ?string $current_version, string $minimum_version ): bool {
		if ( null === $current_version || '' === $current_version ) {
			return false;
		}

		return version_compare( $current_version, $minimum_version, '>=' );
	}

	/**
	 * Find missing required runtime files.
	 *
	 * Array keys are absolute paths. Array values are safe display labels.
	 *
	 * @param array<string,string> $required_files Required file paths and labels.
	 * @return string[]
	 */
	public static function missing_files( array $required_files ): array {
		$missing = array();

		foreach ( $required_files as $path => $label ) {
			if ( '' === $label ) {
				continue;
			}

			if ( ! file_exists( $path ) ) {
				$missing[] = $label;
			}
		}

		return $missing;
	}

	/**
	 * Evaluate all bootstrap requirements and return user-safe failure messages.
	 *
	 * @param string               $current_php Current PHP version.
	 * @param string|null          $current_wp Current WordPress version.
	 * @param string               $minimum_php Minimum supported PHP version.
	 * @param string               $minimum_wp Minimum supported WordPress version.
	 * @param array<string,string> $required_files Required file paths and labels.
	 * @return string[]
	 */
	public static function evaluate(
		string $current_php,
		?string $current_wp,
		string $minimum_php,
		string $minimum_wp,
		array $required_files
	): array {
		$failures = array();

		if ( ! self::supports_php( $current_php, $minimum_php ) ) {
			$failures[] = sprintf(
				'HyperWeb Lighthouse Image Optimizer requires PHP %1$s or higher. This site is running PHP %2$s.',
				$minimum_php,
				$current_php
			);
		}

		if ( ! self::supports_wordpress( $current_wp, $minimum_wp ) ) {
			$failures[] = sprintf(
				'HyperWeb Lighthouse Image Optimizer requires WordPress %1$s or higher. Current version: %2$s.',
				$minimum_wp,
				null === $current_wp || '' === $current_wp ? 'unknown' : $current_wp
			);
		}

		foreach ( self::missing_files( $required_files ) as $missing_file ) {
			$failures[] = sprintf(
				'HyperWeb Lighthouse Image Optimizer is missing required runtime file: %s.',
				$missing_file
			);
		}

		return $failures;
	}
}
