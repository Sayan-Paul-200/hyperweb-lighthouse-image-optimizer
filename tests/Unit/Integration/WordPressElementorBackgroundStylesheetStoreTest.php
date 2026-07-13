<?php
/**
 * Tests for the WordPress-backed Elementor background stylesheet store.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Integration;

use HyperWeb\LighthouseImageOptimizer\Integration\WordPressElementorBackgroundStylesheetStore;
use PHPUnit\Framework\TestCase;

/**
 * Verifies companion stylesheet artifacts stay inside uploads and replace safely.
 */
final class WordPressElementorBackgroundStylesheetStoreTest extends TestCase {

	/**
	 * Temporary uploads base directory.
	 *
	 * @var string
	 */
	private $uploads_dir = '';

	/**
	 * Set up temp directory.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->uploads_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/hwlio-elementor-background-store-' . uniqid();
	}

	/**
	 * Clean up temp directory.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		$this->delete_directory( $this->uploads_dir );
		parent::tearDown();
	}

	/**
	 * Test the store resolves deterministic paths and URLs.
	 *
	 * @return void
	 */
	public function test_store_resolves_deterministic_paths_and_urls(): void {
		$store = $this->store();

		self::assertSame(
			'hwlio/elementor-background-css/501.hwlio-backgrounds.css',
			$store->relative_path( 501 )
		);
		self::assertSame(
			'https://example.test/wp-content/uploads/hwlio/elementor-background-css/501.hwlio-backgrounds.css',
			$store->url( 501 )
		);
	}

	/**
	 * Test writes replace safely and rollback deletes only plugin-owned companion files.
	 *
	 * @return void
	 */
	public function test_store_writes_replaces_and_deletes_plugin_owned_artifacts(): void {
		$store = $this->store();

		self::assertTrue( $store->write( 501, "/* first */\nbody{}" ) );
		self::assertSame( "/* first */\nbody{}", $store->read( 501 ) );
		self::assertTrue( $store->exists( 501 ) );

		self::assertTrue( $store->write( 501, "/* second */\nbody{color:black;}" ) );
		self::assertSame( "/* second */\nbody{color:black;}", $store->read( 501 ) );
		self::assertFileDoesNotExist( $this->absolute_path( 501 ) . '.bak' );
		self::assertFileDoesNotExist( $this->absolute_path( 501 ) . '.tmp' );

		self::assertTrue( $store->delete( 501 ) );
		self::assertFalse( $store->exists( 501 ) );
		self::assertFileDoesNotExist( $this->absolute_path( 501 ) );
	}

	/**
	 * Build the real store against a temp uploads directory.
	 *
	 * @return WordPressElementorBackgroundStylesheetStore
	 */
	private function store(): WordPressElementorBackgroundStylesheetStore {
		return new WordPressElementorBackgroundStylesheetStore(
			function (): array {
				return array(
					'basedir' => $this->uploads_dir,
					'baseurl' => 'https://example.test/wp-content/uploads',
					'error'   => '',
				);
			}
		);
	}

	/**
	 * Get the absolute expected artifact path.
	 *
	 * @param int $document_id Document ID.
	 * @return string
	 */
	private function absolute_path( int $document_id ): string {
		return $this->uploads_dir . '/hwlio/elementor-background-css/' . $document_id . '.hwlio-backgrounds.css';
	}

	/**
	 * Delete one directory tree recursively.
	 *
	 * @param string $directory Directory path.
	 * @return void
	 */
	private function delete_directory( string $directory ): void {
		if ( '' === $directory || ! is_dir( $directory ) ) {
			return;
		}

		$items = scandir( $directory );

		if ( ! is_array( $items ) ) {
			return;
		}

		foreach ( $items as $item ) {
			if ( '.' === $item || '..' === $item ) {
				continue;
			}

			$path = $directory . '/' . $item;

			if ( is_dir( $path ) ) {
				$this->delete_directory( $path );
				continue;
			}

			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Cleaning up test temp files.
			unlink( $path );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir,WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Cleaning up test temp directories.
		rmdir( $directory );
	}
}
