<?php
/**
 * Tests for derivative URL resolution.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Delivery;

use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifestSanitizer;
use HyperWeb\LighthouseImageOptimizer\Delivery\DerivativeUrlRequest;
use HyperWeb\LighthouseImageOptimizer\Delivery\DerivativeUrlResolutionResult;
use HyperWeb\LighthouseImageOptimizer\Delivery\DerivativeUrlResolver;
use PHPUnit\Framework\TestCase;

/**
 * Verifies safe runtime URL resolution from stored derivative paths.
 */
final class DerivativeUrlResolverTest extends TestCase {

	/**
	 * Test valid relative paths resolve against the current uploads base URL.
	 *
	 * @return void
	 */
	public function test_valid_relative_paths_resolve_against_current_uploads_base_url(): void {
		$runtime  = new FakeUploadsUrlRuntime();
		$resolver = $this->resolver( $runtime );
		$request  = new DerivativeUrlRequest( '2026/07/hero.jpg.hwlio.webp', 123, 'full', 'webp' );
		$result   = $resolver->resolve( $request );

		self::assertTrue( $result->is_successful() );
		self::assertSame( DerivativeUrlResolutionResult::CODE_RESOLVED, $result->code() );
		self::assertSame(
			'https://example.test/wp-content/uploads/2026/07/hero.jpg.hwlio.webp',
			$result->url()
		);
		self::assertSame( '2026/07/hero.jpg.hwlio.webp', $runtime->base_url_requests[0]->relative_path() );
		self::assertSame( 0, $runtime->base_dir_reads );
		self::assertSame( 'webp', $runtime->filter_requests[0]['request']->format() );
	}

	/**
	 * Test URL resolution follows current domain and scheme changes.
	 *
	 * @return void
	 */
	public function test_resolution_follows_runtime_domain_and_scheme_changes(): void {
		$runtime            = new FakeUploadsUrlRuntime();
		$runtime->base_url  = 'http://old.example.test/wp-content/uploads';
		$resolver           = $this->resolver( $runtime );
		$request            = new DerivativeUrlRequest( '2026/07/hero.jpg.hwlio.avif', 123, 'full', 'avif' );
		$first              = $resolver->resolve( $request );
		$runtime->base_url  = 'https://cdn.example.test/wp-content/uploads';
		$second             = $resolver->resolve( $request );

		self::assertSame(
			'http://old.example.test/wp-content/uploads/2026/07/hero.jpg.hwlio.avif',
			$first->url()
		);
		self::assertSame(
			'https://cdn.example.test/wp-content/uploads/2026/07/hero.jpg.hwlio.avif',
			$second->url()
		);
		self::assertSame( $first->relative_path(), $second->relative_path() );
	}

	/**
	 * Test invalid relative path forms are rejected.
	 *
	 * @return void
	 */
	public function test_invalid_relative_paths_are_rejected(): void {
		$paths = array(
			'',
			'../hero.jpg.hwlio.webp',
			'/var/www/uploads/hero.jpg.hwlio.webp',
			'https://example.test/hero.jpg.hwlio.webp',
			'2026//07/hero.jpg.hwlio.webp',
			'C:\\uploads\\hero.jpg.hwlio.webp',
		);

		foreach ( $paths as $path ) {
			$runtime  = new FakeUploadsUrlRuntime();
			$resolver = $this->resolver( $runtime );
			$result   = $resolver->resolve( new DerivativeUrlRequest( $path, 123, 'full', 'webp' ) );

			self::assertFalse( $result->is_successful(), $path );
			self::assertSame( DerivativeUrlResolutionResult::CODE_INVALID_RELATIVE_PATH, $result->code(), $path );
			self::assertCount( 0, $runtime->base_url_requests, $path );
		}
	}

	/**
	 * Test uploads errors or empty base URLs return invalid results.
	 *
	 * @return void
	 */
	public function test_uploads_errors_or_empty_base_urls_return_invalid_results(): void {
		$runtime           = new FakeUploadsUrlRuntime();
		$runtime->base_url = '';
		$resolver          = $this->resolver( $runtime );
		$result            = $resolver->resolve( new DerivativeUrlRequest( '2026/07/hero.jpg.hwlio.webp' ) );

		self::assertFalse( $result->is_successful() );
		self::assertSame( DerivativeUrlResolutionResult::CODE_UPLOADS_URL_UNAVAILABLE, $result->code() );

		$runtime->base_url = null;
		$result            = $resolver->resolve( new DerivativeUrlRequest( '2026/07/hero.jpg.hwlio.webp' ) );

		self::assertSame( DerivativeUrlResolutionResult::CODE_UPLOADS_URL_UNAVAILABLE, $result->code() );
	}

	/**
	 * Test filters can deterministically rewrite the final derivative URL.
	 *
	 * @return void
	 */
	public function test_runtime_filters_can_rewrite_the_final_derivative_url(): void {
		$runtime                  = new FakeUploadsUrlRuntime();
		$runtime->base_url        = 'https://example.test/wp-content/uploads/';
		$runtime->filter_callback = static function ( string $url, DerivativeUrlRequest $request ): string {
			return sprintf(
				'https://cdn.example.test/%d/%s/%s',
				$request->attachment_id(),
				$request->format(),
				basename( $url )
			);
		};
		$resolver = $this->resolver( $runtime );
		$result   = $resolver->resolve(
			new DerivativeUrlRequest( '2026/07/hero.jpg.hwlio.avif', 88, 'full', 'avif' )
		);

		self::assertTrue( $result->is_successful() );
		self::assertSame(
			'https://cdn.example.test/88/avif/hero.jpg.hwlio.avif',
			$result->url()
		);
		self::assertSame(
			'https://example.test/wp-content/uploads/2026/07/hero.jpg.hwlio.avif',
			$runtime->filter_requests[0]['url']
		);
	}

	/**
	 * Test serialized results never expose absolute filesystem paths.
	 *
	 * @return void
	 */
	public function test_serialized_results_never_expose_absolute_filesystem_paths(): void {
		$runtime  = new FakeUploadsUrlRuntime();
		$resolver = $this->resolver( $runtime );
		$result   = $resolver->resolve(
			new DerivativeUrlRequest( '2026/07/hero.jpg.hwlio.webp', 123, 'full', 'webp' )
		);
		$payload  = $result->to_array();

		self::assertSame( '2026/07/hero.jpg.hwlio.webp', $payload['relative_path'] );
		self::assertSame(
			'https://example.test/wp-content/uploads/2026/07/hero.jpg.hwlio.webp',
			$payload['url']
		);
		self::assertStringNotContainsString( 'C:/', json_encode( $payload ) ?: '' );
		self::assertStringNotContainsString( '/var/www/', json_encode( $payload ) ?: '' );
	}

	/**
	 * Build resolver.
	 *
	 * @param FakeUploadsUrlRuntime $runtime Runtime.
	 * @return DerivativeUrlResolver
	 */
	private function resolver( FakeUploadsUrlRuntime $runtime ): DerivativeUrlResolver {
		return new DerivativeUrlResolver( $runtime, new DerivativeManifestSanitizer() );
	}
}
