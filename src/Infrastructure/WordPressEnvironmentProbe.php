<?php
/**
 * WordPress-backed environment probe.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Infrastructure;

/**
 * Reads environment facts through WordPress and PHP APIs.
 */
final class WordPressEnvironmentProbe implements EnvironmentProbeInterface {

	/**
	 * Default image editor candidates used by WordPress.
	 *
	 * @var string[]
	 */
	private const DEFAULT_IMAGE_EDITORS = array(
		'WP_Image_Editor_Imagick',
		'WP_Image_Editor_GD',
	);

	/**
	 * Get the current PHP version.
	 *
	 * @return string
	 */
	public function php_version(): string {
		return PHP_VERSION;
	}

	/**
	 * Get the current WordPress version.
	 *
	 * @return string|null
	 */
	public function wordpress_version(): ?string {
		global $wp_version;

		return isset( $wp_version ) && is_string( $wp_version ) ? $wp_version : null;
	}

	/**
	 * Get active image editor candidate class names.
	 *
	 * @return string[]
	 */
	public function image_editor_candidates(): array {
		$candidates = self::DEFAULT_IMAGE_EDITORS;

		if ( function_exists( 'apply_filters' ) ) {
			$filtered = \apply_filters( 'wp_image_editors', $candidates );

			if ( is_array( $filtered ) ) {
				$candidates = $filtered;
			}
		}

		$normalized = array();

		foreach ( $candidates as $candidate ) {
			if ( is_string( $candidate ) && '' !== trim( $candidate ) && ! in_array( $candidate, $normalized, true ) ) {
				$normalized[] = $candidate;
			}
		}

		return $normalized;
	}

	/**
	 * Determine whether a class is available.
	 *
	 * @param string $class_name Class name.
	 * @return bool
	 */
	public function class_available( string $class_name ): bool {
		return class_exists( $class_name );
	}

	/**
	 * Determine whether WordPress recognizes a MIME type.
	 *
	 * @param string $mime_type MIME type.
	 * @return bool|null
	 */
	public function mime_type_recognized( string $mime_type ): ?bool {
		if ( ! function_exists( 'wp_get_mime_types' ) ) {
			return null;
		}

		return in_array( $mime_type, array_values( \wp_get_mime_types() ), true );
	}

	/**
	 * Determine whether WordPress image editors can encode a MIME type.
	 *
	 * @param string $mime_type MIME type.
	 * @return bool|null
	 */
	public function image_editor_supports_mime( string $mime_type ): ?bool {
		if ( ! function_exists( 'wp_image_editor_supports' ) ) {
			return null;
		}

		try {
			return (bool) \wp_image_editor_supports(
				array(
					'mime_type' => $mime_type,
				)
			);
		} catch ( \Throwable $throwable ) {
			unset( $throwable );

			return null;
		}
	}

	/**
	 * Get WordPress uploads data.
	 *
	 * @return array<string,mixed>|null
	 */
	public function uploads(): ?array {
		if ( ! function_exists( 'wp_upload_dir' ) ) {
			return null;
		}

		return \wp_upload_dir( null, false );
	}

	/**
	 * Determine whether a path is writable.
	 *
	 * @param string $path Filesystem path.
	 * @return bool|null
	 */
	public function is_writable( string $path ): ?bool {
		if ( '' === trim( $path ) || ! is_dir( $path ) ) {
			return false;
		}

		if ( function_exists( 'wp_is_writable' ) ) {
			return (bool) \wp_is_writable( $path );
		}

		return null;
	}

	/**
	 * Get raw PHP memory limit.
	 *
	 * @return string
	 */
	public function memory_limit(): string {
		return (string) ini_get( 'memory_limit' );
	}

	/**
	 * Get raw PHP max execution time.
	 *
	 * @return string
	 */
	public function max_execution_time(): string {
		return (string) ini_get( 'max_execution_time' );
	}

	/**
	 * Determine whether Action Scheduler is loaded.
	 *
	 * @return bool
	 */
	public function action_scheduler_loaded(): bool {
		return class_exists( 'ActionScheduler', false ) || function_exists( 'as_has_scheduled_action' );
	}

	/**
	 * Determine whether Action Scheduler is initialized.
	 *
	 * @return bool|null
	 */
	public function action_scheduler_initialized(): ?bool {
		if ( ! class_exists( 'ActionScheduler', false ) ) {
			return null;
		}

		if ( ! is_callable( array( 'ActionScheduler', 'is_initialized' ) ) ) {
			return null;
		}

		try {
			return (bool) call_user_func( array( 'ActionScheduler', 'is_initialized' ) );
		} catch ( \Throwable $throwable ) {
			unset( $throwable );

			return null;
		}
	}
}
