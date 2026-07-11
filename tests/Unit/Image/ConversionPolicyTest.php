<?php
/**
 * Tests for conversion policy.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image;

use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentFingerprint;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifest;
use HyperWeb\LighthouseImageOptimizer\Image\AnimationStatus;
use HyperWeb\LighthouseImageOptimizer\Image\ConversionPolicy;
use HyperWeb\LighthouseImageOptimizer\Image\ConversionPolicyContext;
use HyperWeb\LighthouseImageOptimizer\Image\ConversionPolicyResult;
use HyperWeb\LighthouseImageOptimizer\Image\ConversionResultCode;
use HyperWeb\LighthouseImageOptimizer\Image\ResourceGuard;
use HyperWeb\LighthouseImageOptimizer\Image\SourceImage;
use HyperWeb\LighthouseImageOptimizer\Image\SourceImageValidationResult;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\MemoryLimit;
use PHPUnit\Framework\TestCase;

/**
 * Tests ConversionPolicy gate evaluation.
 *
 * @covers \HyperWeb\LighthouseImageOptimizer\Image\ConversionPolicy
 * @covers \HyperWeb\LighthouseImageOptimizer\Image\ConversionPolicyContext
 * @covers \HyperWeb\LighthouseImageOptimizer\Image\ConversionPolicyResult
 */
final class ConversionPolicyTest extends TestCase {

	/**
	 * Build a standard JPEG source image.
	 *
	 * @param string $size_name Size name.
	 * @param int    $width Width.
	 * @param int    $height Height.
	 * @return SourceImage
	 */
	private function jpeg_source( string $size_name = 'full', int $width = 1200, int $height = 800 ): SourceImage {
		return new SourceImage(
			42,
			$size_name,
			'full' === $size_name ? SourceImage::ROLE_FULL : SourceImage::ROLE_SUBSIZE,
			'2026/07/hero.jpg',
			'/var/www/wp-content/uploads/2026/07/hero.jpg',
			'image/jpeg',
			$width,
			$height,
			500000,
			1783526400
		);
	}

	/**
	 * Build a WebP source image.
	 *
	 * @return SourceImage
	 */
	private function webp_source(): SourceImage {
		return new SourceImage(
			44,
			'full',
			SourceImage::ROLE_FULL,
			'2026/07/photo.webp',
			'/var/www/wp-content/uploads/2026/07/photo.webp',
			'image/webp',
			800,
			600,
			120000,
			1783526400
		);
	}

	/**
	 * Build a default policy with both formats enabled and supported.
	 *
	 * @param array<string,mixed> $overrides Settings overrides.
	 * @return ConversionPolicy
	 */
	private function make_policy( array $overrides = array() ): ConversionPolicy {
		$defaults = array( 'enabled_formats' => array( 'webp', 'avif' ) );

		return new ConversionPolicy(
			new FakeSettingsRepository( array_replace( $defaults, $overrides ) ),
			FakeFormatSupportProvider::all_supported(),
			new ResourceGuard( MemoryLimit::from_raw( '256M' ) )
		);
	}

	/**
	 * Build a matching fingerprint.
	 *
	 * @return AttachmentFingerprint
	 */
	private function make_fingerprint(): AttachmentFingerprint {
		return new AttachmentFingerprint(
			'2026/07/hero.jpg',
			500000,
			1783526400,
			hash( 'sha256', 'test-metadata' )
		);
	}

	/**
	 * Build a manifest with a ready derivative.
	 *
	 * @param AttachmentFingerprint $fp Fingerprint.
	 * @param string                $size Size name.
	 * @param string                $fmt Format.
	 * @return DerivativeManifest
	 */
	private function ready_manifest( AttachmentFingerprint $fp, string $size = 'full', string $fmt = 'webp' ): DerivativeManifest {
		return new DerivativeManifest(
			$fp,
			1783526500,
			array(
				$size => array(
					'source'  => array(
						'file'   => '2026/07/hero.jpg',
						'width'  => 1200,
						'height' => 800,
						'mime'   => 'image/jpeg',
						'bytes'  => 500000,
					),
					'formats' => array(
						$fmt => array(
							'file'         => '2026/07/hero.jpg.hwlio.' . $fmt,
							'mime'         => 'image/' . $fmt,
							'bytes'        => 150000,
							'quality'      => 82,
							'status'       => 'ready',
							'generated_at' => 1783526500,
						),
					),
				),
			)
		);
	}

