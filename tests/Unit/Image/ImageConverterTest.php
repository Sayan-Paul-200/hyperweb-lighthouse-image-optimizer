<?php
/**
 * Tests for image converter.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image;

use HyperWeb\LighthouseImageOptimizer\Image\ConversionEditorResult;
use HyperWeb\LighthouseImageOptimizer\Image\ConversionRequest;
use HyperWeb\LighthouseImageOptimizer\Image\ConversionResult;
use HyperWeb\LighthouseImageOptimizer\Image\ConversionResultCode;
use HyperWeb\LighthouseImageOptimizer\Image\DestinationPath;
use HyperWeb\LighthouseImageOptimizer\Image\ImageConverter;
use HyperWeb\LighthouseImageOptimizer\Image\ResourceGuard;
use HyperWeb\LighthouseImageOptimizer\Image\SourceImage;
use HyperWeb\LighthouseImageOptimizer\Image\SourceMimePolicy;
use HyperWeb\LighthouseImageOptimizer\Image\WordPressConversionEditor;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\MemoryLimit;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Verifies bounded conversion workflow behavior.
 */
final class ImageConverterTest extends TestCase {

	private const UPLOADS = 'C:/site/wp-content/uploads';

	/**
	 * Test JPEG to WebP conversion success.
	 *
	 * @return void
	 */
	public function test_jpeg_to_webp_success_path(): void {
		$filesystem = $this->filesystem_with_source();
		$editor     = new FakeConversionEditor( $filesystem );
		$editor->output( 300, 'image/webp', 100, 100 );

		$result = $this->converter( $filesystem, $editor )->convert(
			new ConversionRequest( $this->source(), $this->destination(), 82, 5.0 )
		);

		self::assertTrue( $result->is_success() );
		self::assertSame( ConversionResultCode::OPTIMIZED, $result->code() );
		self::assertSame( '2026/07/hero.jpg.hwlio.webp', $result->output()->relative_path() );
		self::assertSame( 'image/webp', $result->output()->mime_type() );
		self::assertSame( 300, $result->output()->bytes() );
		self::assertSame( 82, $result->output()->quality() );
		self::assertSame( 1783526500, $result->output()->generated_at() );
		self::assertSame( 700, $result->savings()->savings_bytes() );
		self::assertSame( 70.0, $result->savings()->savings_percent() );
		self::assertTrue( $result->savings()->meets_minimum() );
		self::assertTrue( $filesystem->exists( self::UPLOADS . '/2026/07/hero.jpg' ) );
		self::assertTrue( $filesystem->exists( self::UPLOADS . '/2026/07/hero.jpg.hwlio.webp' ) );
		self::assertFalse( $filesystem->exists( self::UPLOADS . '/2026/07/hero.jpg.hwlio.webp.tmp' ) );
		self::assertSame( self::UPLOADS . '/2026/07/hero.jpg.hwlio.webp.tmp', $editor->temporary_path );
		self::assertSame( 'image/webp', $editor->target_mime );
		self::assertSame(
			array(
				array(
					'source'      => self::UPLOADS . '/2026/07/hero.jpg.hwlio.webp.tmp',
					'destination' => self::UPLOADS . '/2026/07/hero.jpg.hwlio.webp',
				),
			),
			$filesystem->moves
		);
		self::assertNotContains( self::UPLOADS . '/2026/07/hero.jpg', $filesystem->deleted );
	}

	/**
	 * Test PNG to WebP conversion success.
	 *
	 * @return void
	 */
	public function test_png_to_webp_success_path(): void {
		$filesystem = $this->filesystem_with_source( 'image/png' );
		$editor     = new FakeConversionEditor( $filesystem );
		$editor->output( 350, 'image/webp', 100, 100 );

		$result = $this->converter( $filesystem, $editor )->convert(
			new ConversionRequest( $this->source( 'image/png' ), $this->destination(), 80, 1.0 )
		);

		self::assertTrue( $result->is_success() );
		self::assertSame( 'image/webp', $result->output()->mime_type() );
	}

