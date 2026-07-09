<?php
/**
 * Fake log database adapter.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Logging;

use HyperWeb\LighthouseImageOptimizer\Logging\LogDatabaseInterface;

/**
 * Captures log database calls for unit tests.
 */
final class FakeLogDatabase implements LogDatabaseInterface {

	/**
	 * Insert result.
	 *
	 * @var bool
	 */
	public $insert_result = true;

	/**
	 * Delete result.
	 *
	 * @var int
	 */
	public $delete_result = 0;

	/**
	 * Last inserted table.
	 *
	 * @var string|null
	 */
	public $insert_table;

	/**
	 * Last inserted data.
	 *
	 * @var array<string,mixed>|null
	 */
	public $insert_data;

	/**
	 * Last insert formats.
	 *
	 * @var string[]|null
	 */
	public $insert_formats;

	/**
	 * Last delete table.
	 *
	 * @var string|null
	 */
	public $delete_table;

	/**
	 * Last delete cutoff.
	 *
	 * @var string|null
	 */
	public $delete_cutoff_gmt;

	/**
	 * Last delete limit.
	 *
	 * @var int|null
	 */
	public $delete_limit;

	/**
	 * Insert a log row.
	 *
	 * @param string              $table Table name.
	 * @param array<string,mixed> $data Row data.
	 * @param string[]            $formats Insert formats.
	 * @return bool
	 */
	public function insert( string $table, array $data, array $formats ): bool {
		$this->insert_table   = $table;
		$this->insert_data    = $data;
		$this->insert_formats = $formats;

		return $this->insert_result;
	}

	/**
	 * Delete old log rows in a bounded batch.
	 *
	 * @param string $table Table name.
	 * @param string $cutoff_gmt Delete rows older than this GMT datetime.
	 * @param int    $limit Maximum rows to delete.
	 * @return int
	 */
	public function delete_older_than( string $table, string $cutoff_gmt, int $limit ): int {
		$this->delete_table      = $table;
		$this->delete_cutoff_gmt = $cutoff_gmt;
		$this->delete_limit      = $limit;

		return $this->delete_result;
	}
}