	/**
	 * Test rejects unknown target format.
	 *
	 * @return void
	 */
	public function test_rejects_unknown_target_format(): void {
		$result = $this->make_policy()->should_convert(
			$this->jpeg_source(),
			'bmp',
			ConversionPolicyContext::for_new_optimization()
		);

		self::assertFalse( $result->should_convert() );
		self::assertTrue( $result->is_skipped() );
		self::assertSame( ConversionResultCode::INVALID_TARGET_FORMAT, $result->code() );
	}

	/**
	 * Test rejects empty target format.
	 *
	 * @return void
	 */
	public function test_rejects_empty_target_format(): void {
		$result = $this->make_policy()->should_convert(
			$this->jpeg_source(),
			'',
			ConversionPolicyContext::for_new_optimization()
		);

		self::assertSame( ConversionResultCode::INVALID_TARGET_FORMAT, $result->code() );
	}

	/**
	 * Test skips excluded attachment.
	 *
	 * @return void
	 */
	public function test_skips_excluded_attachment(): void {
		$context = new ConversionPolicyContext( false, true );
		$result  = $this->make_policy()->should_convert( $this->jpeg_source(), 'webp', $context );

		self::assertFalse( $result->should_convert() );
		self::assertSame( ConversionResultCode::SKIPPED_EXCLUDED, $result->code() );
	}

	/**
	 * Test exclusion checked before format enablement.
	 *
	 * @return void
	 */
	public function test_exclusion_checked_before_format_enablement(): void {
		$policy  = $this->make_policy( array( 'enabled_formats' => array() ) );
		$context = new ConversionPolicyContext( false, true );
		$result  = $policy->should_convert( $this->jpeg_source(), 'webp', $context );

		self::assertSame( ConversionResultCode::SKIPPED_EXCLUDED, $result->code() );
	}

	/**
	 * Test skips format not enabled.
	 *
	 * @return void
	 */
	public function test_skips_format_not_enabled(): void {
		$policy = $this->make_policy( array( 'enabled_formats' => array( 'webp' ) ) );
		$result = $policy->should_convert(
			$this->jpeg_source(),
			'avif',
			ConversionPolicyContext::for_new_optimization()
		);

		self::assertFalse( $result->should_convert() );
		self::assertSame( ConversionResultCode::SKIPPED_TARGET_NOT_ENABLED, $result->code() );
	}

	/**
	 * Test skips when no formats enabled.
	 *
	 * @return void
	 */
	public function test_skips_when_no_formats_enabled(): void {
		$policy = $this->make_policy( array( 'enabled_formats' => array() ) );
		$result = $policy->should_convert(
			$this->jpeg_source(),
			'webp',
			ConversionPolicyContext::for_new_optimization()
		);

		self::assertSame( ConversionResultCode::SKIPPED_TARGET_NOT_ENABLED, $result->code() );
	}

	/**
	 * Test skips unsupported server format.
	 *
	 * @return void
	 */
	public function test_skips_unsupported_server_format(): void {
		$policy = new ConversionPolicy(
			new FakeSettingsRepository( array( 'enabled_formats' => array( 'webp', 'avif' ) ) ),
			FakeFormatSupportProvider::webp_only(),
			new ResourceGuard( MemoryLimit::from_raw( '256M' ) )
		);

		$result = $policy->should_convert(
			$this->jpeg_source(),
			'avif',
			ConversionPolicyContext::for_new_optimization()
		);

		self::assertFalse( $result->should_convert() );
		self::assertSame( ConversionResultCode::SKIPPED_TARGET_NOT_SUPPORTED, $result->code() );
	}

