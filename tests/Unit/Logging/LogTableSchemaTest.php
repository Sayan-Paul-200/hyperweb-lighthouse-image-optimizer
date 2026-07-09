<?php
/**
 * Tests for log table schema.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Logging;

use HyperWeb\LighthouseImageOptimizer\Logging\LogTableSchema;
use PHPUnit\Framework\TestCase;

/**
 * Verifies controlled log table SQL.
 */
final class LogTableSchemaTest extends TestCase {

	/**
	 * Test prefixed log table name.
	 *
	 * @return void
	 */
	public function test_table_name_uses_wordpress_prefix(): void {
		self::assertSame( 'wp_hwlio_logs', LogTableSchema::table_name( 'wp_' ) );
	}

	/**
	 * Test SQL contains required columns and indexes.
	 *
	 * @return void
	 */
	public function test_sql_contains_required_columns_and_indexes(): void {
		$sql = LogTableSchema::sql( 'wp_', 'DEFAULT CHARSET=utf8mb4' );

		self::assertStringContainsString( 'CREATE TABLE wp_hwlio_logs', $sql );
		self::assertStringContainsString( 'id bigint(20) unsigned NOT NULL AUTO_INCREMENT', $sql );
		self::assertStringContainsString( 'created_at_gmt datetime NOT NULL', $sql );
		self::assertStringContainsString( 'level varchar(20) NOT NULL', $sql );
		self::assertStringContainsString( 'code varchar(64) NOT NULL', $sql );
		self::assertStringContainsString( 'message text NOT NULL', $sql );
		self::assertStringContainsString( 'attachment_id bigint(20) unsigned NULL', $sql );
		self::assertStringContainsString( 'job_id varchar(191) NULL', $sql );
		self::assertStringContainsString( 'context_json longtext NULL', $sql );
		self::assertStringContainsString( 'PRIMARY KEY  (id)', $sql );
		self::assertStringContainsString( 'KEY created_at_gmt (created_at_gmt)', $sql );
		self::assertStringContainsString( 'KEY level (level)', $sql );
		self::assertStringContainsString( 'KEY attachment_id (attachment_id)', $sql );
		self::assertStringContainsString( 'DEFAULT CHARSET=utf8mb4', $sql );
	}
}
