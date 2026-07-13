<?php
/**
 * Statistics cache reader.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\Rest;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\OptionStoreInterface;
use HyperWeb\LighthouseImageOptimizer\Queue\StatisticsCache;

/**
 * Reads and normalizes the internal statistics cache option.
 */
final class StatisticsCacheReader {

	/**
	 * Option store.
	 *
	 * @var OptionStoreInterface
	 */
	private $options;

	/**
	 * Create the reader.
	 *
	 * @param OptionStoreInterface $options Option store.
	 */
	public function __construct( OptionStoreInterface $options ) {
		$this->options = $options;
	}

	/**
	 * Read the normalized cache payload.
	 *
	 * @return StatisticsCache
	 */
	public function read(): StatisticsCache {
		$raw = $this->options->get( LifecyclePolicy::OPTION_STATISTICS_CACHE, null );

		if ( ! is_array( $raw ) ) {
			return StatisticsCache::empty();
		}

		if (
			isset( $raw['schema_version'] ) &&
			is_numeric( $raw['schema_version'] ) &&
			StatisticsCache::SCHEMA_VERSION !== (int) $raw['schema_version']
		) {
			return StatisticsCache::empty();
		}

		return new StatisticsCache(
			isset( $raw['generated_at_gmt'] ) && is_scalar( $raw['generated_at_gmt'] ) ? (string) $raw['generated_at_gmt'] : '',
			isset( $raw['attachment_states'] ) && is_array( $raw['attachment_states'] ) ? $raw['attachment_states'] : array(),
			isset( $raw['totals'] ) && is_array( $raw['totals'] ) ? $raw['totals'] : array(),
			isset( $raw['formats'] ) && is_array( $raw['formats'] ) ? $raw['formats'] : array()
		);
	}
}
