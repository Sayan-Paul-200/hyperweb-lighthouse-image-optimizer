<?php
/**
 * Tests for safe derivative cleanup.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\DerivativeCleanup;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecycleResult;
use PHPUnit\Framework\TestCase;

/**
 * Verifies derivative cleanup never deletes originals or unsafe paths.
 */
final class DerivativeCleanupTest extends TestCase {

	private const UPLOADS = 'C:/site/wp-content/uploads';

	/**
	 * Test cleanup deletes only manifest-owned derivatives.
	 *
	 * @return void
	 */
	public function test_cleanup_deletes_recorded_derivatives_and_preserves_original(): void {
		$filesystem = new FakeFilesystem(
			array(
				self::UPLOADS . '/2026/07/hero.jpg',
				self::UPLOADS . '/2026/07/hero.jpg.hwlio.webp',
				self::UPLOADS . '/2026/07/hero.jpg.hwlio.avif',
			),
			array( self::UPLOADS )
		);

		$cleanup = new DerivativeCleanup(
			self::UPLOADS,
			$filesystem,
			new FakeDerivativeManifestProvider( array( 10 => $this->manifest() ) )
		);

		$result = $cleanup->cleanup();

		self::assertTrue( $result->is_successful() );
		self::assertTrue( $result->has_code( LifecycleResult::CODE_DERIVATIVES_DELETED ) );
		self::assertSame(
			array(
				self::UPLOADS . '/2026/07/hero.jpg.hwlio.webp',
				self::UPLOADS . '/2026/07/hero.jpg.hwlio.avif',
			),
			$filesystem->deleted
		);
		self::assertArrayHasKey( self::UPLOADS . '/2026/07/hero.jpg', $filesystem->files );
	}

	/**
	 * Test cleanup preserves source file paths even when listed as a format.
	 *
	 * @return void
	 */
	public function test_cleanup_preserves_original_even_if_manifest_format_points_to_source(): void {
		$manifest = $this->manifest();
		$manifest['sizes']['full']['formats']['webp']['file'] = '2026/07/hero.jpg';

		$filesystem = new FakeFilesystem(
			array(
				self::UPLOADS . '/2026/07/hero.jpg',
				self::UPLOADS . '/2026/07/hero.jpg.hwlio.avif',
			),
			array( self::UPLOADS )
		);

		$cleanup = new DerivativeCleanup(
			self::UPLOADS,
			$filesystem,
			new FakeDerivativeManifestProvider( array( 10 => $manifest ) )
		);

		$result = $cleanup->cleanup();

		self::assertTrue( $result->has_warnings() );
		self::assertTrue( $result->has_code( LifecycleResult::CODE_ORIGINAL_PRESERVED ) );
		self::assertSame( array( self::UPLOADS . '/2026/07/hero.jpg.hwlio.avif' ), $filesystem->deleted );
		self::assertArrayHasKey( self::UPLOADS . '/2026/07/hero.jpg', $filesystem->files );
	}

	/**
	 * Test unsafe paths are rejected.
	 *
	 * @return void
	 */
	public function test_cleanup_rejects_traversal_absolute_and_symlink_paths(): void {
		$manifest = $this->manifest();
		$manifest['sizes']['full']['formats']['webp']['file'] = '../outside.webp';
		$manifest['sizes']['full']['formats']['avif']['file'] = 'C:/outside/file.avif';
		$manifest['sizes']['full']['formats']['badlink']      = array(
			'file'   => '2026/07/link.webp',
			'status' => 'ready',
		);

		$filesystem = new FakeFilesystem(
			array(),
			array( self::UPLOADS ),
			array( self::UPLOADS . '/2026/07/link.webp' )
		);

		$cleanup = new DerivativeCleanup(
			self::UPLOADS,
			$filesystem,
			new FakeDerivativeManifestProvider( array( 10 => $manifest ) )
		);

		$result = $cleanup->cleanup();

		self::assertTrue( $result->has_warnings() );
		self::assertTrue( $result->has_code( LifecycleResult::CODE_DERIVATIVE_REJECTED ) );
		self::assertSame( array(), $filesystem->deleted );
	}

	/**
	 * Build a representative derivative manifest.
	 *
	 * @return array<string,mixed>
	 */
	private function manifest(): array {
		return array(
			'schema_version' => 1,
			'sizes'          => array(
				'full' => array(
					'source'  => array(
						'file'   => '2026/07/hero.jpg',
						'width'  => 2400,
						'height' => 1600,
						'mime'   => 'image/jpeg',
					),
					'formats' => array(
						'webp' => array(
							'file'   => '2026/07/hero.jpg.hwlio.webp',
							'status' => 'ready',
						),
						'avif' => array(
							'file'   => '2026/07/hero.jpg.hwlio.avif',
							'status' => 'ready',
						),
					),
				),
			),
		);
	}
}