	/**
	 * Test WordPress editor alternate temp filename is normalized before validation.
	 *
	 * @return void
	 */
	public function test_editor_alternate_output_path_is_normalized_to_deterministic_temp_path(): void {
		$filesystem = $this->filesystem_with_source( 'image/png' );
		$editor     = new FakeConversionEditor( $filesystem );
		$editor->output( 350, 'image/webp', 100, 100 );
		$editor->output_path( self::UPLOADS . '/2026/07/hero.jpg.hwlio.webp.webp' );

		$result = $this->converter( $filesystem, $editor )->convert(
			new ConversionRequest( $this->source( 'image/png' ), $this->destination(), 80, 1.0 )
		);

		self::assertTrue( $result->is_success() );
		self::assertSame( '2026/07/hero.jpg.hwlio.webp', $result->output()->relative_path() );
		self::assertFalse( $filesystem->exists( self::UPLOADS . '/2026/07/hero.jpg.hwlio.webp.webp' ) );
		self::assertFalse( $filesystem->exists( self::UPLOADS . '/2026/07/hero.jpg.hwlio.webp.tmp' ) );
		self::assertTrue( $filesystem->exists( self::UPLOADS . '/2026/07/hero.jpg.hwlio.webp' ) );
		self::assertSame(
			array(
				array(
					'source'      => self::UPLOADS . '/2026/07/hero.jpg.hwlio.webp.webp',
					'destination' => self::UPLOADS . '/2026/07/hero.jpg.hwlio.webp.tmp',
				),
				array(
					'source'      => self::UPLOADS . '/2026/07/hero.jpg.hwlio.webp.tmp',
					'destination' => self::UPLOADS . '/2026/07/hero.jpg.hwlio.webp',
				),
			),
			$filesystem->moves
		);
	}

	/**
	 * Test unsafe alternate editor output path is rejected.
	 *
	 * @return void
	 */
	public function test_editor_alternate_output_path_outside_uploads_is_rejected(): void {
		$filesystem = $this->filesystem_with_source( 'image/png' );
		$editor     = new FakeConversionEditor( $filesystem );
		$editor->output_path( 'C:/outside/hero.jpg.hwlio.webp' );

		$result = $this->converter( $filesystem, $editor )->convert(
			new ConversionRequest( $this->source( 'image/png' ), $this->destination(), 80, 1.0 )
		);

		self::assertTrue( $result->is_failed() );
		self::assertSame( ConversionResultCode::TEMPORARY_OUTSIDE_UPLOADS, $result->code() );
		self::assertSame( 'editor_output_outside_uploads', $result->details()['reason'] );
		self::assertFalse( $filesystem->exists( self::UPLOADS . '/2026/07/hero.jpg.hwlio.webp' ) );
	}

	/**
	 * Test AVIF conversion success.
	 *
	 * @return void
	 */
	public function test_avif_success_path_when_editor_reports_success(): void {
		$filesystem  = $this->filesystem_with_source();
		$editor      = new FakeConversionEditor( $filesystem );
		$destination = $this->destination(
			SourceMimePolicy::TARGET_AVIF,
			'image/avif',
			'2026/07/hero.jpg.hwlio.avif'
		);
		$editor->output( 250, 'image/avif', 100, 100 );

		$result = $this->converter( $filesystem, $editor )->convert(
			new ConversionRequest( $this->source(), $destination, 75, 5.0 )
		);

		self::assertTrue( $result->is_success() );
		self::assertSame( SourceMimePolicy::TARGET_AVIF, $result->target_format() );
		self::assertSame( 'image/avif', $result->output()->mime_type() );
		self::assertSame( 'image/avif', $editor->target_mime );
	}

	/**
	 * Test WordPress editor adapter maps missing API to editor unavailable.
	 *
	 * @return void
	 */
	public function test_wordpress_editor_maps_missing_api_to_editor_unavailable(): void {
		$result = ( new WordPressConversionEditor() )->save( $this->source(), $this->destination(), 82 );

		self::assertFalse( $result->is_success() );
		self::assertSame( ConversionResultCode::EDITOR_UNAVAILABLE, $result->code() );
	}

