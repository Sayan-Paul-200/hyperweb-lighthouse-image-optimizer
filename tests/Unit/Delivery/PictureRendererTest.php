<?php
/**
 * Tests for picture rendering.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Delivery;

require_once __DIR__ . '/DeliveryTestWordPressShim.php';

use HyperWeb\LighthouseImageOptimizer\Delivery\FormatSourceSet;
use HyperWeb\LighthouseImageOptimizer\Delivery\PictureRenderRequest;
use HyperWeb\LighthouseImageOptimizer\Delivery\PictureRenderResult;
use HyperWeb\LighthouseImageOptimizer\Delivery\PictureRenderer;
use HyperWeb\LighthouseImageOptimizer\Delivery\SourceSetBuildResult;
use PHPUnit\Framework\TestCase;

/**
 * Verifies callable-only picture rendering behavior.
 */
final class PictureRendererTest extends TestCase {

	/**
	 * Clean up shim globals.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		unset( $GLOBALS['hwlio_test_filters'] );
	}

	/**
	 * Test standard image markup renders a modern picture.
	 *
	 * @return void
	 */
	public function test_standard_image_markup_renders_a_picture_with_preferred_sources(): void {
		$result = PictureRenderer::for_wordpress()->render(
			new PictureRenderRequest(
				123,
				'<img src="https://example.test/uploads/hero.jpg" srcset="https://example.test/uploads/hero-150x100.jpg 150w, https://example.test/uploads/hero.jpg 2400w" sizes="(max-width: 600px) 100vw, 600px" width="2400" height="1600" alt="Hero" loading="lazy" fetchpriority="high" decoding="async">',
				$this->source_sets(),
				array( 'avif', 'webp' )
			)
		);

		self::assertTrue( $result->is_rendered() );
		self::assertSame( array( 'avif', 'webp' ), $result->formats() );
		self::assertTrue( $result->has_code( PictureRenderResult::CODE_RENDERED ) );
		self::assertSame(
			'<picture class="hwlio-picture"><source type="image/avif" srcset="https://example.test/uploads/hero-150x100.jpg.hwlio.avif 150w, https://example.test/uploads/hero.jpg.hwlio.avif 2400w" sizes="(max-width: 600px) 100vw, 600px"><source type="image/webp" srcset="https://example.test/uploads/hero-150x100.jpg.hwlio.webp 150w, https://example.test/uploads/hero.jpg.hwlio.webp 2400w" sizes="(max-width: 600px) 100vw, 600px"><img src="https://example.test/uploads/hero.jpg" srcset="https://example.test/uploads/hero-150x100.jpg 150w, https://example.test/uploads/hero.jpg 2400w" sizes="(max-width: 600px) 100vw, 600px" width="2400" height="1600" alt="Hero" loading="lazy" fetchpriority="high" decoding="async"></picture>',
			$result->html()
		);
	}

	/**
	 * Test fallback image markup is preserved verbatim.
	 *
	 * @return void
	 */
	public function test_fallback_image_markup_is_preserved_verbatim(): void {
		$img    = '<img src="hero.jpg" class="hero hero--wide" data-track="1" aria-hidden="true" loading="lazy" fetchpriority="high" decoding="async">';
		$result = PictureRenderer::for_wordpress()->render(
			new PictureRenderRequest( 123, $img, $this->source_sets() )
		);

		self::assertTrue( $result->is_rendered() );
		self::assertStringContainsString( $img, $result->html() );
	}

	/**
	 * Test empty wrapper classes are omitted.
	 *
	 * @return void
	 */
	public function test_empty_wrapper_classes_are_omitted(): void {
		$result = PictureRenderer::for_wordpress()->render(
			new PictureRenderRequest( 123, '<img src="hero.jpg" alt="Hero">', $this->source_sets(), array( 'webp' ), '' )
		);

		self::assertSame(
			'<picture><source type="image/webp" srcset="https://example.test/uploads/hero-150x100.jpg.hwlio.webp 150w, https://example.test/uploads/hero.jpg.hwlio.webp 2400w"><img src="hero.jpg" alt="Hero"></picture>',
			$result->html()
		);
	}

	/**
	 * Test no valid sources returns the original markup unchanged.
	 *
	 * @return void
	 */
	public function test_no_valid_sources_returns_the_original_markup_unchanged(): void {
		$request = new PictureRenderRequest(
			123,
			'<img src="hero.jpg" alt="Hero">',
			new SourceSetBuildResult( 123, array() )
		);
		$result  = PictureRenderer::for_wordpress()->render( $request );

		self::assertFalse( $result->is_rendered() );
		self::assertSame( $request->img_html(), $result->html() );
		self::assertTrue( $result->has_code( PictureRenderResult::CODE_NO_SOURCES ) );
	}

	/**
	 * Test partial source availability still renders with surviving formats.
	 *
	 * @return void
	 */
	public function test_partial_source_availability_still_renders_with_surviving_formats(): void {
		$result = PictureRenderer::for_wordpress()->render(
			new PictureRenderRequest(
				123,
				'<img src="hero.jpg" alt="Hero">',
				new SourceSetBuildResult(
					123,
					array(
						'webp' => new FormatSourceSet(
							'webp',
							'image/webp',
							array(
								150 => array(
									'url'        => 'https://example.test/uploads/hero-150x100.jpg.hwlio.webp',
									'descriptor' => 'w',
									'value'      => 150,
								),
							)
						),
					),
					array( SourceSetBuildResult::CODE_PARTIAL_CANDIDATES_OMITTED )
				),
				array( 'avif', 'webp' )
			)
		);

		self::assertTrue( $result->is_rendered() );
		self::assertSame( array( 'webp' ), $result->formats() );
		self::assertTrue( $result->has_code( PictureRenderResult::CODE_PARTIAL_SOURCES_OMITTED ) );
	}

