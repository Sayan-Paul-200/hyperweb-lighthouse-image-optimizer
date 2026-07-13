<?php
/**
 * Statistics cache value object.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Queue;

use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentStatus;

/**
 * Carries a schema-versioned internal statistics cache payload.
 */
final class StatisticsCache {

	public const SCHEMA_VERSION = 1;

	/**
	 * Cache generation timestamp in GMT.
	 *
	 * @var string
	 */
	private $generated_at_gmt;

	/**
	 * Attachment state counts.
	 *
	 * @var array<string,int>
	 */
	private $attachment_states;

	/**
	 * Aggregate totals.
	 *
	 * @var array<string,int|float>
	 */
	private $totals;

	/**
	 * Per-format totals.
	 *
	 * @var array<string,array<string,int>>
	 */
	private $formats;

	/**
	 * Create statistics cache.
	 *
	 * @param string                          $generated_at_gmt Cache generation timestamp in GMT.
	 * @param array<string,int>               $attachment_states Attachment state counts.
	 * @param array<string,int|float>         $totals Aggregate totals.
	 * @param array<string,array<string,int>> $formats Per-format totals.
	 */
	public function __construct( string $generated_at_gmt, array $attachment_states, array $totals, array $formats ) {
		$this->generated_at_gmt  = $this->normalize_timestamp( $generated_at_gmt );
		$this->attachment_states = $this->normalize_attachment_states( $attachment_states );
		$this->totals            = $this->normalize_totals( $totals );
		$this->formats           = $this->normalize_formats( $formats );
	}

	/**
	 * Build an empty cache.
	 *
	 * @param string $generated_at_gmt Cache generation timestamp in GMT.
	 * @return self
	 */
	public static function empty( string $generated_at_gmt = '' ): self {
		return new self( $generated_at_gmt, array(), array(), array() );
	}

	/**
	 * Get generated timestamp.
	 *
	 * @return string
	 */
	public function generated_at_gmt(): string {
		return $this->generated_at_gmt;
	}

	/**
	 * Get attachment state counts.
	 *
	 * @return array<string,int>
	 */
	public function attachment_states(): array {
		return $this->attachment_states;
	}

	/**
	 * Get aggregate totals.
	 *
	 * @return array<string,int|float>
	 */
	public function totals(): array {
		return $this->totals;
	}

	/**
	 * Get per-format totals.
	 *
	 * @return array<string,array<string,int>>
	 */
	public function formats(): array {
		return $this->formats;
	}

	/**
	 * Serialize the cache.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'schema_version'    => self::SCHEMA_VERSION,
			'generated_at_gmt'  => $this->generated_at_gmt,
			'attachment_states' => $this->attachment_states,
			'totals'            => $this->totals,
			'formats'           => $this->formats,
		);
	}

	/**
	 * Normalize generated-at timestamp.
	 *
	 * @param string $generated_at_gmt Timestamp.
	 * @return string
	 */
	private function normalize_timestamp( string $generated_at_gmt ): string {
		$generated_at_gmt = trim( $generated_at_gmt );

		return '' === $generated_at_gmt ? gmdate( 'Y-m-d H:i:s' ) : substr( $generated_at_gmt, 0, 19 );
	}

	/**
	 * Normalize attachment state counts.
	 *
	 * @param array<string,int> $attachment_states Attachment state counts.
	 * @return array<string,int>
	 */
	private function normalize_attachment_states( array $attachment_states ): array {
		$normalized = array();

		foreach ( AttachmentStatus::states() as $state ) {
			$normalized[ $state ] = max( 0, (int) ( $attachment_states[ $state ] ?? 0 ) );
		}

		return $normalized;
	}

	/**
	 * Normalize aggregate totals.
	 *
	 * @param array<string,int|float> $totals Totals.
	 * @return array<string,int|float>
	 */
	private function normalize_totals( array $totals ): array {
		$normalized = array(
			'attachments_considered'             => 0,
			'attachments_with_ready_derivatives' => 0,
			'sources_represented'                => 0,
			'source_bytes'                       => 0,
			'generated_bytes'                    => 0,
			'savings_bytes'                      => 0,
			'savings_percent'                    => 0.0,
		);

		foreach ( $normalized as $key => $default ) {
			$value = $totals[ $key ] ?? $default;

			$normalized[ $key ] = 'savings_percent' === $key
				? round( max( 0, (float) $value ), 2 )
				: max( 0, (int) $value );
		}

		return $normalized;
	}

	/**
	 * Normalize per-format totals.
	 *
	 * @param array<string,array<string,int>> $formats Formats.
	 * @return array<string,array<string,int>>
	 */
	private function normalize_formats( array $formats ): array {
		$normalized = array();

		foreach ( AttachmentStatus::formats() as $format ) {
			$entry = $formats[ $format ] ?? array();

			$normalized[ $format ] = array(
				'sources_ready'   => max( 0, (int) ( $entry['sources_ready'] ?? 0 ) ),
				'source_bytes'    => max( 0, (int) ( $entry['source_bytes'] ?? 0 ) ),
				'generated_bytes' => max( 0, (int) ( $entry['generated_bytes'] ?? 0 ) ),
				'savings_bytes'   => max( 0, (int) ( $entry['savings_bytes'] ?? 0 ) ),
			);
		}

		return $normalized;
	}
}
