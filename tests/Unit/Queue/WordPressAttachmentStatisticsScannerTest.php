<?php
/**
 * WordPress attachment statistics scanner tests.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Queue;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;
use HyperWeb\LighthouseImageOptimizer\Queue\WordPressAttachmentStatisticsScanner;
use PHPUnit\Framework\TestCase;

/**
 * Verifies the WordPress-backed statistics scanner query shape.
 */
final class WordPressAttachmentStatisticsScannerTest extends TestCase {

	/**
	 * Test normal Media Library attachments are included by querying inherit status explicitly.
	 *
	 * @return void
	 */
	public function test_scan_page_queries_inherit_attachment_statuses(): void {
		$captured_args = array();
		$scanner       = new WordPressAttachmentStatisticsScanner(
			static function ( array $args ) use ( &$captured_args ): array {
				$captured_args = $args;

				return array( '10', 0, '11' );
			}
		);

		$ids = $scanner->scan_page( 2, 500 );

		self::assertSame( array( 10, 11 ), $ids );
		self::assertSame( 'attachment', $captured_args['post_type'] );
		self::assertSame( array( 'inherit', 'private', 'publish' ), $captured_args['post_status'] );
		self::assertSame( 'ids', $captured_args['fields'] );
		self::assertSame( 100, $captured_args['posts_per_page'] );
		self::assertSame( 2, $captured_args['paged'] );
		self::assertSame( 'OR', $captured_args['meta_query']['relation'] );
		self::assertSame( LifecyclePolicy::META_STATUS, $captured_args['meta_query'][0]['key'] );
		self::assertSame( LifecyclePolicy::META_DERIVATIVES, $captured_args['meta_query'][1]['key'] );
		self::assertSame( LifecyclePolicy::META_EXCLUDED, $captured_args['meta_query'][2]['key'] );
	}
}
