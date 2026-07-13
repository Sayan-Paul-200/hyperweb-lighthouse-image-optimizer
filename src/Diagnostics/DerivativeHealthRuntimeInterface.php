<?php
/**
 * Derivative health runtime contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Diagnostics;

/**
 * Provides bounded attachment ID pages for derivative-health diagnostics.
 */
interface DerivativeHealthRuntimeInterface {

	/**
	 * Read attachment IDs with plugin-owned derivative metadata after a cursor.
	 *
	 * @param int $after_id Exclusive attachment-ID cursor.
	 * @param int $limit Page size.
	 * @return int[]
	 */
	public function attachment_ids_after( int $after_id, int $limit ): array;
}
