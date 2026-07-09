<?php
/**
 * Fake attachment source provider.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image;

use HyperWeb\LighthouseImageOptimizer\Image\AttachmentSourceProviderInterface;

/**
 * Provides deterministic attachment source data for tests.
 */
final class FakeAttachmentSourceProvider implements AttachmentSourceProviderInterface {

	/**
	 * Attached file.
	 *
	 * @var string|null
	 */
	public $attached_file;

	/**
	 * Metadata.
	 *
	 * @var array<string,mixed>|null
	 */
	public $metadata;

	/**
	 * Uploads base directory.
	 *
	 * @var string|null
	 */
	public $uploads_base_dir;

	/**
	 * Create provider.
	 *
	 * @param string|null              $attached_file Attached file.
	 * @param array<string,mixed>|null $metadata Metadata.
	 * @param string|null              $uploads_base_dir Uploads base directory.
	 */
	public function __construct(
		?string $attached_file = 'C:/site/wp-content/uploads/2026/07/hero.jpg',
		?array $metadata = null,
		?string $uploads_base_dir = 'C:/site/wp-content/uploads'
	) {
		$this->attached_file    = $attached_file;
		$this->metadata         = $metadata ?? array(
			'file'   => '2026/07/hero.jpg',
			'width'  => 2400,
			'height' => 1600,
			'sizes'  => array(),
		);
		$this->uploads_base_dir = $uploads_base_dir;
	}

	/**
	 * Get attached file.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return string|null
	 */
	public function attached_file( int $attachment_id ): ?string {
		unset( $attachment_id );

		return $this->attached_file;
	}

	/**
	 * Get metadata.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return array<string,mixed>|null
	 */
	public function metadata( int $attachment_id ): ?array {
		unset( $attachment_id );

		return $this->metadata;
	}

	/**
	 * Get uploads base.
	 *
	 * @return string|null
	 */
	public function uploads_base_dir(): ?string {
		return $this->uploads_base_dir;
	}
}
