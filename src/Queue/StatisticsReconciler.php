<?php
/**
 * Statistics reconciler.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Queue;

use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentStatus;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifest;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeRepository;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\OptionStoreInterface;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\WordPressOptionStore;

/**
 * Builds and persists a conservative internal attachment statistics cache.
 */
final class StatisticsReconciler implements StatisticsReconcilerInterface {

	private const PAGE_SIZE = 100;

	/**
	 * Attachment scanner.
	 *
	 * @var AttachmentStatisticsScannerInterface
	 */
	private $scanner;

	/**
	 * Derivative repository.
	 *
	 * @var DerivativeRepository
	 */
	private $repository;

	/**
	 * Option store.
	 *
	 * @var OptionStoreInterface
	 */
	private $options;

	/**
	 * Clock callback returning GMT datetime text.
	 *
	 * @var callable|null
	 */
	private $clock;

	/**
	 * Build the WordPress-backed reconciler.
	 *
	 * @return self
	 */
	public static function for_wordpress(): self {
		return new self(
			new WordPressAttachmentStatisticsScanner(),
			DerivativeRepository::for_wordpress(),
			new WordPressOptionStore()
		);
	}

	/**
	 * Create the reconciler.
	 *
	 * @param AttachmentStatisticsScannerInterface $scanner Attachment scanner.
	 * @param DerivativeRepository                 $repository Derivative repository.
	 * @param OptionStoreInterface                 $options Option store.
	 * @param callable|null                        $clock Optional clock returning GMT datetime text.
	 */
	public function __construct(
		AttachmentStatisticsScannerInterface $scanner,
		DerivativeRepository $repository,
		OptionStoreInterface $options,
		?callable $clock = null
	) {
		$this->scanner    = $scanner;
		$this->repository = $repository;
		$this->options    = $options;
		$this->clock      = $clock;
	}

	/**
	 * Recalculate and persist the internal statistics cache.
	 *
	 * @return StatisticsReconciliationResult
	 */
	public function reconcile(): StatisticsReconciliationResult {
		$states            = $this->empty_state_counts();
		$formats           = $this->empty_format_totals();
		$totals            = $this->empty_totals();
		$metadata_warnings = false;
		$generated_at_gmt  = $this->now();

		try {
			for ( $page = 1; ; ++$page ) {
				$attachment_ids = $this->scanner->scan_page( $page, self::PAGE_SIZE );

				if ( array() === $attachment_ids ) {
					break;
				}

				foreach ( $attachment_ids as $attachment_id ) {
					$attachment_id = max( 0, (int) $attachment_id );

					if ( 0 === $attachment_id ) {
						continue;
					}

					++$totals['attachments_considered'];

					$read = $this->repository->read( $attachment_id );
					++$states[ $read->status()->state() ];

					if ( $read->has_warnings() ) {
						$metadata_warnings = true;
					}

					if ( $this->accumulate_manifest( $read->manifest(), $totals, $formats ) ) {
						++$totals['attachments_with_ready_derivatives'];
					}
				}

				if ( count( $attachment_ids ) < self::PAGE_SIZE ) {
					break;
				}
			}
		} catch ( \Throwable $throwable ) {
			return StatisticsReconciliationResult::failure(
				array( StatisticsReconciliationResult::CODE_SCAN_FAILED ),
				array( $throwable->getMessage() ),
				StatisticsCache::empty( $generated_at_gmt )
			);
		}

		$totals['savings_percent'] = 0 < $totals['source_bytes']
			? round( ( $totals['savings_bytes'] / $totals['source_bytes'] ) * 100, 2 )
			: 0.0;

		$cache = new StatisticsCache( $generated_at_gmt, $states, $totals, $formats );

		if ( ! $this->save_cache( $cache ) ) {
			return StatisticsReconciliationResult::failure(
				array( StatisticsReconciliationResult::CODE_WRITE_FAILED ),
				array( 'Statistics cache could not be saved.' ),
				$cache
			);
		}

		$codes    = array();
		$messages = array();

		if ( $metadata_warnings ) {
			$codes[]    = StatisticsReconciliationResult::CODE_METADATA_IGNORED;
			$messages[] = 'Some attachment metadata was invalid and was ignored during statistics reconciliation.';
		}

		return StatisticsReconciliationResult::success( $cache, $codes, $messages, $metadata_warnings );
	}

