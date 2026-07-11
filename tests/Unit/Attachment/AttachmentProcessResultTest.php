<?php
/**
 * Attachment process result tests.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment;

use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentProcessResult;
use HyperWeb\LighthouseImageOptimizer\Image\ConversionResultCollection;
use PHPUnit\Framework\TestCase;

/**
 * Tests for AttachmentProcessResult.
 *
 * @covers \HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentProcessResult
 */
final class AttachmentProcessResultTest extends TestCase {

	/**
	 * Test success factory.
	 */
	public function test_success_factory(): void {
		$results = new ConversionResultCollection();

		$result = AttachmentProcessResult::success( $results, array( 'code_a' ), array( 'Message.' ) );

		self::assertTrue( $result->is_successful() );
		self::assertFalse( $result->is_locked() );
		self::assertSame( $results, $result->results() );
		self::assertSame( array( 'code_a' ), $result->codes() );
		self::assertSame( array( 'Message.' ), $result->messages() );
	}

	/**
	 * Test skip factory.
	 */
	public function test_skip_factory(): void {
		$result = AttachmentProcessResult::skip( 'skipped_code', 'Skipped.' );

		self::assertTrue( $result->is_successful() );
		self::assertFalse( $result->is_locked() );
		self::assertNull( $result->results() );
		self::assertSame( array( 'skipped_code' ), $result->codes() );
		self::assertSame( array( 'Skipped.' ), $result->messages() );
	}

	/**
	 * Test locked factory.
	 */
	public function test_locked_factory(): void {
		$result = AttachmentProcessResult::locked();

		self::assertFalse( $result->is_successful() );
		self::assertTrue( $result->is_locked() );
		self::assertNull( $result->results() );
		self::assertSame( array( AttachmentProcessResult::CODE_SKIPPED_LOCKED ), $result->codes() );
	}

	/**
	 * Test failure factory.
	 */
	public function test_failure_factory(): void {
		$result = AttachmentProcessResult::failure( 'fail_code', 'Failed.' );

		self::assertFalse( $result->is_successful() );
		self::assertFalse( $result->is_locked() );
		self::assertNull( $result->results() );
		self::assertSame( array( 'fail_code' ), $result->codes() );
		self::assertSame( array( 'Failed.' ), $result->messages() );
	}
}
