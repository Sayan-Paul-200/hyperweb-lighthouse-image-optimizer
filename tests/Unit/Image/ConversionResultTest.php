<?php
/**
 * Tests for conversion result models.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image;

use HyperWeb\LighthouseImageOptimizer\Image\ConversionOutput;
use HyperWeb\LighthouseImageOptimizer\Image\ConversionResult;
use HyperWeb\LighthouseImageOptimizer\Image\ConversionResultCode;
use HyperWeb\LighthouseImageOptimizer\Image\ConversionResultCollection;
use HyperWeb\LighthouseImageOptimizer\Image\ConversionResultSanitizer;
use HyperWeb\LighthouseImageOptimizer\Image\ConversionSavings;
use HyperWeb\LighthouseImageOptimizer\Image\DestinationPath;
use HyperWeb\LighthouseImageOptimizer\Image\SourceImage;
use HyperWeb\LighthouseImageOptimizer\Image\SourceMimePolicy;
use PHPUnit\Framework\TestCase;

/**
 * Verifies conversion result taxonomy and serialization.
 */
final class ConversionResultTest extends TestCase {

	private const UPLOADS = 'C:/site/wp-content/uploads';

	/**
	 * Test result status normalization.
	 *
	 * @return void
	 */
	public function test_valid_and_invalid_statuses_are_normalized(): void {
		self::assertSame( ConversionResult::STATUS_SUCCESS, ConversionResult::normalize_status( 'success' ) );
		self::assertSame( ConversionResult::STATUS_SKIPPED, ConversionResult::normalize_status( ' skipped ' ) );
		self::assertSame( ConversionResult::STATUS_FAILED, ConversionResult::normalize_status( 'failed' ) );
		self::assertSame( ConversionResult::STATUS_FAILED, ConversionResult::normalize_status( 'unexpected' ) );
	}

	/**
	 * Test result code normalization for each status.
	 *
	 * @return void
	 */
	public function test_valid_and_invalid_codes_are_normalized_by_status(): void {
		self::assertSame(
			ConversionResultCode::OPTIMIZED,
			ConversionResultCode::normalize_for_status( ConversionResult::STATUS_SUCCESS, ConversionResultCode::OPTIMIZED )
		);
		self::assertSame(
			ConversionResultCode::ALREADY_CURRENT,
			ConversionResultCode::normalize_for_status( ConversionResult::STATUS_SUCCESS, ConversionResultCode::ALREADY_CURRENT )
		);
		self::assertSame(
			ConversionResultCode::OPTIMIZED,
			ConversionResultCode::normalize_for_status( ConversionResult::STATUS_SUCCESS, 'not stable' )
		);
		self::assertSame(
			ConversionResultCode::SKIPPED_NOT_SMALLER,
			ConversionResultCode::normalize_for_status( ConversionResult::STATUS_SKIPPED, ConversionResultCode::SKIPPED_NOT_SMALLER )
		);
		self::assertSame(
			ConversionResultCode::SKIPPED_UNKNOWN,
			ConversionResultCode::normalize_for_status( ConversionResult::STATUS_SKIPPED, 'not stable' )
		);
		self::assertSame(
			ConversionResultCode::EDITOR_LOAD_FAILED,
			ConversionResultCode::normalize_for_status( ConversionResult::STATUS_FAILED, ConversionResultCode::EDITOR_LOAD_FAILED )
		);
		self::assertSame(
			ConversionResultCode::CONVERSION_FAILED,
			ConversionResultCode::normalize_for_status( ConversionResult::STATUS_FAILED, 'not stable' )
		);
	}

