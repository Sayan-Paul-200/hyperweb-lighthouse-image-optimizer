<?php
/**
 * Byte occurrence report row.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Reporting;

/**
 * Carries one theoretical page-transfer row for a page inventory occurrence.
 */
final class ByteOccurrenceReport {

	/**
	 * Estimated status.
	 *
	 * @var string
	 */
	private $estimate_status;

	/**
	 * Estimate reason.
	 *
	 * @var string
	 */
	private $estimate_reason;

	/**
	 * Row payload.
	 *
	 * @var array<string,mixed>
	 */
	private $payload;

	/**
	 * Create row.
	 *
	 * @param array<string,mixed> $payload Row payload.
	 */
	public function __construct( array $payload ) {
		$status = isset( $payload['estimate_status'] ) && is_string( $payload['estimate_status'] ) ? strtolower( trim( $payload['estimate_status'] ) ) : 'unavailable';
		$reason = isset( $payload['estimate_reason'] ) && is_string( $payload['estimate_reason'] ) ? strtolower( trim( $payload['estimate_reason'] ) ) : 'unknown_reference';

		if ( ! in_array( $status, array( 'estimated', 'source_only', 'unavailable' ), true ) ) {
			$status = 'unavailable';
		}

		$this->estimate_status = $status;
		$this->estimate_reason = (string) preg_replace( '/[^a-z0-9_]/', '_', $reason );
		$this->payload         = $this->sanitize_payload( $payload );
		$this->payload['estimate_status'] = $this->estimate_status;
		$this->payload['estimate_reason'] = $this->estimate_reason;
	}

	/**
	 * Get estimate status.
	 *
	 * @return string
	 */
	public function estimate_status(): string {
		return $this->estimate_status;
	}

	/**
	 * Get download key when present.
	 *
	 * @return string
	 */
	public function download_key(): string {
		return isset( $this->payload['download_key'] ) && is_string( $this->payload['download_key'] ) ? $this->payload['download_key'] : '';
	}

	/**
	 * Get source bytes when present.
	 *
	 * @return int|null
	 */
	public function source_bytes(): ?int {
		return isset( $this->payload['source_bytes'] ) && is_int( $this->payload['source_bytes'] ) ? $this->payload['source_bytes'] : null;
	}

	/**
	 * Get modern bytes when present.
	 *
	 * @return int|null
	 */
	public function modern_bytes(): ?int {
		return isset( $this->payload['modern_bytes'] ) && is_int( $this->payload['modern_bytes'] ) ? $this->payload['modern_bytes'] : null;
	}

	/**
	 * Get savings bytes when present.
	 *
	 * @return int|null
	 */
	public function savings_bytes(): ?int {
		return isset( $this->payload['savings_bytes'] ) && is_int( $this->payload['savings_bytes'] ) ? $this->payload['savings_bytes'] : null;
	}

	/**
	 * Serialize row.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return $this->payload;
	}

	/**
	 * Sanitize row payload.
	 *
	 * @param array<string,mixed> $payload Raw payload.
	 * @return array<string,mixed>
	 */
	private function sanitize_payload( array $payload ): array {
		$sanitized = array(
			'occurrence_id'   => isset( $payload['occurrence_id'] ) && is_scalar( $payload['occurrence_id'] ) ? substr( trim( (string) $payload['occurrence_id'] ), 0, 64 ) : 'occ-0',
			'source'          => isset( $payload['source'] ) && is_scalar( $payload['source'] ) ? substr( trim( (string) $payload['source'] ), 0, 64 ) : 'core_content',
			'presentation'    => isset( $payload['presentation'] ) && is_scalar( $payload['presentation'] ) ? substr( trim( (string) $payload['presentation'] ), 0, 32 ) : PageInventoryItem::PRESENTATION_INLINE,
			'origin'          => isset( $payload['origin'] ) && is_scalar( $payload['origin'] ) ? substr( trim( (string) $payload['origin'] ), 0, 64 ) : PageInventoryItem::ORIGIN_UNKNOWN,
			'attachment_id'   => isset( $payload['attachment_id'] ) && is_numeric( $payload['attachment_id'] ) ? max( 0, (int) $payload['attachment_id'] ) : null,
			'url'             => isset( $payload['url'] ) && is_string( $payload['url'] ) && '' !== trim( $payload['url'] ) ? trim( $payload['url'] ) : null,
			'download_key'    => isset( $payload['download_key'] ) && is_string( $payload['download_key'] ) ? trim( $payload['download_key'] ) : '',
			'basis'           => isset( $payload['basis'] ) && is_string( $payload['basis'] ) ? substr( trim( $payload['basis'] ), 0, 64 ) : 'unavailable',
			'matched_size_name' => isset( $payload['matched_size_name'] ) && is_string( $payload['matched_size_name'] ) && '' !== trim( $payload['matched_size_name'] ) ? substr( trim( $payload['matched_size_name'] ), 0, 64 ) : null,
			'best_ready_format' => isset( $payload['best_ready_format'] ) && is_string( $payload['best_ready_format'] ) && '' !== trim( $payload['best_ready_format'] ) ? substr( trim( $payload['best_ready_format'] ), 0, 16 ) : null,
		);

		foreach ( array( 'source_bytes', 'modern_bytes', 'savings_bytes' ) as $key ) {
			if ( isset( $payload[ $key ] ) && is_numeric( $payload[ $key ] ) ) {
				$sanitized[ $key ] = max( 0, (int) $payload[ $key ] );
			} else {
				$sanitized[ $key ] = null;
			}
		}

		$sanitized['savings_percent'] = isset( $payload['savings_percent'] ) && is_numeric( $payload['savings_percent'] )
			? round( max( 0.0, (float) $payload['savings_percent'] ), 2 )
			: null;

		return $sanitized;
	}
}
