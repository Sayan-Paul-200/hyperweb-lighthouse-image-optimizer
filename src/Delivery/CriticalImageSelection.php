<?php
/**
 * Critical image selection value object.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Delivery;

/**
 * Carries one normalized critical-image selection for the current request.
 */
final class CriticalImageSelection {

	/**
	 * Primary critical attachment ID.
	 *
	 * @var int|null
	 */
	private $primary_attachment_id;

	/**
	 * Critical attachment IDs.
	 *
	 * @var int[]
	 */
	private $critical_attachment_ids;

	/**
	 * Critical URLs.
	 *
	 * @var string[]
	 */
	private $critical_urls;

	/**
	 * Attachment ID explicitly selected for responsive preload.
	 *
	 * @var int|null
	 */
	private $preload_attachment_id;

	/**
	 * Create selection.
	 *
	 * @param int|null $primary_attachment_id Primary critical attachment ID.
	 * @param int[]    $critical_attachment_ids Critical attachment IDs.
	 * @param string[] $critical_urls Critical URLs.
	 * @param int|null $preload_attachment_id Preload attachment ID.
	 */
	public function __construct( ?int $primary_attachment_id, array $critical_attachment_ids, array $critical_urls, ?int $preload_attachment_id = null ) {
		$normalized_ids = array_values(
			array_unique(
				array_filter(
					array_map(
						static function ( $attachment_id ): int {
							return max( 0, (int) $attachment_id );
						},
						$critical_attachment_ids
					),
					static function ( int $attachment_id ): bool {
						return $attachment_id > 0;
					}
				)
			)
		);

		$this->primary_attachment_id = null !== $primary_attachment_id && $primary_attachment_id > 0
			? $primary_attachment_id
			: null;

		if ( null !== $this->primary_attachment_id && ! in_array( $this->primary_attachment_id, $normalized_ids, true ) ) {
			array_unshift( $normalized_ids, $this->primary_attachment_id );
		}

		$this->critical_attachment_ids = $normalized_ids;
		$this->critical_urls           = array_values(
			array_unique(
				array_filter(
					array_map(
						static function ( string $url ): string {
							return trim( $url );
						},
						$critical_urls
					),
					static function ( string $url ): bool {
						return '' !== $url;
					}
				)
			)
		);
		$this->preload_attachment_id   = null !== $preload_attachment_id && $preload_attachment_id > 0
			? $preload_attachment_id
			: null;
	}

	/**
	 * Get the primary critical attachment ID.
	 *
	 * @return int|null
	 */
	public function primary_attachment_id(): ?int {
		return $this->primary_attachment_id;
	}

	/**
	 * Get all critical attachment IDs.
	 *
	 * @return int[]
	 */
	public function critical_attachment_ids(): array {
		return $this->critical_attachment_ids;
	}

	/**
	 * Get all critical URLs.
	 *
	 * @return string[]
	 */
	public function critical_urls(): array {
		return $this->critical_urls;
	}

	/**
	 * Get the explicitly selected preload attachment ID.
	 *
	 * @return int|null
	 */
	public function preload_attachment_id(): ?int {
		return $this->preload_attachment_id;
	}

	/**
	 * Whether the given attachment is the primary critical image.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return bool
	 */
	public function is_primary_attachment( int $attachment_id ): bool {
		return null !== $this->primary_attachment_id && $attachment_id === $this->primary_attachment_id;
	}

	/**
	 * Whether the given attachment is in the critical set.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return bool
	 */
	public function is_critical_attachment( int $attachment_id ): bool {
		return $attachment_id > 0 && in_array( $attachment_id, $this->critical_attachment_ids, true );
	}

	/**
	 * Whether the given attachment is explicitly selected for responsive preload.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return bool
	 */
	public function should_preload_attachment( int $attachment_id ): bool {
		return null !== $this->preload_attachment_id && $attachment_id === $this->preload_attachment_id;
	}

	/**
	 * Whether the given URL matches a critical URL exactly after trimming.
	 *
	 * @param string $url URL.
	 * @return bool
	 */
	public function matches_url( string $url ): bool {
		return in_array( trim( $url ), $this->critical_urls, true );
	}

	/**
	 * Serialize the normalized selection.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'primary_attachment_id'   => $this->primary_attachment_id,
			'critical_attachment_ids' => $this->critical_attachment_ids,
			'critical_urls'           => $this->critical_urls,
			'preload_attachment_id'   => $this->preload_attachment_id,
		);
	}
}
