<?php
/**
 * Fake Elementor background stylesheet store.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Integration;

use HyperWeb\LighthouseImageOptimizer\Integration\ElementorBackgroundStylesheetStoreInterface;

/**
 * In-memory artifact store for companion stylesheet tests.
 */
final class FakeElementorBackgroundStylesheetStore implements ElementorBackgroundStylesheetStoreInterface {

	/**
	 * Stored contents keyed by document ID.
	 *
	 * @var array<int,string>
	 */
	public $contents = array();

	/**
	 * Relative paths keyed by document ID.
	 *
	 * @var array<int,string>
	 */
	public $paths = array();

	/**
	 * Public URLs keyed by document ID.
	 *
	 * @var array<int,string>
	 */
	public $urls = array();

	/**
	 * Whether writes should fail.
	 *
	 * @var bool
	 */
	public $fail_writes = false;

	/**
	 * Whether deletes should fail.
	 *
	 * @var bool
	 */
	public $fail_deletes = false;

	/**
	 * Write operations.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	public $writes = array();

	/**
	 * Delete operations.
	 *
	 * @var int[]
	 */
	public $deletes = array();

	/**
	 * Get the uploads-relative stylesheet path.
	 *
	 * @param int $document_id Document ID.
	 * @return string|null
	 */
	public function relative_path( int $document_id ): ?string {
		$document_id = max( 0, $document_id );

		if ( 1 > $document_id ) {
			return null;
		}

		if ( ! isset( $this->paths[ $document_id ] ) ) {
			$this->paths[ $document_id ] = 'hwlio/elementor-background-css/' . $document_id . '.hwlio-backgrounds.css';
		}

		return $this->paths[ $document_id ];
	}

	/**
	 * Get the public stylesheet URL.
	 *
	 * @param int $document_id Document ID.
	 * @return string|null
	 */
	public function url( int $document_id ): ?string {
		$document_id = max( 0, $document_id );

		if ( 1 > $document_id ) {
			return null;
		}

		if ( ! isset( $this->urls[ $document_id ] ) ) {
			$this->urls[ $document_id ] = 'https://example.test/wp-content/uploads/' . $this->relative_path( $document_id );
		}

		return $this->urls[ $document_id ];
	}

	/**
	 * Whether the stylesheet exists.
	 *
	 * @param int $document_id Document ID.
	 * @return bool
	 */
	public function exists( int $document_id ): bool {
		return array_key_exists( max( 0, $document_id ), $this->contents );
	}

	/**
	 * Read one stylesheet.
	 *
	 * @param int $document_id Document ID.
	 * @return string|null
	 */
	public function read( int $document_id ): ?string {
		$document_id = max( 0, $document_id );

		return array_key_exists( $document_id, $this->contents ) ? $this->contents[ $document_id ] : null;
	}

	/**
	 * Write one stylesheet safely.
	 *
	 * @param int    $document_id Document ID.
	 * @param string $contents Stylesheet contents.
	 * @return bool
	 */
	public function write( int $document_id, string $contents ): bool {
		$document_id    = max( 0, $document_id );
		$this->writes[] = array(
			'document_id' => $document_id,
			'contents'    => $contents,
		);

		if ( $this->fail_writes || 1 > $document_id ) {
			return false;
		}

		$this->contents[ $document_id ] = $contents;

		return true;
	}

	/**
	 * Delete one stylesheet safely.
	 *
	 * @param int $document_id Document ID.
	 * @return bool
	 */
	public function delete( int $document_id ): bool {
		$document_id     = max( 0, $document_id );
		$this->deletes[] = $document_id;

		if ( $this->fail_deletes ) {
			return false;
		}

		unset( $this->contents[ $document_id ] );

		return true;
	}
}
