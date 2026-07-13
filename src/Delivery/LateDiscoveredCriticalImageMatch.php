<?php
/**
 * Late-discovered critical image match.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Delivery;

/**
 * Carries one uniquely matched late-discovered content image fragment.
 */
final class LateDiscoveredCriticalImageMatch {

	/**
	 * Attachment ID.
	 *
	 * @var int
	 */
	private $attachment_id;

	/**
	 * Original HTML fragment.
	 *
	 * @var string
	 */
	private $html;

	/**
	 * Create match.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $html Original HTML fragment.
	 */
	public function __construct( int $attachment_id, string $html ) {
		$this->attachment_id = max( 0, $attachment_id );
		$this->html          = $html;
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
	 * Get matched HTML fragment.
	 *
	 * @return string
	 */
	public function html(): string {
		return $this->html;
	}

	/**
	 * Serialize match.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'attachment_id' => $this->attachment_id,
			'html'          => $this->html,
		);
	}
}
