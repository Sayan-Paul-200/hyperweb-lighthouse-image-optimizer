<?php
/**
 * Optimization retry policy tests.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Queue;

use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentProcessResult;
use HyperWeb\LighthouseImageOptimizer\Image\ConversionResult;
use HyperWeb\LighthouseImageOptimizer\Image\ConversionResultCode;
use HyperWeb\LighthouseImageOptimizer\Image\ConversionResultCollection;
use HyperWeb\LighthouseImageOptimizer\Image\ConversionSavings;
use HyperWeb\LighthouseImageOptimizer\Image\SourceImage;
use HyperWeb\LighthouseImageOptimizer\Queue\OptimizationJob;
use HyperWeb\LighthouseImageOptimizer\Queue\OptimizationRetryPolicy;
use PHPUnit\Framework\TestCase;

/**
 * Tests for OptimizationRetryPolicy.
 */
final class OptimizationRetryPolicyTest extends TestCase {

	/**
	 * Test retry attempt parsing from reason.
	 *
	 * @return void
	 */
	public function test_retry_attempt_is_parsed_from_reason(): void {
		$policy = new OptimizationRetryPolicy();

		self::assertSame( 0, $policy->retry_attempt_from_reason( 'manual' ) );
		self::assertSame( 2, $policy->retry_attempt_from_reason( 'retry_2' ) );
	}

	/**
	 * Test max retry checks.
	 *
	 * @return void
	 */
	public function test_max_retry_checks(): void {
		$policy = new OptimizationRetryPolicy();
		$job    = new OptimizationJob( 123, 'webp', 0, false, 'retry_3', str_repeat( 'a', 20 ) );

		self::assertFalse( $policy->can_retry( $job, 3 ) );
		self::assertTrue( $policy->can_retry( new OptimizationJob( 123, 'webp', 0, false, 'manual', str_repeat( 'a', 20 ) ), 3 ) );
	}

	/**
	 * Test retry backoff values.
	 *
	 * @return void
	 */
	public function test_retry_backoff_values(): void {
		$policy = new OptimizationRetryPolicy();

		self::assertSame( 60, $policy->retry_delay_seconds( new OptimizationJob( 123, 'webp', 0, false, 'manual', str_repeat( 'a', 20 ) ) ) );
		self::assertSame( 120, $policy->retry_delay_seconds( new OptimizationJob( 123, 'webp', 0, false, 'retry_1', str_repeat( 'a', 20 ) ) ) );
		self::assertSame( 240, $policy->retry_delay_seconds( new OptimizationJob( 123, 'webp', 0, false, 'retry_2', str_repeat( 'a', 20 ) ) ) );
	}

	/**
	 * Test retryable vs permanent failure classification.
	 *
	 * @return void
	 */
	public function test_retryable_vs_permanent_failure_classification(): void {
		$policy    = new OptimizationRetryPolicy();
		$job       = new OptimizationJob( 123, 'webp', 0, false, 'manual', str_repeat( 'a', 20 ) );
		$source    = $this->source();
		$retryable = ConversionResult::failed(
			$source,
			'webp',
			'image/webp',
			ConversionResultCode::TEMPORARY_WRITE_FAILED,
			'Temporary file write failed.',
			null,
			new ConversionSavings( 1000, null )
		);
		$permanent = ConversionResult::failed(
			$source,
			'webp',
			'image/webp',
			ConversionResultCode::SOURCE_CORRUPT,
			'Source is corrupt.',
			null,
			new ConversionSavings( 1000, null )
		);

		self::assertTrue(
			$policy->should_retry_result(
				$job,
				AttachmentProcessResult::success( new ConversionResultCollection( array( $retryable ) ) ),
				3
			)
		);
		self::assertFalse(
			$policy->should_retry_result(
				$job,
				AttachmentProcessResult::success( new ConversionResultCollection( array( $permanent ) ) ),
				3
			)
		);
	}

	/**
	 * Build a source image for failed conversion results.
	 *
	 * @return SourceImage
	 */
	private function source(): SourceImage {
		return new SourceImage(
			123,
			'full',
			SourceImage::ROLE_FULL,
			'2026/07/hero.jpg',
			'/uploads/2026/07/hero.jpg',
			'image/jpeg',
			2400,
			1600,
			1000,
			1783526400
		);
	}
}
