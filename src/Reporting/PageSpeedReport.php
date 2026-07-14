<?php
/**
 * PageSpeed Insights report value object.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Reporting;

/**
 * Carries one normalized PSI content report.
 */
final class PageSpeedReport {

	public const SOURCE_NONE  = 'none';
	public const SOURCE_CACHE = 'cache';
	public const SOURCE_LIVE  = 'live';

	/**
	 * Report payload.
	 *
	 * @var array<string,mixed>
	 */
	private $data;

	/**
	 * Create the report.
	 *
	 * @param int                     $content_id Content ID.
	 * @param string                  $strategy Strategy.
	 * @param string                  $source Source.
	 * @param string                  $result_code Result code.
	 * @param bool                    $integration_enabled Whether live PSI requests are enabled.
	 * @param string                  $public_url Safe public URL.
	 * @param string                  $requested_url Requested URL.
	 * @param string                  $final_url Final URL.
	 * @param string                  $fetched_at_gmt Fetch timestamp.
	 * @param PageSpeedMetrics|null   $lab_data Lab metrics.
	 * @param PageSpeedAuditSummary[] $image_audits Image audit summaries.
	 */
	public function __construct(
		int $content_id,
		string $strategy,
		string $source,
		string $result_code,
		bool $integration_enabled,
		string $public_url = '',
		string $requested_url = '',
		string $final_url = '',
		string $fetched_at_gmt = '',
		?PageSpeedMetrics $lab_data = null,
		array $image_audits = array()
	) {
		$audit_rows = array();

		foreach ( $image_audits as $audit ) {
			if ( $audit instanceof PageSpeedAuditSummary && $audit->is_valid() ) {
				$audit_rows[] = $audit;
			}
		}

		$this->data = array(
			'content_id'           => max( 0, $content_id ),
			'strategy'             => $this->normalize_strategy( $strategy ),
			'source'               => $this->normalize_source( $source ),
			'result_code'          => trim( $result_code ),
			'integration_enabled'  => $integration_enabled,
			'public_url'           => trim( $public_url ),
			'requested_url'        => trim( $requested_url ),
			'final_url'            => trim( $final_url ),
			'fetched_at_gmt'       => $this->timestamp( $fetched_at_gmt ),
			'available'            => $lab_data instanceof PageSpeedMetrics && $lab_data->has_values(),
			'lab_data'             => ( $lab_data ?? PageSpeedMetrics::empty() )->to_array(),
			'image_audits'         => array_map(
				static function ( PageSpeedAuditSummary $audit ): array {
					return $audit->to_array();
				},
				$audit_rows
			),
		);
	}

	/**
	 * Build an unavailable report.
	 *
	 * @param int    $content_id Content ID.
	 * @param string $strategy Strategy.
	 * @param string $result_code Result code.
	 * @param bool   $integration_enabled Whether live PSI requests are enabled.
	 * @param string $public_url Public URL.
	 * @return self
	 */
	public static function unavailable(
		int $content_id,
		string $strategy,
		string $result_code,
		bool $integration_enabled,
		string $public_url = ''
	): self {
		return new self(
			$content_id,
			$strategy,
			self::SOURCE_NONE,
			$result_code,
			$integration_enabled,
			$public_url
		);
	}

	/**
	 * Build a report from stored payload data.
	 *
	 * @param int                 $content_id Content ID.
	 * @param string              $strategy Strategy.
	 * @param array<string,mixed> $payload Stored payload.
	 * @param bool                $integration_enabled Whether live PSI requests are enabled.
	 * @param string              $public_url Safe public URL.
	 * @return self
	 */
	public static function from_storage(
		int $content_id,
		string $strategy,
		array $payload,
		bool $integration_enabled,
		string $public_url = ''
	): self {
		$audit_rows = array();

		if ( isset( $payload['image_audits'] ) && is_array( $payload['image_audits'] ) ) {
			foreach ( $payload['image_audits'] as $audit ) {
				if ( is_array( $audit ) ) {
					$audit_rows[] = new PageSpeedAuditSummary( $audit );
				}
			}
		}

		return new self(
			$content_id,
			$strategy,
			self::SOURCE_CACHE,
			'pagespeed_cached_result',
			$integration_enabled,
			$public_url,
			isset( $payload['requested_url'] ) && is_string( $payload['requested_url'] ) ? $payload['requested_url'] : '',
			isset( $payload['final_url'] ) && is_string( $payload['final_url'] ) ? $payload['final_url'] : '',
			isset( $payload['fetched_at_gmt'] ) && is_string( $payload['fetched_at_gmt'] ) ? $payload['fetched_at_gmt'] : '',
			new PageSpeedMetrics( isset( $payload['lab_data'] ) && is_array( $payload['lab_data'] ) ? $payload['lab_data'] : array() ),
			$audit_rows
		);
	}

	/**
	 * Serialize the public payload.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return $this->data;
	}

	/**
	 * Serialize the storage payload.
	 *
	 * @return array<string,mixed>
	 */
	public function to_storage_array(): array {
		return array(
			'requested_url'  => $this->requested_url(),
			'final_url'      => $this->final_url(),
			'fetched_at_gmt' => $this->fetched_at_gmt(),
			'lab_data'       => $this->lab_data(),
			'image_audits'   => $this->image_audits(),
		);
	}

	/**
	 * Get the content ID.
	 *
	 * @return int
	 */
	public function content_id(): int {
		return (int) $this->data['content_id'];
	}

	/**
	 * Get the strategy.
	 *
	 * @return string
	 */
	public function strategy(): string {
		return (string) $this->data['strategy'];
	}

	/**
	 * Get the requested URL.
	 *
	 * @return string
	 */
	public function requested_url(): string {
		return (string) $this->data['requested_url'];
	}

	/**
	 * Get the final URL.
	 *
	 * @return string
	 */
	public function final_url(): string {
		return (string) $this->data['final_url'];
	}

	/**
	 * Get the fetch timestamp.
	 *
	 * @return string
	 */
	public function fetched_at_gmt(): string {
		return (string) $this->data['fetched_at_gmt'];
	}

	/**
	 * Get lab-data metrics.
	 *
	 * @return array<string,mixed>
	 */
	public function lab_data(): array {
		return is_array( $this->data['lab_data'] ) ? $this->data['lab_data'] : array();
	}

	/**
	 * Get image audit summaries.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function image_audits(): array {
		return is_array( $this->data['image_audits'] ) ? $this->data['image_audits'] : array();
	}

	/**
	 * Normalize strategy.
	 *
	 * @param string $strategy Raw strategy.
	 * @return string
	 */
	private function normalize_strategy( string $strategy ): string {
		$strategy = strtolower( trim( $strategy ) );

		return in_array( $strategy, array( 'mobile', 'desktop' ), true ) ? $strategy : 'mobile';
	}

	/**
	 * Normalize source.
	 *
	 * @param string $source Raw source.
	 * @return string
	 */
	private function normalize_source( string $source ): string {
		$source = strtolower( trim( $source ) );

		return in_array( $source, array( self::SOURCE_NONE, self::SOURCE_CACHE, self::SOURCE_LIVE ), true )
			? $source
			: self::SOURCE_NONE;
	}

	/**
	 * Normalize a GMT timestamp.
	 *
	 * @param string $value Raw timestamp.
	 * @return string
	 */
	private function timestamp( string $value ): string {
		return 1 === preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', trim( $value ) ) ? trim( $value ) : '';
	}
}