	/**
	 * Test conversion taxonomy includes master-plan and phase-three codes.
	 *
	 * @return void
	 */
	public function test_conversion_code_taxonomy_contains_required_codes(): void {
		$expected = array(
			ConversionResultCode::OPTIMIZED,
			ConversionResultCode::ALREADY_CURRENT,
			ConversionResultCode::SKIPPED_UNSUPPORTED_SOURCE_MIME,
			ConversionResultCode::SKIPPED_TARGET_NOT_ENABLED,
			ConversionResultCode::SKIPPED_TARGET_NOT_SUPPORTED,
			ConversionResultCode::SKIPPED_ANIMATED_IMAGE,
			ConversionResultCode::SKIPPED_NOT_SMALLER,
			ConversionResultCode::SKIPPED_RESOURCE_LIMIT,
			ConversionResultCode::SKIPPED_EXCLUDED,
			ConversionResultCode::SKIPPED_OUTSIDE_UPLOADS,
			ConversionResultCode::SOURCE_MISSING,
			ConversionResultCode::SOURCE_UNREADABLE,
			ConversionResultCode::SOURCE_INVALID_MIME,
			ConversionResultCode::SOURCE_CORRUPT,
			ConversionResultCode::UPLOADS_UNAVAILABLE,
			ConversionResultCode::UNSAFE_SOURCE_PATH,
			ConversionResultCode::INVALID_TARGET_FORMAT,
			ConversionResultCode::DESTINATION_OUTSIDE_UPLOADS,
			ConversionResultCode::TEMPORARY_OUTSIDE_UPLOADS,
			ConversionResultCode::DESTINATION_COLLISION,
			ConversionResultCode::TEMPORARY_COLLISION,
			ConversionResultCode::DESTINATION_REALPATH_OUTSIDE_UPLOADS,
			ConversionResultCode::TEMPORARY_REALPATH_OUTSIDE_UPLOADS,
			ConversionResultCode::EDITOR_UNAVAILABLE,
			ConversionResultCode::EDITOR_LOAD_FAILED,
			ConversionResultCode::CONVERSION_FAILED,
			ConversionResultCode::TEMPORARY_WRITE_FAILED,
			ConversionResultCode::OUTPUT_VALIDATION_FAILED,
			ConversionResultCode::ATOMIC_MOVE_FAILED,
			ConversionResultCode::METADATA_WRITE_FAILED,
			ConversionResultCode::LOCK_UNAVAILABLE,
			ConversionResultCode::QUEUE_UNAVAILABLE,
			ConversionResultCode::PERMISSION_DENIED,
			ConversionResultCode::INVALID_JOB_PAYLOAD,
		);

		foreach ( $expected as $code ) {
			self::assertContains( $code, ConversionResultCode::all_codes() );
		}
	}

	/**
	 * Test successful result serialization.
	 *
	 * @return void
	 */
	public function test_success_result_serializes_source_destination_output_and_savings(): void {
		$source      = $this->source();
		$destination = $this->destination();
		$output      = $this->output();
		$savings     = ConversionSavings::from_source_and_output( $source, $output, 5.0 );

		$result = ConversionResult::success( $source, $destination, $output, $savings, array( 'attempt' => 1 ) );

		self::assertTrue( $result->is_success() );
		self::assertSame( ConversionResultCode::OPTIMIZED, $result->code() );
		self::assertSame( SourceMimePolicy::TARGET_WEBP, $result->target_format() );
		self::assertSame( 'image/webp', $result->target_mime() );

		$serialized = $result->to_array();

		self::assertSame( '2026/07/hero.jpg', $serialized['source']['relative_path'] );
		self::assertSame( '2026/07/hero.jpg.hwlio.webp', $serialized['destination']['relative_path'] );
		self::assertSame( '2026/07/hero.jpg.hwlio.webp', $serialized['output']['relative_path'] );
		self::assertSame( 82, $serialized['output']['quality'] );
		self::assertSame( 1783526500, $serialized['output']['generated_at'] );
		self::assertSame( 1000, $serialized['savings']['source_bytes'] );
		self::assertSame( 300, $serialized['savings']['output_bytes'] );
		self::assertSame( 700, $serialized['savings']['savings_bytes'] );
		self::assertSame( 70.0, $serialized['savings']['savings_percent'] );
		self::assertTrue( $serialized['savings']['meets_minimum'] );
	}

	/**
	 * Test already-current result.
	 *
	 * @return void
	 */
	public function test_already_current_is_a_success_result(): void {
		$result = ConversionResult::already_current(
			$this->source(),
			$this->destination(),
			$this->output(),
			new ConversionSavings( 1000, 300, 5.0 )
		);

		self::assertTrue( $result->is_success() );
		self::assertSame( ConversionResultCode::ALREADY_CURRENT, $result->code() );
		self::assertSame( ConversionResult::STATUS_SUCCESS, $result->status() );
	}