	/**
	 * Test skips unsupported source MIME.
	 *
	 * @return void
	 */
	public function test_skips_unsupported_source_mime(): void {
		$source = new SourceImage(
			45,
			'full',
			SourceImage::ROLE_FULL,
			'2026/07/icon.svg',
			'/var/www/wp-content/uploads/2026/07/icon.svg',
			'image/svg+xml',
			100,
			100,
			5000,
			1783526400
		);

		$result = $this->make_policy()->should_convert(
			$source,
			'webp',
			ConversionPolicyContext::for_new_optimization()
		);

		self::assertFalse( $result->should_convert() );
		self::assertSame( ConversionResultCode::SKIPPED_UNSUPPORTED_SOURCE_MIME, $result->code() );
	}

	/**
	 * Test skips null source MIME.
	 *
	 * @return void
	 */
	public function test_skips_null_source_mime(): void {
		$source = new SourceImage(
			46,
			'full',
			SourceImage::ROLE_FULL,
			'2026/07/unknown.dat',
			'/var/www/wp-content/uploads/2026/07/unknown.dat',
			null,
			100,
			100,
			5000,
			1783526400
		);

		$result = $this->make_policy()->should_convert(
			$source,
			'webp',
			ConversionPolicyContext::for_new_optimization()
		);

		self::assertSame( ConversionResultCode::SKIPPED_UNSUPPORTED_SOURCE_MIME, $result->code() );
	}

	/**
	 * Test skips WebP to WebP conversion.
	 *
	 * @return void
	 */
	public function test_skips_webp_to_webp(): void {
		$result = $this->make_policy()->should_convert(
			$this->webp_source(),
			'webp',
			ConversionPolicyContext::for_new_optimization()
		);

		self::assertFalse( $result->should_convert() );
		self::assertSame( ConversionResultCode::SKIPPED_UNSUPPORTED_SOURCE_MIME, $result->code() );
	}

	/**
	 * Test allows WebP to AVIF conversion.
	 *
	 * @return void
	 */
	public function test_allows_webp_to_avif(): void {
		$result = $this->make_policy()->should_convert(
			$this->webp_source(),
			'avif',
			ConversionPolicyContext::for_new_optimization()
		);

		self::assertTrue( $result->should_convert() );
		self::assertSame( 'eligible', $result->code() );
	}

	/**
	 * Test skips when validation shows animated.
	 *
	 * @return void
	 */
	public function test_skips_when_validation_shows_animated(): void {
		$source    = $this->jpeg_source();
		$animation = AnimationStatus::animated( 'image/gif', 'animated_image' );

		$validation = SourceImageValidationResult::skipped(
			$source,
			SourceImageValidationResult::CODE_SKIPPED_ANIMATED_IMAGE,
			'Animated source images are skipped.',
			'image/gif',
			'image/gif',
			$animation
		);

		$context = ConversionPolicyContext::for_new_optimization()->with_validation( $validation );
		$result  = $this->make_policy()->should_convert( $source, 'webp', $context );

		self::assertFalse( $result->should_convert() );
		self::assertSame( ConversionResultCode::SKIPPED_ANIMATED_IMAGE, $result->code() );
	}

	/**
	 * Test skips when validation shows missing.
	 *
	 * @return void
	 */
	public function test_skips_when_validation_shows_missing(): void {
		$source    = $this->jpeg_source();
		$animation = AnimationStatus::not_applicable( '' );

		$validation = SourceImageValidationResult::invalid(
			$source,
			SourceImageValidationResult::CODE_SOURCE_MISSING,
			'The source file no longer exists.',
			null,
			'image/jpeg',
			$animation
		);

		$context = ConversionPolicyContext::for_new_optimization()->with_validation( $validation );
		$result  = $this->make_policy()->should_convert( $source, 'webp', $context );

		self::assertFalse( $result->should_convert() );
		self::assertSame( ConversionResultCode::SOURCE_MISSING, $result->code() );
	}

