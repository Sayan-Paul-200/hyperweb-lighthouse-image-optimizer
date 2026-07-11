<?php
/**
 * Attachment process request.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Attachment;

use HyperWeb\LighthouseImageOptimizer\Image\SourceImageCollection;

/**
 * Carries immutable inputs for one attachment-format processing pass.
 */
final class AttachmentProcessRequest {

	/**
	 * Attachment ID.
	 *
	 * @var int
	 */
	private $attachment_id;

	/**
	 * Target format.
	 *
	 * @var string
	 */
	private $target_format;

	/**
	 * Source cursor.
	 *
	 * @var int
	 */
	private $cursor;

	/**
	 * Worker time budget in seconds.
	 *
	 * @var int
	 */
	private $time_budget_seconds;

	/**
	 * Force flag.
	 *
	 * @var bool
	 */
	private $force;

	/**
	 * Collected sources.
	 *
	 * @var SourceImageCollection
	 */
	private $collection;

	/**
	 * Current attachment fingerprint.
	 *
	 * @var AttachmentFingerprint|null
	 */
	private $fingerprint;

	/**
	 * Create request.
	 *
	 * @param int                        $attachment_id Attachment ID.
	 * @param string                     $target_format Target format.
	 * @param int                        $cursor Source cursor.
	 * @param int                        $time_budget_seconds Time budget in seconds.
	 * @param bool                       $force Force flag.
	 * @param SourceImageCollection      $collection Source collection.
	 * @param AttachmentFingerprint|null $fingerprint Current fingerprint.
	 */
	public function __construct(
		int $attachment_id,
		string $target_format,
		int $cursor,
		int $time_budget_seconds,
		bool $force,
		SourceImageCollection $collection,
		?AttachmentFingerprint $fingerprint = null
	) {
		$this->attachment_id       = max( 0, $attachment_id );
		$this->target_format       = strtolower( trim( $target_format ) );
		$this->cursor              = max( 0, $cursor );
		$this->time_budget_seconds = max( 0, $time_budget_seconds );
		$this->force               = $force;
		$this->collection          = $collection;
		$this->fingerprint         = $fingerprint;
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
	 * Get target format.
	 *
	 * @return string
	 */
	public function target_format(): string {
		return $this->target_format;
	}

	/**
	 * Get source cursor.
	 *
	 * @return int
	 */
	public function cursor(): int {
		return $this->cursor;
	}

	/**
	 * Get time budget in seconds.
	 *
	 * @return int
	 */
	public function time_budget_seconds(): int {
		return $this->time_budget_seconds;
	}

	/**
	 * Whether this run is forced.
	 *
	 * @return bool
	 */
	public function force(): bool {
		return $this->force;
	}

	/**
	 * Get collected sources.
	 *
	 * @return SourceImageCollection
	 */
	public function collection(): SourceImageCollection {
		return $this->collection;
	}

	/**
	 * Get current fingerprint.
	 *
	 * @return AttachmentFingerprint|null
	 */
	public function fingerprint(): ?AttachmentFingerprint {
		return $this->fingerprint;
	}
}
