<?php
/**
 * Fake WP Offload Media runtime.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Integration\Offload;

use HyperWeb\LighthouseImageOptimizer\Integration\Offload\WpOffloadMediaRuntimeInterface;

/**
 * Provides in-memory offload runtime facts for adapter tests.
 */
final class FakeWpOffloadMediaRuntime implements WpOffloadMediaRuntimeInterface {

	/**
	 * Active plugins.
	 *
	 * @var string[]
	 */
	public $active_plugins = array();

	/**
	 * Attachment URLs keyed by ID.
	 *
	 * @var array<int,string>
	 */
	public $attachment_urls = array();

	/**
	 * Image URLs keyed by attachment then size.
	 *
	 * @var array<int,array<string,string>>
	 */
	public $attachment_image_urls = array();

	/**
	 * Attachment metadata keyed by ID.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	public $metadata = array();

	/**
	 * Local attachment files keyed by ID.
	 *
	 * @var array<int,string>
	 */
	public $attached_files = array();

	/**
	 * Existing local files keyed by path.
	 *
	 * @var array<string,bool>
	 */
	public $existing_files = array();

	/**
	 * Readable local files keyed by path.
	 *
	 * @var array<string,bool>
	 */
	public $readable_files = array();

	/**
	 * Uploads base URL.
	 *
	 * @var string|null
	 */
	public $uploads_base_url = 'https://example.test/wp-content/uploads';

	/**
	 * Uploads base directory.
	 *
	 * @var string|null
	 */
	public $uploads_base_dir = 'C:/site/wp-content/uploads';

	/**
	 * Whether remote operations are supported.
	 *
	 * @var bool
	 */
	public $remote_operations_available = true;

	/**
	 * Temp download result.
	 *
	 * @var string|null
	 */
	public $downloaded_file;

	/**
	 * Pushed derivative records.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	public $pushes = array();

	/**
	 * Deleted derivative records.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	public $deletes = array();

	/**
	 * Temp file deletions.
	 *
	 * @var string[]
	 */
	public $deleted_temp_files = array();

	/**
	 * {@inheritDoc}
	 */
	public function active_plugin_basenames(): array {
		return $this->active_plugins;
	}

	/**
	 * {@inheritDoc}
	 */
	public function attachment_url( int $attachment_id ): ?string {
		return $this->attachment_urls[ $attachment_id ] ?? null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function attachment_image_url( int $attachment_id, string $size_name ): ?string {
		return $this->attachment_image_urls[ $attachment_id ][ $size_name ] ?? null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function attachment_metadata( int $attachment_id ): ?array {
		return $this->metadata[ $attachment_id ] ?? null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function attached_file( int $attachment_id ): ?string {
		return $this->attached_files[ $attachment_id ] ?? null;
	}

	/**
	 * {@inheritDoc}
	 */
	public function uploads_base_url(): ?string {
		return $this->uploads_base_url;
	}

	/**
	 * {@inheritDoc}
	 */
	public function uploads_base_dir(): ?string {
		return $this->uploads_base_dir;
	}

	/**
	 * {@inheritDoc}
	 */
	public function file_exists( string $path ): bool {
		return $this->existing_files[ $path ] ?? false;
	}

	/**
	 * {@inheritDoc}
	 */
	public function is_readable( string $path ): bool {
		return $this->readable_files[ $path ] ?? false;
	}

	/**
	 * {@inheritDoc}
	 */
	public function download_remote_file( string $url ): ?string {
		unset( $url );

		return $this->downloaded_file;
	}

	/**
	 * {@inheritDoc}
	 */
	public function delete_local_temp_file( string $path ): bool {
		$this->deleted_temp_files[] = $path;

		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function remote_operations_available(): bool {
		return $this->remote_operations_available;
	}

	/**
	 * {@inheritDoc}
	 */
	public function push_derivative( int $attachment_id, string $relative_path, string $absolute_path, ?string $mime_type, array $context = array() ): bool {
		$this->pushes[] = array(
			'attachment_id' => $attachment_id,
			'relative_path' => $relative_path,
			'absolute_path' => $absolute_path,
			'mime_type'     => $mime_type,
			'context'       => $context,
		);

		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function delete_derivative( int $attachment_id, string $relative_path, array $context = array() ): bool {
		$this->deletes[] = array(
			'attachment_id' => $attachment_id,
			'relative_path' => $relative_path,
			'context'       => $context,
		);

		return true;
	}
}
