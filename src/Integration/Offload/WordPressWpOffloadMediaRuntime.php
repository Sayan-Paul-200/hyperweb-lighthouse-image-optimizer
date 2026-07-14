<?php
/**
 * WordPress-backed WP Offload Media runtime.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration\Offload;

/**
 * Uses conservative WordPress/core APIs and optional injected callables for remote object operations.
 */
final class WordPressWpOffloadMediaRuntime implements WpOffloadMediaRuntimeInterface {

	/**
	 * Optional remote push callback.
	 *
	 * @var callable|null
	 */
	private $push_callback;

	/**
	 * Optional remote delete callback.
	 *
	 * @var callable|null
	 */
	private $delete_callback;

	/**
	 * Optional download callback.
	 *
	 * @var callable|null
	 */
	private $download_callback;

	/**
	 * Create runtime.
	 *
	 * @param callable|null $push_callback Optional remote push callback.
	 * @param callable|null $delete_callback Optional remote delete callback.
	 * @param callable|null $download_callback Optional remote download callback.
	 */
	public function __construct( ?callable $push_callback = null, ?callable $delete_callback = null, ?callable $download_callback = null ) {
		$this->push_callback     = $push_callback;
		$this->delete_callback   = $delete_callback;
		$this->download_callback = $download_callback;
	}

	/**
	 * Get active plugin basenames.
	 *
	 * @return string[]
	 */
	public function active_plugin_basenames(): array {
		$active = array();

		if ( function_exists( 'get_option' ) ) {
			$option = get_option( 'active_plugins', array() );
			if ( is_array( $option ) ) {
				$active = array_merge( $active, array_values( array_filter( array_map( 'strval', $option ) ) ) );
			}
		}

		if ( function_exists( 'is_multisite' ) && \is_multisite() && function_exists( 'get_site_option' ) ) {
			$network = get_site_option( 'active_sitewide_plugins', array() );
			if ( is_array( $network ) ) {
				$active = array_merge( $active, array_keys( $network ) );
			}
		}

		return array_values( array_unique( $active ) );
	}

	/**
	 * Get the attachment URL.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return string|null
	 */
	public function attachment_url( int $attachment_id ): ?string {
		if ( $attachment_id < 1 || ! function_exists( 'wp_get_attachment_url' ) ) {
			return null;
		}

		$url = \wp_get_attachment_url( $attachment_id );

		return is_string( $url ) && '' !== trim( $url ) ? trim( $url ) : null;
	}

	/**
	 * Get the image URL for one named size.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $size_name Size name.
	 * @return string|null
	 */
	public function attachment_image_url( int $attachment_id, string $size_name ): ?string {
		if ( $attachment_id < 1 || '' === trim( $size_name ) || ! function_exists( 'wp_get_attachment_image_url' ) ) {
			return null;
		}

		$url = \wp_get_attachment_image_url( $attachment_id, $size_name );

		return is_string( $url ) && '' !== trim( $url ) ? trim( $url ) : null;
	}

	/**
	 * Get metadata.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return array<string,mixed>|null
	 */
	public function attachment_metadata( int $attachment_id ): ?array {
		if ( $attachment_id < 1 || ! function_exists( 'wp_get_attachment_metadata' ) ) {
			return null;
		}

		$metadata = \wp_get_attachment_metadata( $attachment_id );

		return is_array( $metadata ) ? $metadata : null;
	}

	/**
	 * Get attached file.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return string|null
	 */
	public function attached_file( int $attachment_id ): ?string {
		if ( $attachment_id < 1 || ! function_exists( 'get_attached_file' ) ) {
			return null;
		}

		$file = \get_attached_file( $attachment_id );

		return is_string( $file ) && '' !== trim( $file ) ? trim( $file ) : null;
	}

	/**
	 * Get uploads base URL.
	 *
	 * @return string|null
	 */
	public function uploads_base_url(): ?string {
		$uploads = $this->uploads();

		return is_array( $uploads ) && isset( $uploads['baseurl'] ) && is_string( $uploads['baseurl'] ) && '' !== trim( $uploads['baseurl'] )
			? trim( $uploads['baseurl'] )
			: null;
	}

