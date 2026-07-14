<?php
/**
 * CLI log pruning runner.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Cli;

use HyperWeb\LighthouseImageOptimizer\Logging\LogPruner;
use HyperWeb\LighthouseImageOptimizer\Logging\LogPrunerInterface;

/**
 * Runs bounded log pruning batches to completion for WP-CLI.
 */
final class CliLogPruneService {

	/**
	 * Log pruner.
	 *
	 * @var LogPrunerInterface
	 */
	private $pruner;

	/**
	 * Create the service.
	 *
	 * @param LogPrunerInterface $pruner Log pruner.
	 */
	public function __construct( LogPrunerInterface $pruner ) {
		$this->pruner = $pruner;
	}

	/**
	 * Prune old logs to completion.
	 *
	 * @param callable|null $progress Optional progress callback.
	 * @return CliOperationResult
	 */
	public function prune( ?callable $progress = null ): CliOperationResult {
		$batches      = 0;
		$rows_removed = 0;

		do {
			$removed = $this->pruner->prune();
			++$batches;
			$rows_removed += max( 0, $removed );

			if ( null !== $progress ) {
				call_user_func(
					$progress,
					sprintf(
						'Log prune progress: batch=%d removed=%d total_removed=%d',
						$batches,
						max( 0, $removed ),
						$rows_removed
					)
				);
			}
		} while ( LogPruner::BATCH_SIZE <= $removed );

		return CliOperationResult::success(
			'prune-logs',
			array(
				'progress' => array(
					'batches'  => $batches,
					'complete' => true,
				),
				'summary'  => array(
					'rows_removed' => $rows_removed,
					'batch_size'   => LogPruner::BATCH_SIZE,
				),
			)
		);
	}
}