	/**
	 * Test passes when validation is eligible.
	 *
	 * @return void
	 */
	public function test_passes_when_validation_is_eligible(): void {
		$source    = $this->jpeg_source();
		$animation = AnimationStatus::not_animated( 'image/jpeg' );

		$validation = SourceImageValidationResult::eligible(
			$source,
			'image/jpeg',
			'image/jpeg',
			$animation,
			array( 'webp', 'avif' )
		);

		$context = ConversionPolicyContext::for_new_optimization()->with_validation( $validation );
		$result  = $this->make_policy()->should_convert( $source, 'webp', $context );

		self::assertTrue( $result->should_convert() );
	}

	/**
	 * Test passes when no validation provided.
	 *
	 * @return void
	 */
	public function test_passes_when_no_validation_provided(): void {
		$result = $this->make_policy()->should_convert(
			$this->jpeg_source(),
			'webp',
			ConversionPolicyContext::for_new_optimization()
		);

		self::assertTrue( $result->should_convert() );
	}

	/**
	 * Test skips oversized source via resource guard.
	 *
	 * @return void
	 */
	public function test_skips_oversized_source(): void {
		$source = new SourceImage(
			47,
			'full',
			SourceImage::ROLE_FULL,
			'2026/07/huge.jpg',
			'/var/www/wp-content/uploads/2026/07/huge.jpg',
			'image/jpeg',
			10000,
			10000,
			50000000,
			1783526400
		);

		$policy = new ConversionPolicy(
			new FakeSettingsRepository( array( 'enabled_formats' => array( 'webp' ) ) ),
			FakeFormatSupportProvider::all_supported(),
			new ResourceGuard( MemoryLimit::from_raw( '128M' ), 50000000 )
		);

		$result = $policy->should_convert( $source, 'webp', ConversionPolicyContext::for_new_optimization() );

		self::assertFalse( $result->should_convert() );
		self::assertSame( ConversionResultCode::SKIPPED_RESOURCE_LIMIT, $result->code() );
	}

	/**
	 * Test skips when valid derivative exists with matching fingerprint.
	 *
	 * @return void
	 */
	public function test_skips_when_valid_derivative_exists(): void {
		$fp       = $this->make_fingerprint();
		$manifest = $this->ready_manifest( $fp, 'full', 'webp' );
		$context  = new ConversionPolicyContext( false, false, $manifest, $fp );

		$result = $this->make_policy()->should_convert( $this->jpeg_source(), 'webp', $context );

		self::assertFalse( $result->should_convert() );
		self::assertSame( ConversionResultCode::ALREADY_CURRENT, $result->code() );
	}

	/**
	 * Test converts when derivative is different format.
	 *
	 * @return void
	 */
	public function test_converts_when_derivative_is_different_format(): void {
		$fp      = $this->make_fingerprint();
		$context = new ConversionPolicyContext( false, false, $this->ready_manifest( $fp, 'full', 'webp' ), $fp );

		self::assertTrue( $this->make_policy()->should_convert( $this->jpeg_source(), 'avif', $context )->should_convert() );
	}

	/**
	 * Test converts when derivative is different size.
	 *
	 * @return void
	 */
	public function test_converts_when_derivative_is_different_size(): void {
		$fp      = $this->make_fingerprint();
		$context = new ConversionPolicyContext( false, false, $this->ready_manifest( $fp, 'thumbnail', 'webp' ), $fp );

		self::assertTrue( $this->make_policy()->should_convert( $this->jpeg_source(), 'webp', $context )->should_convert() );
	}

	/**
	 * Test converts when fingerprint mismatch.
	 *
	 * @return void
	 */
	public function test_converts_when_fingerprint_mismatch(): void {
		$stored_fp  = $this->make_fingerprint();
		$current_fp = new AttachmentFingerprint(
			'2026/07/hero.jpg',
			600000,
			1783526400,
			hash( 'sha256', 'test-metadata' )
		);

		$manifest = $this->ready_manifest( $stored_fp, 'full', 'webp' );
		$context  = new ConversionPolicyContext( false, false, $manifest, $current_fp );

		self::assertTrue( $this->make_policy()->should_convert( $this->jpeg_source(), 'webp', $context )->should_convert() );
	}

