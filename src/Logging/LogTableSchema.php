<?php
/**
 * Log table schema.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Logging;

/**
 * Provides the controlled SQL used by dbDelta().
 */
final class LogTableSchema {

	/**
	 * Unprefixed log table suffix.
	 *
	 * @var string
	 */
	public const TABLE_SUFFIX = 'hwlio_logs';

	/**
	 * Build the prefixed table name.
	 *
	 * @param string $prefix WordPress database table prefix.
	 * @return string
	 */
	public static function table_name( string $prefix ): string {
		return $prefix . self::TABLE_SUFFIX;
	}

	/**
	 * Build the controlled dbDelta SQL.
	 *
	 * @param string $prefix WordPress database table prefix.
	 * @param string $charset_collate WordPress charset/collation clause.
	 * @return string
	 */
	public static function sql( string $prefix, string $charset_collate ): string {
		$table_name      = self::table_name( $prefix );
		$charset_collate = trim( $charset_collate );
		$charset_suffix  = '' === $charset_collate ? '' : ' ' . $charset_collate;

		return "CREATE TABLE {$table_name} (
id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
created_at_gmt datetime NOT NULL,
level varchar(20) NOT NULL,
code varchar(64) NOT NULL,
message text NOT NULL,
attachment_id bigint(20) unsigned NULL,
job_id varchar(191) NULL,
context_json longtext NULL,
PRIMARY KEY  (id),
KEY created_at_gmt (created_at_gmt),
KEY level (level),
KEY attachment_id (attachment_id)
){$charset_suffix};";
	}
}