	/**
	 * Test editor failure maps to converter failure.
	 *
	 * @return void
	 */
	public function test_editor_load_failure_maps_to_failed_result(): void {
		$filesystem = $this->filesystem_with_source();
		$editor     = new FakeConversionEditor( $filesystem );
		$editor->result(
			ConversionEditorResult::failure(
				ConversionResultCode::EDITOR_LOAD_FAILED,
				'Editor load failed.',
				array(
					'path'     => self::UPLOADS . '/2026/07/hero.jpg',
					'wp_error' => new stdClass(),
				)
			)
		);

		$result = $this->converter( $filesystem, $editor )->convert(
			new ConversionRequest( $this->source(), $this->destination(), 82, 5.0 )
		);

		self::assertTrue( $result->is_failed() );
		self::assertSame( ConversionResultCode::EDITOR_LOAD_FAILED, $result->code() );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Unit test intentionally avoids WordPress bootstrap.
		self::assertStringNotContainsString( self::UPLOADS, (string) json_encode( $result->to_array() ) );
	}

	/**
	 * Test quality is clamped and passed to editor.
	 *
	 * @return void
	 */
	public function test_quality_is_passed_to_editor_and_clamped(): void {
		$filesystem = $this->filesystem_with_source();
		$editor     = new FakeConversionEditor( $filesystem );

		$result = $this->converter( $filesystem, $editor )->convert(
			new ConversionRequest( $this->source(), $this->destination(), 150, 1.0 )
		);

		self::assertTrue( $result->is_success() );
		self::assertSame( 100, $editor->quality );
		self::assertSame( 100, $result->output()->quality() );
	}

	/**
	 * Test output MIME mismatch fails and cleans temporary file.
	 *
	 * @return void
	 */
	public function test_output_mime_mismatch_returns_validation_failure_and_cleans_temp(): void {
		$filesystem = $this->filesystem_with_source();
		$editor     = new FakeConversionEditor( $filesystem );
		$editor->output( 300, 'image/png', 100, 100 );

		$result = $this->converter( $filesystem, $editor )->convert(
			new ConversionRequest( $this->source(), $this->destination(), 82, 5.0 )
		);

		self::assertTrue( $result->is_failed() );
		self::assertSame( ConversionResultCode::OUTPUT_VALIDATION_FAILED, $result->code() );
		self::assertSame( 'mime_mismatch', $result->details()['reason'] );
		self::assertFalse( $filesystem->exists( self::UPLOADS . '/2026/07/hero.jpg.hwlio.webp.tmp' ) );
	}

	/**
	 * Test output dimensions mismatch fails and cleans temporary file.
	 *
	 * @return void
	 */
	public function test_output_dimensions_mismatch_returns_validation_failure(): void {
		$filesystem = $this->filesystem_with_source();
		$editor     = new FakeConversionEditor( $filesystem );
		$editor->output( 300, 'image/webp', 101, 100 );

		$result = $this->converter( $filesystem, $editor )->convert(
			new ConversionRequest( $this->source(), $this->destination(), 82, 5.0 )
		);

		self::assertSame( ConversionResultCode::OUTPUT_VALIDATION_FAILED, $result->code() );
		self::assertSame( 'dimension_mismatch', $result->details()['reason'] );
		self::assertFalse( $filesystem->exists( self::UPLOADS . '/2026/07/hero.jpg.hwlio.webp.tmp' ) );
	}

	/**
	 * Test missing or zero-byte output fails.
	 *
	 * @return void
	 */
	public function test_zero_byte_output_returns_validation_failure(): void {
		$filesystem = $this->filesystem_with_source();
		$editor     = new FakeConversionEditor( $filesystem );
		$editor->output( 0, 'image/webp', 100, 100 );

		$result = $this->converter( $filesystem, $editor )->convert(
			new ConversionRequest( $this->source(), $this->destination(), 82, 5.0 )
		);

		self::assertSame( ConversionResultCode::OUTPUT_VALIDATION_FAILED, $result->code() );
		self::assertSame( 'empty_output', $result->details()['reason'] );
	}

