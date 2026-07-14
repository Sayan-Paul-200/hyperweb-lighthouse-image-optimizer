<?php
/**
 * Tests for the CLI log prune runner.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Cli;

use HyperWeb\LighthouseImageOptimizer\Cli\CliLogPruneService;
use HyperWeb\LighthouseImageOptimizer\Logging\LogPruner;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Logging\FakeLogPruner;
use PHPUnit\Framework\TestCase;

/**
 * Verifies CLI log pruning loops bounded batches safely.
 */
final class CliLogPruneServiceTest extends TestCase {

	/**
	 * Test pruning loops until the final short batch.
	 *
	 * @return void
	 */
	public function test_prune_loops_until_short_batch(): void {
		$pruner          = new FakeLogPruner();
		$pruner->results = array( LogPruner::BATCH_SIZE, 17 );
		$messages        = array();
		$service         = new CliLogPruneService( $pruner );

		$result = $service->prune(
			static function ( string $message ) use ( &$messages ): void {
				$messages[] = $message;
			}
		);

		self::assertFalse( $result->is_degraded() );
		self::assertSame( 2, $pruner->calls );
		self::assertSame( 2, $result->payload()['progress']['batches'] );
		self::assertSame( LogPruner::BATCH_SIZE + 17, $result->payload()['summary']['rows_removed'] );
		self::assertCount( 2, $messages );
	}
}