	/**
	 * Test skipped not-smaller results carry negative savings.
	 *
	 * @return void
	 */
	public function test_skipped_not_smaller_result_carries_negative_savings(): void {
		$savings = new ConversionSavings( 1000, 1050, 5.0 );
		$result  = ConversionResult::skipped(
			$this->source(),
			SourceMimePolicy::TARGET_WEBP,
			'image/webp',
			ConversionResultCode::SKIPPED_NOT_SMALLER,
			'Derivative was larger than the source.',
			$savings,
			$this->destination()
		);

		self::assertTrue( $result->is_skipped() );
		self::assertSame( ConversionResultCode::SKIPPED_NOT_SMALLER, $result->code() );
		self::assertSame( -50, $result->savings()->savings_bytes() );
		self::assertSame( -5.0, $result->savings()->savings_percent() );
		self::assertFalse( $result->savings()->meets_minimum() );
	}

	/**
	 * Test failed result codes.
	 *
	 * @return void
	 */
	public function test_failed_result_supports_converter_failure_codes(): void {
		$codes = array(
			ConversionResultCode::EDITOR_UNAVAILABLE,
			ConversionResultCode::EDITOR_LOAD_FAILED,
			ConversionResultCode::CONVERSION_FAILED,
			ConversionResultCode::OUTPUT_VALIDATION_FAILED,
			ConversionResultCode::ATOMIC_MOVE_FAILED,
		);

		foreach ( $codes as $code ) {
			$result = ConversionResult::failed(
				$this->source(),
				SourceMimePolicy::TARGET_WEBP,
				'image/webp',
				$code,
				'Conversion failed.'
			);

			self::assertTrue( $result->is_failed() );
			self::assertSame( $code, $result->code() );
		}
	}

	/**
	 * Test savings calculations for common outcomes.
	 *
	 * @return void
	 */
	public function test_savings_calculations_handle_smaller_equal_larger_zero_and_missing_output(): void {
		$smaller = new ConversionSavings( 1000, 700, 5.0 );
		$equal   = new ConversionSavings( 1000, 1000, 5.0 );
		$larger  = new ConversionSavings( 1000, 1100, 5.0 );
		$zero    = new ConversionSavings( 0, 10, 5.0 );
		$missing = new ConversionSavings( 1000, null, 5.0 );

		self::assertSame( 300, $smaller->savings_bytes() );
		self::assertSame( 30.0, $smaller->savings_percent() );
		self::assertTrue( $smaller->meets_minimum() );
		self::assertSame( 0, $equal->savings_bytes() );
		self::assertSame( 0.0, $equal->savings_percent() );
		self::assertFalse( $equal->meets_minimum() );
		self::assertSame( -100, $larger->savings_bytes() );
		self::assertSame( -10.0, $larger->savings_percent() );
		self::assertFalse( $larger->meets_minimum() );
		self::assertSame( -10, $zero->savings_bytes() );
		self::assertNull( $zero->savings_percent() );
		self::assertNull( $zero->meets_minimum() );
		self::assertNull( $missing->output_bytes() );
		self::assertNull( $missing->savings_bytes() );
		self::assertNull( $missing->savings_percent() );
		self::assertNull( $missing->meets_minimum() );
	}

	/**
	 * Test output metadata normalization.
	 *
	 * @return void
	 */
	public function test_output_metadata_normalizes_public_fields(): void {
		$output = new ConversionOutput(
			'/2026\07\hero.jpg.hwlio.webp',
			' IMAGE/WEBP ',
			0,
			-5,
			-10,
			150,
			-1
		);

		self::assertSame( '2026/07/hero.jpg.hwlio.webp', $output->relative_path() );
		self::assertSame( 'image/webp', $output->mime_type() );
		self::assertSame( 1, $output->width() );
		self::assertSame( 1, $output->height() );
		self::assertSame( 0, $output->bytes() );
		self::assertSame( 100, $output->quality() );
		self::assertSame( 0, $output->generated_at() );
		self::assertArrayNotHasKey( 'absolute_path', $output->to_array() );
	}

	/**
	 * Test collection filtering and summary.
	 *
	 * @return void
	 */
	public function test_collection_filters_results_and_summarizes_counts(): void {
		$success = ConversionResult::success(
			$this->source(),
			$this->destination(),
			$this->output(),
			new ConversionSavings( 1000, 300 )
		);
		$skipped = ConversionResult::skipped(
			$this->source(),
			SourceMimePolicy::TARGET_WEBP,
			'image/webp',
			ConversionResultCode::SKIPPED_TARGET_NOT_ENABLED,
			'Target disabled.',
			new ConversionSavings( 1000, null )
		);
		$failed  = ConversionResult::failed(
			$this->source(),
			SourceMimePolicy::TARGET_WEBP,
			'image/webp',
			ConversionResultCode::CONVERSION_FAILED,
			'Conversion failed.'
		);

		$collection = new ConversionResultCollection( array( $success, $skipped, $failed, 'invalid' ) );

		self::assertCount( 3, $collection->results() );
		self::assertCount( 1, $collection->successful() );
		self::assertCount( 1, $collection->skipped() );
		self::assertCount( 1, $collection->failed() );
		self::assertTrue( $collection->has_failures() );
		self::assertSame(
			array(
				'total'   => 3,
				'success' => 1,
				'skipped' => 1,
				'failed'  => 1,
			),
			$collection->summary()
		);
		self::assertArrayHasKey( 'summary', $collection->to_array() );
	}

