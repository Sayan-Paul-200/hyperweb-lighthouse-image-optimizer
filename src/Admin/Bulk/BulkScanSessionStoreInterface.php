<?php
/**
 * Bulk scan session store contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\Bulk;

/**
 * Persists resumable bulk-scan sessions and candidate chunks.
 */
interface BulkScanSessionStoreInterface {

	/**
	 * Load one stored session.
	 *
	 * @param string $token Scan token.
	 * @return BulkScanSession|null
	 */
	public function load( string $token ): ?BulkScanSession;

	/**
	 * Save one session metadata snapshot.
	 *
	 * @param BulkScanSession $session Session.
	 * @return bool
	 */
	public function save( BulkScanSession $session ): bool;

	/**
	 * Delete one stored session and its candidate chunks.
	 *
	 * @param string $token Scan token.
	 * @return void
	 */
	public function delete( string $token ): void;

	/**
	 * Append one candidate-ID batch and return updated session progress.
	 *
	 * @param BulkScanSession $session Session.
	 * @param int[]           $attachment_ids Candidate attachment IDs.
	 * @return BulkScanSession
	 */
	public function append_candidate_ids( BulkScanSession $session, array $attachment_ids ): BulkScanSession;

	/**
	 * Read one bounded candidate preview page.
	 *
	 * @param BulkScanSession $session Session.
	 * @param int             $page Page number.
	 * @param int             $per_page Page size.
	 * @return int[]
	 */
	public function read_candidate_page( BulkScanSession $session, int $page, int $per_page ): array;
}