	/**
	 * Test generated output below threshold is skipped and deleted.
	 *
	 * @return void
	 */
	public function test_output_below_minimum_savings_is_skipped_and_deleted(): void {
		$filesystem = $this->filesystem_with_source();
		$editor     = new FakeConversionEditor( $filesystem );
		$editor->output( 950, 'image/webp', 100, 100 );

		$result = $this->converter( $filesystem, $editor )->convert(
			new ConversionRequest( $this->source(), $this->destination(), 82, 10.0 )
		);

		self::assertTrue( $result->is_skipped() );
		self::assertSame( ConversionResultCode::SKIPPED_NOT_SMALLER, $result->code() );
		self::assertSame( 5.0, $result->savings()->savings_percent() );
		self::assertFalse( $filesystem->exists( self::UPLOADS . '/2026/07/hero.jpg.hwlio.webp.tmp' ) );
	}

	/**
	 * Test equal savings threshold is accepted.
	 *
	 * @return void
	 */
	public function test_equal_to_threshold_savings_is_accepted(): void {
		$filesystem = $this->filesystem_with_source();
		$editor     = new FakeConversionEditor( $filesystem );
		$editor->output( 900, 'image/webp', 100, 100 );

		$result = $this->converter( $filesystem, $editor )->convert(
			new ConversionRequest( $this->source(), $this->destination(), 82, 10.0 )
		);

		self::assertTrue( $result->is_success() );
		self::assertSame( 10.0, $result->savings()->savings_percent() );
	}

	/**
	 * Test larger derivative is skipped and deleted.
	 *
	 * @return void
	 */
	public function test_larger_derivative_is_skipped(): void {
		$filesystem = $this->filesystem_with_source();
		$editor     = new FakeConversionEditor( $filesystem );
		$editor->output( 1100, 'image/webp', 100, 100 );

		$result = $this->converter( $filesystem, $editor )->convert(
			new ConversionRequest( $this->source(), $this->destination(), 82, 0.0 )
		);

		self::assertTrue( $result->is_skipped() );
		self::assertSame( -10.0, $result->savings()->savings_percent() );
		self::assertFalse( $filesystem->exists( self::UPLOADS . '/2026/07/hero.jpg.hwlio.webp.tmp' ) );
	}

	/**
	 * Test rename failure returns atomic move failure and cleans temp.
	 *
	 * @return void
	 */
	public function test_atomic_rename_failure_returns_failure_and_cleans_temp(): void {
		$filesystem                   = $this->filesystem_with_source();
		$filesystem->move_should_fail = true;
		$editor                       = new FakeConversionEditor( $filesystem );

		$result = $this->converter( $filesystem, $editor )->convert(
			new ConversionRequest( $this->source(), $this->destination(), 82, 5.0 )
		);

		self::assertTrue( $result->is_failed() );
		self::assertSame( ConversionResultCode::ATOMIC_MOVE_FAILED, $result->code() );
		self::assertFalse( $filesystem->exists( self::UPLOADS . '/2026/07/hero.jpg.hwlio.webp.tmp' ) );
		self::assertFalse( $filesystem->exists( self::UPLOADS . '/2026/07/hero.jpg.hwlio.webp' ) );
	}

	/**
	 * Test existing final destination is not overwritten.
	 *
	 * @return void
	 */
	public function test_existing_final_destination_is_not_overwritten(): void {
		$filesystem = $this->filesystem_with_source();
		$filesystem->add_file( self::UPLOADS . '/2026/07/hero.jpg.hwlio.webp', 500, 'image/webp', 100, 100 );
		$editor = new FakeConversionEditor( $filesystem );

		$result = $this->converter( $filesystem, $editor )->convert(
			new ConversionRequest( $this->source(), $this->destination(), 82, 5.0 )
		);

		self::assertTrue( $result->is_failed() );
		self::assertSame( ConversionResultCode::DESTINATION_COLLISION, $result->code() );
		self::assertSame( 0, $editor->save_calls );
		self::assertSame( 500, $filesystem->file_size( self::UPLOADS . '/2026/07/hero.jpg.hwlio.webp' ) );
	}

	/**
	 * Test replace mode safely replaces an existing destination.
	 *
	 * @return void
	 */
	public function test_replace_mode_safely_replaces_existing_destination(): void {
		$filesystem = $this->filesystem_with_source();
		$filesystem->add_file( self::UPLOADS . '/2026/07/hero.jpg.hwlio.webp', 500, 'image/webp', 100, 100 );
		$editor = new FakeConversionEditor( $filesystem );
		$editor->output( 300, 'image/webp', 100, 100 );

		$result = $this->converter( $filesystem, $editor )->convert(
			new ConversionRequest( $this->source(), $this->destination(), 82, 5.0, true )
		);

		self::assertTrue( $result->is_success() );
		self::assertSame( 300, $filesystem->file_size( self::UPLOADS . '/2026/07/hero.jpg.hwlio.webp' ) );
		self::assertFalse( $filesystem->exists( self::UPLOADS . '/2026/07/hero.jpg.hwlio.webp.bak' ) );
	}