	/**
	 * Test converts when no manifest.
	 *
	 * @return void
	 */
	public function test_converts_when_no_manifest(): void {
		$result = $this->make_policy()->should_convert(
			$this->jpeg_source(),
			'webp',
			ConversionPolicyContext::for_new_optimization()
		);

		self::assertTrue( $result->should_convert() );
	}

	/**
	 * Test converts when manifest empty.
	 *
	 * @return void
	 */
	public function test_converts_when_manifest_empty(): void {
		$context = new ConversionPolicyContext( false, false, DerivativeManifest::empty(), $this->make_fingerprint() );

		self::assertTrue( $this->make_policy()->should_convert( $this->jpeg_source(), 'webp', $context )->should_convert() );
	}

	/**
	 * Test force bypasses existing derivative.
	 *
	 * @return void
	 */
	public function test_force_bypasses_existing_derivative(): void {
		$fp      = $this->make_fingerprint();
		$context = new ConversionPolicyContext( true, false, $this->ready_manifest( $fp ), $fp );

		$result = $this->make_policy()->should_convert( $this->jpeg_source(), 'webp', $context );

		self::assertTrue( $result->should_convert() );
		self::assertSame( 'eligible', $result->code() );
	}

	/**
	 * Test force does not bypass exclusion.
	 *
	 * @return void
	 */
	public function test_force_does_not_bypass_exclusion(): void {
		$context = new ConversionPolicyContext( true, true );
		$result  = $this->make_policy()->should_convert( $this->jpeg_source(), 'webp', $context );

		self::assertFalse( $result->should_convert() );
		self::assertSame( ConversionResultCode::SKIPPED_EXCLUDED, $result->code() );
	}

	/**
	 * Test JPEG to WebP eligible.
	 *
	 * @return void
	 */
	public function test_jpeg_to_webp_eligible(): void {
		$result = $this->make_policy()->should_convert(
			$this->jpeg_source(),
			'webp',
			ConversionPolicyContext::for_new_optimization()
		);

		self::assertTrue( $result->should_convert() );
		self::assertSame( 'eligible', $result->code() );
		self::assertStringContainsString( 'WEBP', $result->reason() );
	}

	/**
	 * Test PNG to WebP eligible.
	 *
	 * @return void
	 */
	public function test_png_to_webp_eligible(): void {
		$source = new SourceImage(
			43,
			'full',
			SourceImage::ROLE_FULL,
			'2026/07/logo.png',
			'/var/www/wp-content/uploads/2026/07/logo.png',
			'image/png',
			400,
			200,
			80000,
			1783526400
		);

		self::assertTrue( $this->make_policy()->should_convert( $source, 'webp', ConversionPolicyContext::for_new_optimization() )->should_convert() );
	}

	/**
	 * Test target format is case insensitive.
	 *
	 * @return void
	 */
	public function test_target_format_is_case_insensitive(): void {
		$result = $this->make_policy()->should_convert(
			$this->jpeg_source(),
			'WebP',
			ConversionPolicyContext::for_new_optimization()
		);

		self::assertTrue( $result->should_convert() );
	}

	/**
	 * Test context for new optimization defaults.
	 *
	 * @return void
	 */
	public function test_context_for_new_optimization(): void {
		$context = ConversionPolicyContext::for_new_optimization();

		self::assertFalse( $context->is_forced() );
		self::assertFalse( $context->is_excluded() );
		self::assertNull( $context->manifest() );
		self::assertNull( $context->fingerprint() );
		self::assertNull( $context->validation() );
	}

	/**
	 * Test context for reoptimization.
	 *
	 * @return void
	 */
	public function test_context_for_reoptimization(): void {
		$fp      = $this->make_fingerprint();
		$context = ConversionPolicyContext::for_reoptimization( null, $fp );

		self::assertTrue( $context->is_forced() );
		self::assertFalse( $context->is_excluded() );
		self::assertNull( $context->manifest() );
		self::assertSame( $fp, $context->fingerprint() );
	}

