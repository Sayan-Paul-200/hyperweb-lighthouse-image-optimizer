<?php
/**
 * Fake Elementor document-data store.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Integration;

use HyperWeb\LighthouseImageOptimizer\Integration\ElementorDocumentData;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorDocumentDataStoreInterface;

/**
 * Deterministic Elementor document-data store for unit tests.
 */
final class FakeElementorDocumentDataStore implements ElementorDocumentDataStoreInterface {

	/**
	 * Read result.
	 *
	 * @var ElementorDocumentData
	 */
	public $document;

	/**
	 * Last requested document ID.
	 *
	 * @var int
	 */
	public $last_document_id = 0;

	/**
	 * Create fake store.
	 */
	public function __construct() {
		$this->document = ElementorDocumentData::missing();
	}

	/**
	 * Read one document.
	 *
	 * @param int $document_id Document ID.
	 * @return ElementorDocumentData
	 */
	public function read_document( int $document_id ): ElementorDocumentData {
		$this->last_document_id = $document_id;

		return $this->document;
	}
}
