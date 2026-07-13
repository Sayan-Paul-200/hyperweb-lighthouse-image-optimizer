<?php
/**
 * Elementor document-data store contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration;

/**
 * Provides read-only access to structured Elementor document data.
 */
interface ElementorDocumentDataStoreInterface {

	/**
	 * Read one Elementor document.
	 *
	 * @param int $document_id Post/document ID.
	 * @return ElementorDocumentData
	 */
	public function read_document( int $document_id ): ElementorDocumentData;
}