	/**
	 * Accumulate manifest totals.
	 *
	 * @param DerivativeManifest              $manifest Derivative manifest.
	 * @param array<string,int|float>         $totals Aggregate totals.
	 * @param array<string,array<string,int>> $formats Per-format totals.
	 * @return bool
	 */
	private function accumulate_manifest( DerivativeManifest $manifest, array &$totals, array &$formats ): bool {
		$attachment_has_ready = false;

		foreach ( $manifest->sizes() as $size ) {
			if ( ! isset( $size['source'], $size['formats'] ) || ! is_array( $size['source'] ) || ! is_array( $size['formats'] ) ) {
				continue;
			}

			$source_bytes = isset( $size['source']['bytes'] ) && is_numeric( $size['source']['bytes'] )
				? max( 0, (int) $size['source']['bytes'] )
				: 0;
			$best_bytes   = null;

			foreach ( AttachmentStatus::formats() as $format ) {
				if ( ! isset( $size['formats'][ $format ] ) || ! is_array( $size['formats'][ $format ] ) ) {
					continue;
				}

				$entry_bytes   = isset( $size['formats'][ $format ]['bytes'] ) && is_numeric( $size['formats'][ $format ]['bytes'] )
					? max( 0, (int) $size['formats'][ $format ]['bytes'] )
					: 0;
				$savings_bytes = isset( $size['formats'][ $format ]['savings_bytes'] ) && is_numeric( $size['formats'][ $format ]['savings_bytes'] )
					? max( 0, (int) $size['formats'][ $format ]['savings_bytes'] )
					: max( 0, $source_bytes - $entry_bytes );

				++$formats[ $format ]['sources_ready'];
				$formats[ $format ]['source_bytes']    += $source_bytes;
				$formats[ $format ]['generated_bytes'] += $entry_bytes;
				$formats[ $format ]['savings_bytes']   += $savings_bytes;

				if ( null === $best_bytes || $entry_bytes < $best_bytes ) {
					$best_bytes = $entry_bytes;
				}
			}

			if ( null === $best_bytes ) {
				continue;
			}

			$attachment_has_ready = true;
			++$totals['sources_represented'];
			$totals['source_bytes']    += $source_bytes;
			$totals['generated_bytes'] += $best_bytes;
			$totals['savings_bytes']   += max( 0, $source_bytes - $best_bytes );
		}

		return $attachment_has_ready;
	}

	/**
	 * Save the cache with autoload disabled.
	 *
	 * @param StatisticsCache $cache Cache.
	 * @return bool
	 */
	private function save_cache( StatisticsCache $cache ): bool {
		$current = $this->options->get( LifecyclePolicy::OPTION_STATISTICS_CACHE, null );

		if ( null === $current ) {
			if ( $this->options->add( LifecyclePolicy::OPTION_STATISTICS_CACHE, $cache->to_array(), false ) ) {
				return true;
			}
		}

		return $this->options->update( LifecyclePolicy::OPTION_STATISTICS_CACHE, $cache->to_array(), false );
	}

	/**
	 * Build empty attachment state counts.
	 *
	 * @return array<string,int>
	 */
	private function empty_state_counts(): array {
		return array_fill_keys( AttachmentStatus::states(), 0 );
	}

	/**
	 * Build empty aggregate totals.
	 *
	 * @return array<string,int|float>
	 */
	private function empty_totals(): array {
		return array(
			'attachments_considered'             => 0,
			'attachments_with_ready_derivatives' => 0,
			'sources_represented'                => 0,
			'source_bytes'                       => 0,
			'generated_bytes'                    => 0,
			'savings_bytes'                      => 0,
			'savings_percent'                    => 0.0,
		);
	}

	/**
	 * Build empty per-format totals.
	 *
	 * @return array<string,array<string,int>>
	 */
	private function empty_format_totals(): array {
		$formats = array();

		foreach ( AttachmentStatus::formats() as $format ) {
			$formats[ $format ] = array(
				'sources_ready'   => 0,
				'source_bytes'    => 0,
				'generated_bytes' => 0,
				'savings_bytes'   => 0,
			);
		}

		return $formats;
	}

	/**
	 * Get current GMT datetime text.
	 *
	 * @return string
	 */
	private function now(): string {
		if ( null !== $this->clock ) {
			return (string) call_user_func( $this->clock );
		}

		return gmdate( 'Y-m-d H:i:s' );
	}
}