	/**
	 * Test replace mode rolls back when the final move fails.
	 *
	 * @return void
	 */
	public function test_replace_mode_rolls_back_when_final_move_fails(): void {
		$filesystem = $this->filesystem_with_source();
		$filesystem->add_file( self::UPLOADS . '/2026/07/hero.jpg.hwlio.webp', 500, 'image/webp', 100, 100 );
		$filesystem->fail_move_for(
			self::UPLOADS . '/2026/07/hero.jpg.hwlio.webp.tmp',
			self::UPLOADS . '/2026/07/hero.jpg.hwlio.webp'
		);
		$editor = new FakeConversionEditor( $filesystem );
		$editor->output( 300, 'image/webp', 100, 100 );

		$result = $this->converter( $filesystem, $editor )->convert(
			new ConversionRequest( $this->source(), $this->destination(), 82, 5.0, true )
		);

		self::assertTrue( $result->is_failed() );
		self::assertSame( ConversionResultCode::ATOMIC_MOVE_FAILED, $result->code() );
		self::assertSame( 500, $filesystem->file_size( self::UPLOADS . '/2026/07/hero.jpg.hwlio.webp' ) );
		self::assertFalse( $filesystem->exists( self::UPLOADS . '/2026/07/hero.jpg.hwlio.webp.bak' ) );
	}

	/**
	 * Test stale temporary output is cleaned before conversion.
	 *
	 * @return void
	 */
	public function test_stale_temporary_file_is_cleaned_before_conversion(): void {
		$filesystem = $this->filesystem_with_source();
		$filesystem->add_file( self::UPLOADS . '/2026/07/hero.jpg.hwlio.webp.tmp', 111, 'image/webp', 100, 100 );
		$editor = new FakeConversionEditor( $filesystem );

		$result = $this->converter( $filesystem, $editor )->convert(
			new ConversionRequest( $this->source(), $this->destination(), 82, 5.0 )
		);

		self::assertTrue( $result->is_success() );
		self::assertContains( self::UPLOADS . '/2026/07/hero.jpg.hwlio.webp.tmp', $filesystem->deleted );
	}

	/**
	 * Test cleanup failure is surfaced without exposing paths.
	 *
	 * @return void
	 */
	public function test_cleanup_failure_is_surfaced_without_exposing_paths(): void {
		$filesystem = $this->filesystem_with_source();
		$filesystem->fail_delete_for( self::UPLOADS . '/2026/07/hero.jpg.hwlio.webp.tmp' );
		$editor = new FakeConversionEditor( $filesystem );
		$editor->output( 950, 'image/webp', 100, 100 );

		$result = $this->converter( $filesystem, $editor )->convert(
			new ConversionRequest( $this->source(), $this->destination(), 82, 10.0 )
		);

		self::assertTrue( $result->is_skipped() );
		self::assertTrue( $result->details()['cleanup_failed'] );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Unit test intentionally avoids WordPress bootstrap.
		self::assertStringNotContainsString( self::UPLOADS, (string) json_encode( $result->to_array() ) );
	}

	/**
	 * Test invalid target format is rejected.
	 *
	 * @return void
	 */
	public function test_invalid_target_format_is_rejected(): void {
		$filesystem  = $this->filesystem_with_source();
		$editor      = new FakeConversionEditor( $filesystem );
		$destination = $this->destination( 'gif', 'image/gif', '2026/07/hero.jpg.hwlio.gif' );

		$result = $this->converter( $filesystem, $editor )->convert(
			new ConversionRequest( $this->source(), $destination, 82, 5.0 )
		);

		self::assertTrue( $result->is_failed() );
		self::assertSame( ConversionResultCode::INVALID_TARGET_FORMAT, $result->code() );
	}

