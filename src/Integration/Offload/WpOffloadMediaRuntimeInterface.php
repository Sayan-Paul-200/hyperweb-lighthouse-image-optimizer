<?php
/**
 * WP Offload Media runtime contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration\Offload;

/**
 * Isolates third-party offload runtime reads and remote-object operations.
 */
interface WpOffloadMediaRuntimeInterface {

	/**
	 * Get active plugin basenames for the current site/network context.
	 *
	 * @return string[]
	 */
	public function active_plugin_basenames(): array;

	/**
	 * Get the current full attachment URL.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return string|null
	 */
	public function attachment_url( int $attachment_id ): ?string;

	/**
	 * Get the current image URL for one named subsize.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $size_name Size name.
	 * @return string|null
	 */
	public function attachment_image_url( int $attachment_id, string $size_name ): ?string;

	/**
	 * Get attachment metadata.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return array<string,mixed>|null
	 */
	public function attachment_metadata( int $attachment_id ): ?array;

	/**
	 * Get the current attached local file path.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return string|null
	 */
	public function attached_file( int $attachment_id ): ?string;

	/**
	 * Get the current uploads base URL.
	 *
	 * @return string|null
	 */
	public function uploads_base_url(): ?string;

	/**
	 * Get the current uploads base directory.
	 *
	 * @return string|null
	 */
	public function uploads_base_dir(): ?string;

	/**
	 * Determine whether a local path exists.
	 *
	 * @param string $path Path.
	 * @return bool
	 */
	public function file_exists( string $path ): bool;

	/**
	 * Determine whether a local path is readable.
	 *
	 * @param string $path Path.
	 * @return bool
	 */
	public function is_readable( string $path ): bool;

	/**
	 * Download a remote source to a temporary local file.
	 *
	 * @param string $url Remote URL.
	 * @return string|null
	 */
	public function download_remote_file( string $url ): ?string;

	/**
	 * Delete one local temporary file.
	 *
	 * @param string $path Temporary path.
	 * @return bool
	 */
	public function delete_local_temp_file( string $path ): bool;

	/**
	 * Whether remote write/delete support is safely available.
	 *
	 * @return bool
	 */
	public function remote_operations_available(): bool;

	/**
	 * Publish one derivative to remote storage.
	 *
	 * @param int         $attachment_id Attachment ID.
	 * @param string      $relative_path Relative derivative path.
	 * @param string      $absolute_path Local absolute derivative path.
	 * @param string|null $mime_type MIME type.
	 * @param array<string,mixed> $context Safe context.
	 * @return bool
	 */
	public function push_derivative( int $attachment_id, string $relative_path, string $absolute_path, ?string $mime_type, array $context = array() ): bool;

	/**
	 * Delete one remote derivative.
	 *
	 * @param int                 $attachment_id Attachment ID.
	 * @param string              $relative_path Relative derivative path.
	 * @param array<string,mixed> $context Safe context.
	 * @return bool
	 */
	public function delete_derivative( int $attachment_id, string $relative_path, array $context = array() ): bool;
}
