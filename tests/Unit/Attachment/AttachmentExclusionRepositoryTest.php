<?php
/**
 * Attachment exclusion repository tests.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment;

use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentExclusionRepository;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;
use PHPUnit\Framework\TestCase;

/**
 * Tests for attachment exclusion reads.
 */
final class AttachmentExclusionRepositoryTest extends TestCase {

	/**
	 * Test source meta is used as the primary exclusion signal.
	 *
	 * @return void
	 */
	public function test_reads_hwlio_excluded_first(): void {
		$store = new FakeAttachmentMetaStore();
		$store->meta[123][ LifecyclePolicy::META_EXCLUDED ] = true;
		$store->meta[123][ LifecyclePolicy::META_STATUS ]   = array(
			'state'      => 'unprocessed',
			'formats'    => array(),
			'updated_at' => 0,
			'error_code' => null,
			'excluded'   => false,
		);

		$repository = new AttachmentExclusionRepository( $store );

		self::assertTrue( $repository->is_excluded( 123 ) );
	}

	/**
	 * Test status exclusion is used as a fallback.
	 *
	 * @return void
	 */
	public function test_falls_back_to_status_summary_when_source_meta_is_absent(): void {
		$store = new FakeAttachmentMetaStore();
		$store->meta[123][ LifecyclePolicy::META_STATUS ] = array(
			'state'      => 'excluded',
			'formats'    => array(),
			'updated_at' => 0,
			'error_code' => null,
			'excluded'   => true,
		);

		$repository = new AttachmentExclusionRepository( $store );

		self::assertTrue( $repository->is_excluded( 123 ) );
	}

	/**
	 * Test false is returned when no exclusion markers exist.
	 *
	 * @return void
	 */
	public function test_returns_false_without_exclusion_markers(): void {
		$repository = new AttachmentExclusionRepository( new FakeAttachmentMetaStore() );

		self::assertFalse( $repository->is_excluded( 123 ) );
	}
}