	/**
	 * Test context with validation.
	 *
	 * @return void
	 */
	public function test_context_with_validation(): void {
		$source    = $this->jpeg_source();
		$animation = AnimationStatus::not_animated( 'image/jpeg' );

		$validation = SourceImageValidationResult::eligible(
			$source,
			'image/jpeg',
			'image/jpeg',
			$animation,
			array( 'webp' )
		);

		$context = ConversionPolicyContext::for_new_optimization()->with_validation( $validation );

		self::assertSame( $validation, $context->validation() );
		self::assertFalse( $context->is_forced() );
	}

	/**
	 * Test context serialization.
	 *
	 * @return void
	 */
	public function test_context_to_array(): void {
		$array = ConversionPolicyContext::for_new_optimization()->to_array();

		self::assertFalse( $array['force'] );
		self::assertFalse( $array['excluded'] );
		self::assertFalse( $array['has_manifest'] );
		self::assertFalse( $array['has_fingerprint'] );
		self::assertFalse( $array['has_validation'] );
	}

	/**
	 * Test eligible result factory.
	 *
	 * @return void
	 */
	public function test_result_eligible(): void {
		$result = ConversionPolicyResult::eligible();

		self::assertTrue( $result->should_convert() );
		self::assertFalse( $result->is_skipped() );
		self::assertSame( 'eligible', $result->code() );
	}

	/**
	 * Test skip result factory.
	 *
	 * @return void
	 */
	public function test_result_skip(): void {
		$result = ConversionPolicyResult::skip( 'skipped_excluded', 'Excluded.' );

		self::assertFalse( $result->should_convert() );
		self::assertTrue( $result->is_skipped() );
		self::assertSame( 'skipped_excluded', $result->code() );
		self::assertSame( 'Excluded.', $result->reason() );
	}

	/**
	 * Test result serialization.
	 *
	 * @return void
	 */
	public function test_result_to_array(): void {
		$array = ConversionPolicyResult::eligible( 'Test reason.' )->to_array();

		self::assertTrue( $array['should_convert'] );
		self::assertSame( 'eligible', $array['code'] );
		self::assertSame( 'Test reason.', $array['reason'] );
	}

	/**
	 * Test result normalizes code.
	 *
	 * @return void
	 */
	public function test_result_normalizes_code(): void {
		$result = ConversionPolicyResult::skip( 'INVALID CODE!', 'reason' );

		self::assertSame( 'invalid_code', $result->code() );
	}

	/**
	 * Test converts when manifest has no fingerprint.
	 *
	 * @return void
	 */
	public function test_converts_when_manifest_has_no_fingerprint(): void {
		$manifest = new DerivativeManifest(
			null,
			1783526500,
			array(
				'full' => array(
					'formats' => array(
						'webp' => array(
							'status' => 'ready',
							'file'   => 'x.webp',
						),
					),
				),
			)
		);

		$context = new ConversionPolicyContext( false, false, $manifest, $this->make_fingerprint() );

		self::assertTrue( $this->make_policy()->should_convert( $this->jpeg_source(), 'webp', $context )->should_convert() );
	}

	/**
	 * Test converts when context has no fingerprint.
	 *
	 * @return void
	 */
	public function test_converts_when_context_has_no_fingerprint(): void {
		$fp       = $this->make_fingerprint();
		$manifest = $this->ready_manifest( $fp, 'full', 'webp' );
		$context  = new ConversionPolicyContext( false, false, $manifest, null );

		self::assertTrue( $this->make_policy()->should_convert( $this->jpeg_source(), 'webp', $context )->should_convert() );
	}

	/**
	 * Test converts when derivative status is not ready.
	 *
	 * @return void
	 */
	public function test_converts_when_derivative_status_is_not_ready(): void {
		$fp       = $this->make_fingerprint();
		$manifest = new DerivativeManifest(
			$fp,
			1783526500,
			array(
				'full' => array(
					'formats' => array(
						'webp' => array(
							'status' => 'failed',
							'file'   => 'x.webp',
						),
					),
				),
			)
		);

		$context = new ConversionPolicyContext( false, false, $manifest, $fp );

		self::assertTrue( $this->make_policy()->should_convert( $this->jpeg_source(), 'webp', $context )->should_convert() );
	}
}
