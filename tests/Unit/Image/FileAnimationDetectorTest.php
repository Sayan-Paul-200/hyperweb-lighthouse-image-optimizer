<?php
/**
 * Tests for file-backed animation detection.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image;

use HyperWeb\LighthouseImageOptimizer\Image\AnimationStatus;
use HyperWeb\LighthouseImageOptimizer\Image\FileAnimationDetector;
use HyperWeb\LighthouseImageOptimizer\Image\SourceMimePolicy;
use PHPUnit\Framework\TestCase;

/**
 * Verifies GIF and WebP animation parsing.
 */
final class FileAnimationDetectorTest extends TestCase {

	/**
	 * Temporary files.
	 *
	 * @var string[]
	 */
	private $temporary_files = array();

	/**
	 * Remove temporary files.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		foreach ( $this->temporary_files as $path ) {
			if ( is_file( $path ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- Test cleanup for temporary fixture files.
				unlink( $path );
			}
		}

		$this->temporary_files = array();
	}

	/**
	 * Test animated GIF detection.
	 *
	 * @return void
	 */
	public function test_detects_animated_gif_by_counting_frames(): void {
		$result = ( new FileAnimationDetector() )->detect(
			$this->temporary_file( $this->gif_fixture( 2 ) ),
			SourceMimePolicy::MIME_GIF
		);

		self::assertSame( AnimationStatus::STATUS_ANIMATED, $result->status() );
		self::assertSame( 'animated_gif', $result->code() );
	}

	/**
	 * Test static GIF detection.
	 *
	 * @return void
	 */
	public function test_detects_non_animated_gif(): void {
		$result = ( new FileAnimationDetector() )->detect(
			$this->temporary_file( $this->gif_fixture( 1 ) ),
			SourceMimePolicy::MIME_GIF
		);

		self::assertSame( AnimationStatus::STATUS_NOT_ANIMATED, $result->status() );
	}

	/**
	 * Test animated WebP detection.
	 *
	 * @return void
	 */
	public function test_detects_animated_webp_from_vp8x_flag_and_anmf_chunk(): void {
		$vp8x = ( new FileAnimationDetector() )->detect(
			$this->temporary_file(
				$this->webp_fixture(
					array(
						$this->webp_chunk( 'VP8X', chr( 0x02 ) . str_repeat( "\0", 9 ) ),
					)
				)
			),
			SourceMimePolicy::MIME_WEBP
		);
		$anmf = ( new FileAnimationDetector() )->detect(
			$this->temporary_file(
				$this->webp_fixture(
					array(
						$this->webp_chunk( 'VP8X', str_repeat( "\0", 10 ) ),
						$this->webp_chunk( 'ANMF', str_repeat( "\0", 16 ) ),
					)
				)
			),
			SourceMimePolicy::MIME_WEBP
		);

		self::assertSame( AnimationStatus::STATUS_ANIMATED, $vp8x->status() );
		self::assertSame( AnimationStatus::STATUS_ANIMATED, $anmf->status() );
	}

	/**
	 * Test static WebP detection.
	 *
	 * @return void
	 */
	public function test_detects_non_animated_webp(): void {
		$result = ( new FileAnimationDetector() )->detect(
			$this->temporary_file(
				$this->webp_fixture(
					array(
						$this->webp_chunk( 'VP8 ', 'still' ),
					)
				)
			),
			SourceMimePolicy::MIME_WEBP
		);

		self::assertSame( AnimationStatus::STATUS_NOT_ANIMATED, $result->status() );
	}

	/**
	 * Test malformed animation containers are unknown.
	 *
	 * @return void
	 */
	public function test_malformed_gif_and_webp_return_unknown(): void {
		$gif  = ( new FileAnimationDetector() )->detect(
			$this->temporary_file( 'GIF89a' ),
			SourceMimePolicy::MIME_GIF
		);
		$webp = ( new FileAnimationDetector() )->detect(
			$this->temporary_file( 'RIFF' . pack( 'V', 4 ) . 'WEBPVP8X' ),
			SourceMimePolicy::MIME_WEBP
		);

		self::assertSame( AnimationStatus::STATUS_UNKNOWN, $gif->status() );
		self::assertSame( AnimationStatus::STATUS_UNKNOWN, $webp->status() );
	}

	/**
	 * Write temporary fixture file.
	 *
	 * @param string $contents Contents.
	 * @return string
	 */
	private function temporary_file( string $contents ): string {
		$path = tempnam( sys_get_temp_dir(), 'hwlio-anim-' );

		self::assertIsString( $path );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture file creation.
		file_put_contents( $path, $contents );

		$this->temporary_files[] = $path;

		return $path;
	}

	/**
	 * Build a minimal GIF fixture.
	 *
	 * @param int $frames Frame count.
	 * @return string
	 */
	private function gif_fixture( int $frames ): string {
		$contents = 'GIF89a' . pack( 'vvCCC', 1, 1, 0, 0, 0 );

		for ( $index = 0; $index < $frames; ++$index ) {
			$contents .= "\x2c" . pack( 'vvvvC', 0, 0, 1, 1, 0 ) . "\x02\x02\x4c\x01\x00";
		}

		return $contents . "\x3b";
	}

	/**
	 * Build a RIFF/WebP fixture.
	 *
	 * @param string[] $chunks Chunks.
	 * @return string
	 */
	private function webp_fixture( array $chunks ): string {
		$body = 'WEBP' . implode( '', $chunks );

		return 'RIFF' . pack( 'V', strlen( $body ) ) . $body;
	}

	/**
	 * Build a WebP chunk.
	 *
	 * @param string $type Chunk type.
	 * @param string $payload Payload.
	 * @return string
	 */
	private function webp_chunk( string $type, string $payload ): string {
		return $type . pack( 'V', strlen( $payload ) ) . $payload . ( 0 === strlen( $payload ) % 2 ? '' : "\0" );
	}
}