	/**
	 * Get uploads base directory.
	 *
	 * @return string|null
	 */
	public function uploads_base_dir(): ?string {
		$uploads = $this->uploads();

		return is_array( $uploads ) && isset( $uploads['basedir'] ) && is_string( $uploads['basedir'] ) && '' !== trim( $uploads['basedir'] )
			? trim( $uploads['basedir'] )
			: null;
	}

	/**
	 * Whether a file exists.
	 *
	 * @param string $path Path.
	 * @return bool
	 */
	public function file_exists( string $path ): bool {
		return '' !== trim( $path ) && file_exists( $path );
	}

	/**
	 * Whether a file is readable.
	 *
	 * @param string $path Path.
	 * @return bool
	 */
	public function is_readable( string $path ): bool {
		return '' !== trim( $path ) && is_readable( $path );
	}

	/**
	 * Download one remote file to a local temporary file.
	 *
	 * @param string $url Remote URL.
	 * @return string|null
	 */
	public function download_remote_file( string $url ): ?string {
		if ( is_callable( $this->download_callback ) ) {
			$path = call_user_func( $this->download_callback, $url );

			return is_string( $path ) && '' !== trim( $path ) ? trim( $path ) : null;
		}

		if ( ! function_exists( 'download_url' ) ) {
			return null;
		}

		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.file_ops_download_url -- WordPress core temporary download helper is the supported path here.
		$downloaded = \download_url( $url );

		if ( is_wp_error( $downloaded ) || ! is_string( $downloaded ) || '' === trim( $downloaded ) ) {
			return null;
		}

		return trim( $downloaded );
	}

	/**
	 * Delete one temporary local file.
	 *
	 * @param string $path Path.
	 * @return bool
	 */
	public function delete_local_temp_file( string $path ): bool {
		$path = trim( $path );

		if ( '' === $path || ! file_exists( $path ) ) {
			return true;
		}

		if ( function_exists( 'wp_delete_file' ) ) {
			return (bool) \wp_delete_file( $path );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Fallback only when wp_delete_file() is unavailable.
		return unlink( $path );
	}

	/**
	 * Whether remote operations are available.
	 *
	 * @return bool
	 */
	public function remote_operations_available(): bool {
		return is_callable( $this->push_callback ) && is_callable( $this->delete_callback );
	}

	/**
	 * Push one derivative.
	 *
	 * @param int                 $attachment_id Attachment ID.
	 * @param string              $relative_path Relative path.
	 * @param string              $absolute_path Absolute path.
	 * @param string|null         $mime_type MIME type.
	 * @param array<string,mixed> $context Context.
	 * @return bool
	 */
	public function push_derivative( int $attachment_id, string $relative_path, string $absolute_path, ?string $mime_type, array $context = array() ): bool {
		if ( ! is_callable( $this->push_callback ) ) {
			return false;
		}

		try {
			return (bool) call_user_func( $this->push_callback, $attachment_id, $relative_path, $absolute_path, $mime_type, $context );
		} catch ( \Throwable $throwable ) {
			unset( $throwable );

			return false;
		}
	}

	/**
	 * Delete one remote derivative.
	 *
	 * @param int                 $attachment_id Attachment ID.
	 * @param string              $relative_path Relative path.
	 * @param array<string,mixed> $context Context.
	 * @return bool
	 */
	public function delete_derivative( int $attachment_id, string $relative_path, array $context = array() ): bool {
		if ( ! is_callable( $this->delete_callback ) ) {
			return false;
		}

		try {
			return (bool) call_user_func( $this->delete_callback, $attachment_id, $relative_path, $context );
		} catch ( \Throwable $throwable ) {
			unset( $throwable );

			return false;
		}
	}

	/**
	 * Read uploads configuration safely.
	 *
	 * @return array<string,mixed>|null
	 */
	private function uploads(): ?array {
		if ( ! function_exists( 'wp_upload_dir' ) ) {
			return null;
		}

		$uploads = \wp_upload_dir( null, false );

		if ( ! is_array( $uploads ) ) {
			return null;
		}

		if ( is_string( $uploads['error'] ) && '' !== trim( $uploads['error'] ) ) {
			return null;
		}

		return $uploads;
	}
}
