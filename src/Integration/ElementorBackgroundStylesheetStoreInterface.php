<?php
/**
 * Elementor background stylesheet store contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration;

/**
 * Resolves and mutates plugin-owned Elementor companion stylesheet artifacts.
 */
interface ElementorBackgroundStylesheetStoreInterface {

	/**
	 * Get the uploads-relative stylesheet path.
	 *
	 * @param int $document_id Document ID.
	 * @return string|null
	 */
	public function relative_path( int $document_id ): ?string;

	/**
	 * Get the public stylesheet URL.
	 *
	 * @param int $document_id Document ID.
	 * @return string|null
	 */
	public function url( int $document_id ): ?string;

	/**
	 * Whether the stylesheet exists.
	 *
	 * @param int $document_id Document ID.
	 * @return bool
	 */
	public function exists( int $document_id ): bool;

	/**
	 * Read one stylesheet.
	 *
	 * @param int $document_id Document ID.
	 * @return string|null
	 */
	public function read( int $document_id ): ?string;

	/**
	 * Write one stylesheet safely.
	 *
	 * @param int    $document_id Document ID.
	 * @param string $contents Stylesheet contents.
	 * @return bool
	 */
	public function write( int $document_id, string $contents ): bool;

	/**
	 * Delete one stylesheet safely.
	 *
	 * @param int $document_id Document ID.
	 * @return bool
	 */
	public function delete( int $document_id ): bool;
}
