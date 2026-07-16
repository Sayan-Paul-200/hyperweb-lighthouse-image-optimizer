<?php
/**
 * Tests for local uploads URL attachment resolver.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Delivery;

use HyperWeb\LighthouseImageOptimizer\Delivery\LocalUploadAttachmentResolution;
use HyperWeb\LighthouseImageOptimizer\Delivery\LocalUploadAttachmentResolver;
use HyperWeb\LighthouseImageOptimizer\Reporting\TrustedAttachmentMarkerParser;
use PHPUnit\Framework\TestCase;

/**
 * Verifies safe raw uploads URL attachment resolution.
 */
final class LocalUploadAttachmentResolverTest extends TestCase {

	/**
	 * Test trusted markers resolve before URL lookup.
	 *
	 * @return void
	 */
	public function test_trusted_marker_resolves_before_url_lookup(): void {
		$lookup_calls = 0;
		$resolver     = new LocalUploadAttachmentResolver(
			static function (): string {
				return 'https://example.test/wp-content/uploads';
			},
			static function () use ( &$lookup_calls ): int {
				++$lookup_calls;

				return 999;
			},
			new TrustedAttachmentMarkerParser()
		);

		$result = $resolver->resolve( '<img class="wp-image-123" src="https://example.test/wp-content/uploads/2026/07/hero.jpg?ver=1" alt="Hero">' );

		self::assertTrue( $result->is_resolved() );
		self::assertSame( 123, $result->attachment_id() );
		self::assertSame( LocalUploadAttachmentResolution::CODE_RESOLVED_TRUSTED_MARKER, $result->code() );
		self::assertSame( '2026/07/hero.jpg', $result->relative_path() );
		self::assertSame( 0, $lookup_calls );
	}

	/**
	 * Test same-site uploads URL resolves through callback and is cached.
	 *
	 * @return void
	 */
	public function test_same_site_uploads_url_resolves_through_callback_and_is_cached(): void {
		$lookup_calls = 0;
		$seen_url     = '';
		$resolver     = new LocalUploadAttachmentResolver(
			static function (): string {
				return 'https://example.test/wp-content/uploads';
			},
			static function ( string $url ) use ( &$lookup_calls, &$seen_url ): int {
				++$lookup_calls;
				$seen_url = $url;

				return 456;
			}
		);

		$html   = '<img src="/wp-content/uploads/2026/07/hero.png?cache=1" width="1200" alt="Hero">';
		$first  = $resolver->resolve( $html );
		$second = $resolver->resolve( $html );

		self::assertTrue( $first->is_resolved() );
		self::assertSame( 456, $first->attachment_id() );
		self::assertSame( LocalUploadAttachmentResolution::CODE_RESOLVED_UPLOAD_URL, $first->code() );
		self::assertSame( '2026/07/hero.png', $first->relative_path() );
		self::assertSame( 'https://example.test/wp-content/uploads/2026/07/hero.png', $seen_url );
		self::assertSame( 1, $lookup_calls );
		self::assertSame( $first->to_array(), $second->to_array() );
	}

	/**
	 * Test unsupported and unsafe URLs do not resolve.
	 *
	 * @param string $url URL.
	 * @param string $expected_code Expected code.
	 * @return void
	 *
	 * @dataProvider rejected_url_provider
	 */
	public function test_unsupported_and_unsafe_urls_do_not_resolve( string $url, string $expected_code ): void {
		$resolver = new LocalUploadAttachmentResolver(
			static function (): string {
				return 'https://example.test/wp-content/uploads';
			},
			static function (): int {
				TestCase::fail( 'Unsafe URLs must not be looked up.' );
			}
		);

		$result = $resolver->resolve( '<img src="' . $url . '" alt="Hero">' );

		self::assertFalse( $result->is_resolved() );
		self::assertSame( $expected_code, $result->code() );
	}

	/**
	 * Rejected URL provider.
	 *
	 * @return array<string,array{0:string,1:string}>
	 */
	public function rejected_url_provider(): array {
		return array(
			'external'      => array( 'https://cdn.example.test/wp-content/uploads/2026/07/hero.jpg', LocalUploadAttachmentResolution::CODE_NOT_LOCAL_UPLOAD ),
			'non-uploads'   => array( 'https://example.test/wp-content/themes/theme/hero.jpg', LocalUploadAttachmentResolution::CODE_NOT_LOCAL_UPLOAD ),
			'traversal'     => array( 'https://example.test/wp-content/uploads/2026/../hero.jpg', LocalUploadAttachmentResolution::CODE_UNRESOLVED ),
			'encoded-null'  => array( 'https://example.test/wp-content/uploads/2026/07/hero%00.jpg', LocalUploadAttachmentResolution::CODE_UNRESOLVED ),
			'unsupported'   => array( 'https://example.test/wp-content/uploads/2026/07/hero.svg', LocalUploadAttachmentResolution::CODE_UNRESOLVED ),
			'query-only'    => array( '?image=hero.jpg', LocalUploadAttachmentResolution::CODE_NOT_LOCAL_UPLOAD ),
			'protocol-like' => array( '//example.test/wp-content/uploads/2026/07/hero.jpg', LocalUploadAttachmentResolution::CODE_NOT_LOCAL_UPLOAD ),
		);
	}

	/**
	 * Test unresolved local uploads URLs carry safe path evidence.
	 *
	 * @return void
	 */
	public function test_unresolved_local_uploads_urls_carry_safe_path_evidence(): void {
		$resolver = new LocalUploadAttachmentResolver(
			static function (): string {
				return 'https://example.test/wp-content/uploads';
			},
			static function (): int {
				return 0;
			}
		);

		$result = $resolver->resolve( '<img src="https://example.test/wp-content/uploads/2026/07/unregistered.jpg" alt="Hero">' );

		self::assertFalse( $result->is_resolved() );
		self::assertSame( LocalUploadAttachmentResolution::CODE_UNRESOLVED, $result->code() );
		self::assertSame( '2026/07/unregistered.jpg', $result->relative_path() );
	}
}