	/**
	 * Test already-picture and malformed fragments remain unchanged.
	 *
	 * @return void
	 */
	public function test_already_picture_and_malformed_fragments_remain_unchanged(): void {
		$renderer = PictureRenderer::for_wordpress();

		$picture = new PictureRenderRequest(
			123,
			'<picture><img src="hero.jpg" alt="Hero"></picture>',
			$this->source_sets()
		);
		$bad     = new PictureRenderRequest(
			123,
			'<span>Hero</span><img src="hero.jpg" alt="Hero">',
			$this->source_sets()
		);

		self::assertTrue( $renderer->render( $picture )->has_code( PictureRenderResult::CODE_ALREADY_PICTURE ) );
		self::assertTrue( $renderer->render( $bad )->has_code( PictureRenderResult::CODE_INVALID_MARKUP ) );
		self::assertSame( $picture->img_html(), $renderer->render( $picture )->html() );
		self::assertSame( $bad->img_html(), $renderer->render( $bad )->html() );
	}

	/**
	 * Test sizes are omitted on generated sources when the fallback image has none.
	 *
	 * @return void
	 */
	public function test_sizes_are_omitted_when_the_fallback_image_has_none(): void {
		$result = PictureRenderer::for_wordpress()->render(
			new PictureRenderRequest( 123, '<img src="hero.jpg" alt="Hero">', $this->source_sets(), array( 'webp' ) )
		);

		self::assertStringNotContainsString( ' sizes="', $result->html() );
	}

	/**
	 * Test filters can remove one format deterministically.
	 *
	 * @return void
	 */
	public function test_filters_can_remove_one_format_deterministically(): void {
		$GLOBALS['hwlio_test_filters'] = array(
			'hwlio_picture_sources' => static function ( array $payload, int $attachment_id, string $html, array $preference ): array {
				TestCase::assertSame( 123, $attachment_id );
				TestCase::assertSame( '<img src="hero.jpg" alt="Hero">', $html );
				TestCase::assertSame( array( 'avif', 'webp' ), $preference );

				unset( $payload['avif'] );

				return $payload;
			},
		);

		$result = PictureRenderer::for_wordpress()->render(
			new PictureRenderRequest( 123, '<img src="hero.jpg" alt="Hero">', $this->source_sets() )
		);

		self::assertTrue( $result->is_rendered() );
		self::assertSame( array( 'webp' ), $result->formats() );
		self::assertTrue( $result->has_code( PictureRenderResult::CODE_PARTIAL_SOURCES_OMITTED ) );
	}

	/**
	 * Test invalid filtered payloads are dropped conservatively.
	 *
	 * @return void
	 */
	public function test_invalid_filtered_payloads_are_dropped_conservatively(): void {
		$GLOBALS['hwlio_test_filters'] = array(
			'hwlio_picture_sources' => static function (): string {
				return 'not-an-array';
			},
		);

		$request = new PictureRenderRequest( 123, '<img src="hero.jpg" alt="Hero">', $this->source_sets() );
		$result  = PictureRenderer::for_wordpress()->render( $request );

		self::assertFalse( $result->is_rendered() );
		self::assertSame( $request->img_html(), $result->html() );
		self::assertTrue( $result->has_code( PictureRenderResult::CODE_NO_SOURCES ) );
	}

	/**
	 * Test serialized results remain path-safe.
	 *
	 * @return void
	 */
	public function test_serialized_results_remain_path_safe(): void {
		$payload = PictureRenderer::for_wordpress()->render(
			new PictureRenderRequest( 123, '<img src="hero.jpg" alt="Hero">', $this->source_sets() )
		)->to_array();

		self::assertStringNotContainsString( 'C:/', json_encode( $payload ) ?: '' );
		self::assertStringNotContainsString( '/var/www/', json_encode( $payload ) ?: '' );
	}

	/**
	 * Build source-set test data.
	 *
	 * @return SourceSetBuildResult
	 */
	private function source_sets(): SourceSetBuildResult {
		return new SourceSetBuildResult(
			123,
			array(
				'avif' => new FormatSourceSet(
					'avif',
					'image/avif',
					array(
						150  => array(
							'url'        => 'https://example.test/uploads/hero-150x100.jpg.hwlio.avif',
							'descriptor' => 'w',
							'value'      => 150,
						),
						2400 => array(
							'url'        => 'https://example.test/uploads/hero.jpg.hwlio.avif',
							'descriptor' => 'w',
							'value'      => 2400,
						),
					)
				),
				'webp' => new FormatSourceSet(
					'webp',
					'image/webp',
					array(
						150  => array(
							'url'        => 'https://example.test/uploads/hero-150x100.jpg.hwlio.webp',
							'descriptor' => 'w',
							'value'      => 150,
						),
						2400 => array(
							'url'        => 'https://example.test/uploads/hero.jpg.hwlio.webp',
							'descriptor' => 'w',
							'value'      => 2400,
						),
					)
				),
			)
		);
	}
}