	/**
	 * Test source realpath outside uploads is rejected.
	 *
	 * @return void
	 */
	public function test_source_realpath_outside_uploads_is_rejected(): void {
		$filesystem = new FakeConversionFilesystem();
		$filesystem->add_file(
			self::UPLOADS . '/2026/07/hero.jpg',
			1000,
			'image/jpeg',
			100,
			100,
			true,
			true,
			'D:/outside/hero.jpg'
		);
		$editor = new FakeConversionEditor( $filesystem );

		$result = $this->converter( $filesystem, $editor )->convert(
			new ConversionRequest( $this->source(), $this->destination(), 82, 5.0 )
		);

		self::assertSame( ConversionResultCode::UNSAFE_SOURCE_PATH, $result->code() );
	}

	/**
	 * Test resource guard skips before editor allocation.
	 *
	 * @return void
	 */
	public function test_resource_guard_skips_before_editor_allocation(): void {
		$filesystem = $this->filesystem_with_source( 'image/jpeg', 3000, 3000 );
		$editor     = new FakeConversionEditor( $filesystem );
		$guard      = new ResourceGuard( MemoryLimit::from_raw( '64M' ), 40000000 );
		$source     = $this->source( 'image/jpeg', 3000, 3000 );

		$result = $this->converter( $filesystem, $editor, $guard )->convert(
			new ConversionRequest( $source, $this->destination(), 82, 5.0 )
		);

		self::assertTrue( $result->is_skipped() );
		self::assertSame( ConversionResultCode::SKIPPED_RESOURCE_LIMIT, $result->code() );
		self::assertSame( 'memory_estimate_exceeded', $result->details()['reason'] );
		self::assertSame( 0, $editor->save_calls );
		self::assertFalse( $filesystem->exists( self::UPLOADS . '/2026/07/hero.jpg.hwlio.webp.tmp' ) );
	}

	/**
	 * Build converter.
	 *
	 * @param FakeConversionFilesystem $filesystem Filesystem.
	 * @param FakeConversionEditor     $editor Editor.
	 * @param ResourceGuard|null       $resource_guard Resource guard.
	 * @return ImageConverter
	 */
	private function converter(
		FakeConversionFilesystem $filesystem,
		FakeConversionEditor $editor,
		?ResourceGuard $resource_guard = null
	): ImageConverter {
		return new ImageConverter( $editor, $filesystem, new FakeConversionClock(), $resource_guard );
	}

	/**
	 * Build filesystem with source.
	 *
	 * @param string $mime_type Source MIME.
	 * @param int    $width Width.
	 * @param int    $height Height.
	 * @return FakeConversionFilesystem
	 */
	private function filesystem_with_source( string $mime_type = 'image/jpeg', int $width = 100, int $height = 100 ): FakeConversionFilesystem {
		$filesystem = new FakeConversionFilesystem();
		$filesystem->add_file( self::UPLOADS . '/2026/07/hero.jpg', 1000, $mime_type, $width, $height );

		return $filesystem;
	}

	/**
	 * Build source image.
	 *
	 * @param string $mime_type MIME type.
	 * @param int    $width Width.
	 * @param int    $height Height.
	 * @return SourceImage
	 */
	private function source( string $mime_type = 'image/jpeg', int $width = 100, int $height = 100 ): SourceImage {
		return new SourceImage(
			123,
			'full',
			SourceImage::ROLE_FULL,
			'2026/07/hero.jpg',
			self::UPLOADS . '/2026/07/hero.jpg',
			$mime_type,
			$width,
			$height,
			1000,
			1783526400
		);
	}

	/**
	 * Build destination path.
	 *
	 * @param string $format Target format.
	 * @param string $mime_type Target MIME.
	 * @param string $relative_path Relative path.
	 * @return DestinationPath
	 */
	private function destination(
		string $format = SourceMimePolicy::TARGET_WEBP,
		string $mime_type = 'image/webp',
		string $relative_path = '2026/07/hero.jpg.hwlio.webp'
	): DestinationPath {
		return new DestinationPath(
			$format,
			$mime_type,
			$relative_path,
			self::UPLOADS . '/' . $relative_path,
			$relative_path . '.tmp',
			self::UPLOADS . '/' . $relative_path . '.tmp'
		);
	}
}
