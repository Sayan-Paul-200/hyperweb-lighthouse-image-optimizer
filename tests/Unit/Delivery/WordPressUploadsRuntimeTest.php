<?php
/**
 * Tests for the WordPress uploads runtime.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Delivery;

require_once __DIR__ . '/DeliveryTestWordPressShim.php';

use HyperWeb\LighthouseImageOptimizer\Delivery\DeliveryHookPolicy;
use HyperWeb\LighthouseImageOptimizer\Delivery\DerivativeUrlRequest;
use HyperWeb\LighthouseImageOptimizer\Delivery\WordPressUploadsRuntime;
use PHPUnit\Framework\TestCase;

/**
 * Verifies uploads URL and directory resolution through WordPress APIs.
 */
final class WordPressUploadsRuntimeTest extends TestCase {

	/**
	 * Clean up shim globals.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		unset( $GLOBALS['hwlio_test_wp_upload_dir'], $GLOBALS['hwlio_test_filters'] );
	}

	/**
	 * Test uploads errors and invalid payloads return null.
	 *
	 * @return void
	 */
	public function test_uploads_errors_and_invalid_payloads_return_null(): void {
		$runtime = new WordPressUploadsRuntime();
		$request = new DerivativeUrlRequest( '2026/07/hero.jpg.hwlio.webp', 123, 'full', 'webp' );

		$GLOBALS['hwlio_test_wp_upload_dir'] = null;
		self::assertNull( $runtime->uploads_base_url( $request ) );
		self::assertNull( $runtime->uploads_base_dir() );

		$GLOBALS['hwlio_test_wp_upload_dir'] = array(
			'error' => 'Uploads unavailable.',
		);
		self::assertNull( $runtime->uploads_base_url( $request ) );
		self::assertNull( $runtime->uploads_base_dir() );

		$GLOBALS['hwlio_test_wp_upload_dir'] = array(
			'error'   => '',
			'baseurl' => '',
			'basedir' => '',
		);
		self::assertNull( $runtime->uploads_base_url( $request ) );
		self::assertNull( $runtime->uploads_base_dir() );
	}

	/**
	 * Test uploads base URL, base directory, and derivative URL filters are applied with context.
	 *
	 * @return void
	 */
	public function test_uploads_base_url_and_derivative_url_filters_are_applied_with_context(): void {
		$runtime = new WordPressUploadsRuntime();
		$request = new DerivativeUrlRequest( '2026/07/hero.jpg.hwlio.avif', 321, 'large', 'avif' );

		$GLOBALS['hwlio_test_wp_upload_dir'] = array(
			'error'   => '',
			'baseurl' => 'https://example.test/wp-content/uploads',
			'basedir' => 'C:/site/wp-content/uploads',
		);
		$GLOBALS['hwlio_test_filters']       = array(
			DeliveryHookPolicy::FILTER_UPLOADS_BASE_URL => static function (
				string $base_url,
				string $relative_path,
				?int $attachment_id,
				?string $size_name,
				?string $format,
				array $context
			): string {
				TestCase::assertSame( 'https://example.test/wp-content/uploads', $base_url );
				TestCase::assertSame( '2026/07/hero.jpg.hwlio.avif', $relative_path );
				TestCase::assertSame( 321, $attachment_id );
				TestCase::assertSame( 'large', $size_name );
				TestCase::assertSame( 'avif', $format );
				TestCase::assertSame( $relative_path, $context['relative_path'] );
				TestCase::assertSame( $attachment_id, $context['attachment_id'] );
				TestCase::assertSame( $size_name, $context['size_name'] );
				TestCase::assertSame( $format, $context['format'] );
				TestCase::assertSame( $base_url, $context['base_url'] );
				TestCase::assertInstanceOf( DerivativeUrlRequest::class, $context['request'] );

				return 'https://cdn.example.test/uploads';
			},
			DeliveryHookPolicy::FILTER_DERIVATIVE_URL   => static function (
				string $url,
				string $relative_path,
				?int $attachment_id,
				?string $size_name,
				?string $format,
				array $context
			): string {
				TestCase::assertSame(
					'https://cdn.example.test/uploads/2026/07/hero.jpg.hwlio.avif',
					$url
				);
				TestCase::assertSame( '2026/07/hero.jpg.hwlio.avif', $relative_path );
				TestCase::assertSame( 321, $attachment_id );
				TestCase::assertSame( 'large', $size_name );
				TestCase::assertSame( 'avif', $format );
				TestCase::assertSame( $relative_path, $context['relative_path'] );
				TestCase::assertSame( $attachment_id, $context['attachment_id'] );
				TestCase::assertSame( $size_name, $context['size_name'] );
				TestCase::assertSame( $format, $context['format'] );
				TestCase::assertSame( $url, $context['url'] );
				TestCase::assertInstanceOf( DerivativeUrlRequest::class, $context['request'] );

				return 'https://edge.example.test/hero.jpg.hwlio.avif';
			},
		);

		self::assertSame( 'https://cdn.example.test/uploads', $runtime->uploads_base_url( $request ) );
		self::assertSame( 'C:/site/wp-content/uploads', $runtime->uploads_base_dir() );
		self::assertSame(
			'https://edge.example.test/hero.jpg.hwlio.avif',
			$runtime->filter_derivative_url(
				'https://cdn.example.test/uploads/2026/07/hero.jpg.hwlio.avif',
				$request
			)
		);
	}

	/**
	 * Test invalid derivative URL filter results fall back to the original URL.
	 *
	 * @return void
	 */
	public function test_invalid_derivative_url_filter_results_fall_back_to_the_original_url(): void {
		$runtime = new WordPressUploadsRuntime();
		$request = new DerivativeUrlRequest( '2026/07/hero.jpg.hwlio.webp' );

		$GLOBALS['hwlio_test_filters'] = array(
			DeliveryHookPolicy::FILTER_DERIVATIVE_URL => static function (): array {
				return array( 'invalid' );
			},
		);

		self::assertSame(
			'https://example.test/wp-content/uploads/2026/07/hero.jpg.hwlio.webp',
			$runtime->filter_derivative_url(
				'https://example.test/wp-content/uploads/2026/07/hero.jpg.hwlio.webp',
				$request
			)
		);
	}
}