	/**
	 * Test message and details sanitization.
	 *
	 * @return void
	 */
	public function test_result_sanitizes_absolute_paths_and_sensitive_details(): void {
		$result = ConversionResult::failed(
			$this->source(),
			SourceMimePolicy::TARGET_WEBP,
			'image/webp',
			ConversionResultCode::EDITOR_LOAD_FAILED,
			'Editor failed at C:\site\wp-content\uploads\hero.jpg and /var/www/uploads/hero.jpg.',
			null,
			null,
			null,
			array(
				'path'          => '/var/www/uploads/hero.jpg',
				'Authorization' => 'Bearer secret',
				'nested'        => array(
					'api_key' => 'secret-key',
					'file'    => 'C:\site\wp-content\uploads\hero.jpg',
				),
			)
		);

		self::assertStringNotContainsString( 'C:\site', $result->message() );
		self::assertStringNotContainsString( '/var/www', $result->message() );
		self::assertSame( ConversionResultSanitizer::REDACTED_PATH, $result->details()['path'] );
		self::assertSame( ConversionResultSanitizer::REDACTED, $result->details()['authorization'] );
		self::assertSame( ConversionResultSanitizer::REDACTED, $result->details()['nested']['api_key'] );
		self::assertSame( ConversionResultSanitizer::REDACTED_PATH, $result->details()['nested']['file'] );
	}

	/**
	 * Test raw objects and resources are replaced, not serialized.
	 *
	 * @return void
	 */
	public function test_raw_objects_and_resources_are_not_serialized(): void {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- In-memory stream for sanitizer coverage.
		$stream = fopen( 'php://memory', 'rb' );

		$result = ConversionResult::failed(
			$this->source(),
			SourceMimePolicy::TARGET_WEBP,
			'image/webp',
			ConversionResultCode::CONVERSION_FAILED,
			'Conversion failed.',
			null,
			null,
			null,
			array(
				'wp_error' => new class() {
					/**
					 * Get error code.
					 *
					 * @return string
					 */
					public function get_error_code(): string {
						return 'raw_error';
					}
				},
				'stream'   => $stream,
			)
		);

		if ( is_resource( $stream ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closes in-memory stream from sanitizer test.
			fclose( $stream );
		}

		self::assertSame( ConversionResultSanitizer::UNSUPPORTED, $result->details()['wp_error'] );
		self::assertSame( ConversionResultSanitizer::UNSUPPORTED, $result->details()['stream'] );
		self::assertTrue( $result->details()[ ConversionResultSanitizer::TRUNCATED_KEY ] );
	}

	/**
	 * Build a source image.
	 *
	 * @return SourceImage
	 */
	private function source(): SourceImage {
		return new SourceImage(
			123,
			'full',
			SourceImage::ROLE_FULL,
			'2026/07/hero.jpg',
			self::UPLOADS . '/2026/07/hero.jpg',
			'image/jpeg',
			100,
			100,
			1000,
			1783526400
		);
	}

	/**
	 * Build destination path.
	 *
	 * @return DestinationPath
	 */
	private function destination(): DestinationPath {
		return new DestinationPath(
			SourceMimePolicy::TARGET_WEBP,
			'image/webp',
			'2026/07/hero.jpg.hwlio.webp',
			self::UPLOADS . '/2026/07/hero.jpg.hwlio.webp',
			'2026/07/hero.jpg.hwlio.webp.tmp',
			self::UPLOADS . '/2026/07/hero.jpg.hwlio.webp.tmp'
		);
	}

	/**
	 * Build conversion output.
	 *
	 * @return ConversionOutput
	 */
	private function output(): ConversionOutput {
		return new ConversionOutput(
			'2026/07/hero.jpg.hwlio.webp',
			'image/webp',
			100,
			100,
			300,
			82,
			1783526500
		);
	}
}
