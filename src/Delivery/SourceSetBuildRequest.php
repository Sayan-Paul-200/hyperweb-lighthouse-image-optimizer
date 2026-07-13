<?php
/**
 * Source set build request.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Delivery;

/**
 * Carries one attachment's original WordPress srcset candidates and image meta.
 */
final class SourceSetBuildRequest {

	/**
	 * Attachment ID.
	 *
	 * @var int
	 */
	private $attachment_id;

	/**
	 * Original WordPress sources.
	 *
	 * @var array<int|string,mixed>
	 */
	private $original_sources;

	/**
	 * Image metadata.
	 *
	 * @var array<string,mixed>
	 */
	private $image_meta;

	/**
	 * Create request.
	 *
	 * @param int                     $attachment_id Attachment ID.
	 * @param array<int|string,mixed> $original_sources Original sources.
	 * @param array<string,mixed>     $image_meta Image metadata.
	 */
	public function __construct( int $attachment_id, array $original_sources, array $image_meta ) {
		$this->attachment_id    = max( 0, $attachment_id );
		$this->original_sources = $original_sources;
		$this->image_meta       = $image_meta;
	}

	/**
	 * Get attachment ID.
	 *
	 * @return int
	 */
	public function attachment_id(): int {
		return $this->attachment_id;
	}

	/**
	 * Get original WordPress sources.
	 *
	 * @return array<int|string,mixed>
	 */
	public function original_sources(): array {
		return $this->original_sources;
	}

	/**
	 * Get image metadata.
	 *
	 * @return array<string,mixed>
	 */
	public function image_meta(): array {
		return $this->image_meta;
	}

	/**
	 * Serialize request.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'attachment_id'    => $this->attachment_id,
			'original_sources' => $this->original_sources,
			'image_meta'       => $this->image_meta,
		);
	}
}
