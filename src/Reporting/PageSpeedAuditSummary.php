<?php
/**
 * PageSpeed Insights audit summary value object.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Reporting;

/**
 * Carries one normalized PSI audit summary.
 */
final class PageSpeedAuditSummary {

	/**
	 * Stable audit fields.
	 *
	 * @var array<string,mixed>
	 */
	private $data;

	/**
	 * Create the audit summary.
	 *
	 * @param array<string,mixed> $data Raw audit data.
	 */
	public function __construct( array $data ) {
		$id = isset( $data['id'] ) && is_string( $data['id'] ) ? trim( $data['id'] ) : '';

		$this->data = array(
			'id'            => substr( preg_replace( '/[^a-z0-9_\-]/i', '-', $id ) ?? '', 0, 64 ),
			'title'         => isset( $data['title'] ) && is_string( $data['title'] ) ? trim( $data['title'] ) : '',
			'score'         => $this->score( $data['score'] ?? null ),
			'numeric_value' => is_numeric( $data['numeric_value'] ?? null ) ? round( (float) $data['numeric_value'], 2 ) : null,
			'display_value' => isset( $data['display_value'] ) && is_string( $data['display_value'] ) ? trim( $data['display_value'] ) : '',
		);
	}

	/**
	 * Whether the audit has a stable identifier.
	 *
	 * @return bool
	 */
	public function is_valid(): bool {
		return '' !== $this->data['id'];
	}

	/**
	 * Serialize the audit.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return $this->data;
	}

	/**
	 * Normalize one score-like field.
	 *
	 * @param mixed $value Raw value.
	 * @return int|null
	 */
	private function score( $value ): ?int {
		if ( ! is_numeric( $value ) ) {
			return null;
		}

		$numeric = (float) $value;
		$numeric = $numeric <= 1 ? $numeric * 100 : $numeric;

		return max( 0, min( 100, (int) round( $numeric ) ) );
	}
}
