<?php
/**
 * Attachment processor tests.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment;

use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentFingerprintBuilder;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentProcessRequest;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentProcessResult;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentProcessor;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifestSanitizer;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeRepository;
use HyperWeb\LighthouseImageOptimizer\Image\AttachmentSourceProviderInterface;
use HyperWeb\LighthouseImageOptimizer\Image\ConversionPolicy;
use HyperWeb\LighthouseImageOptimizer\Image\DestinationResolver;
use HyperWeb\LighthouseImageOptimizer\Image\ImageConverter;
use HyperWeb\LighthouseImageOptimizer\Image\ImageFileProbeInterface;
use HyperWeb\LighthouseImageOptimizer\Image\ResourceGuard;
use HyperWeb\LighthouseImageOptimizer\Image\SourceCollector;
use HyperWeb\LighthouseImageOptimizer\Image\SourceImage;
use HyperWeb\LighthouseImageOptimizer\Image\SourceImageCollection;
use HyperWeb\LighthouseImageOptimizer\Image\SourceImageValidator;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\FormatSupportProviderInterface;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\MemoryLimit;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeAnimationDetector;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeAttachmentSourceProvider;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeConversionEditor;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeConversionFilesystem;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeFormatSupportProvider;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeImageFileProbe;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeSettingsRepository;
use PHPUnit\Framework\TestCase;

/**
 * Tests for AttachmentProcessor.
 *
 * @covers \HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentProcessor
 */
final class AttachmentProcessorTest extends TestCase {

	/**
	 * Test process_format bails when no sources and does not acquire locks.
	 *
	 * @return void
	 */
	public function test_process_format_bails_when_no_sources_without_locking(): void {
		$store = new FakeAttachmentMetaStore();
		$clock = new FixedAttachmentClock( 1783526500 );

		$processor = new AttachmentProcessor(
			new SourceCollector(
				new FakeAttachmentSourceProvider( null, null, '/uploads' ),
				new FakeImageFileProbe( array( '/uploads' ) )
			),
			new AttachmentFingerprintBuilder(),
			new DerivativeRepository( $store, new DerivativeManifestSanitizer(), $clock ),
			new SourceImageValidator( new FakeImageFileProbe( array( '/uploads' ) ), new FakeAnimationDetector() ),
			new FakeSettingsRepository( array( 'enabled_formats' => array( 'webp' ) ) ),
			new ConversionPolicy(
				new FakeSettingsRepository( array( 'enabled_formats' => array( 'webp' ) ) ),
				FakeFormatSupportProvider::all_supported(),
				new ResourceGuard( MemoryLimit::from_raw( '256M' ) )
			),
			new ImageConverter( new FakeConversionEditor( new FakeConversionFilesystem() ), new FakeConversionFilesystem() ),
			new DestinationResolver( '/uploads', new FakeImageFileProbe( array( '/uploads' ) ) )
		);

		$result = $processor->process_format( 123, 'webp' );

		self::assertTrue( $result->is_successful() );
		self::assertSame( array( AttachmentProcessResult::CODE_SKIPPED_NO_SOURCES ), $result->codes() );
		self::assertSame( array(), $store->adds );
	}

	/**
	 * Test process_request fails when fingerprint is missing.
	 *
	 * @return void
	 */
	public function test_process_request_fails_when_fingerprint_is_missing(): void {
		$store   = new FakeAttachmentMetaStore();
		$clock   = new FixedAttachmentClock( 1783526500 );
		$source  = $this->source_image();
		$request = new AttachmentProcessRequest(
			123,
			'webp',
			0,
			20,
			false,
			new SourceImageCollection( array( $source ) ),
			null
		);

		$processor = $this->build_processor(
			new SourceCollector(
				new FakeAttachmentSourceProvider( null, null, '/uploads' ),
				new FakeImageFileProbe( array( '/uploads' ) )
			),
			new DerivativeRepository( $store, new DerivativeManifestSanitizer(), $clock )
		);

		$result = $processor->process_request( $request );

		self::assertFalse( $result->is_successful() );
		self::assertSame( array( AttachmentProcessResult::CODE_FINGERPRINT_FAILED ), $result->codes() );
		self::assertSame( AttachmentProcessResult::CODE_FINGERPRINT_FAILED, $store->meta[123][ LifecyclePolicy::META_STATUS ]['error_code'] );
	}

	/**
	 * Test process_request uses supplied collection and fingerprint.
	 *
	 * @return void
	 */
	public function test_process_request_uses_supplied_collection_and_fingerprint_without_collecting_again(): void {
		$store  = new FakeAttachmentMetaStore();
		$clock  = new FixedAttachmentClock( 1783526500 );
		$probe  = new FakeImageFileProbe( array( '/uploads' ) );
		$fs     = new FakeConversionFilesystem();
		$editor = new FakeConversionEditor( $fs );

		$probe->add_file( '/uploads/2026/07/hero.jpg', 1000, 1783526400, 'image/jpeg', 2400, 1600 );
		$fs->add_file( '/uploads/2026/07/hero.jpg', 1000, 'image/jpeg', 2400, 1600 );
		$editor->output( 300, 'image/webp', 2400, 1600 );

		$throwing_provider = $this->createMock( AttachmentSourceProviderInterface::class );
		$throwing_provider->method( 'uploads_base_dir' )->willThrowException( new \RuntimeException( 'Collector should not be used.' ) );
		$throwing_probe = $this->createMock( ImageFileProbeInterface::class );

		$collector   = new SourceCollector( $throwing_provider, $throwing_probe );
		$repository  = new DerivativeRepository( $store, new DerivativeManifestSanitizer(), $clock );
		$settings    = new FakeSettingsRepository( array( 'enabled_formats' => array( 'webp', 'avif' ) ) );
		$fingerprint = new AttachmentFingerprintBuilder();
		$collection  = new SourceImageCollection( array( $this->source_image() ) );
		$request     = new AttachmentProcessRequest(
			123,
			'webp',
			0,
			20,
			false,
			$collection,
			$fingerprint->build( $collection )
		);
		$processor   = new AttachmentProcessor(
			$collector,
			$fingerprint,
			$repository,
			new SourceImageValidator( $probe, new FakeAnimationDetector() ),
			$settings,
			new ConversionPolicy( $settings, FakeFormatSupportProvider::all_supported(), new ResourceGuard( MemoryLimit::from_raw( '256M' ) ) ),
			new ImageConverter( $editor, $fs, null, null ),
			new DestinationResolver( '/uploads', $probe )
		);

		$result = $processor->process_request( $request );

		self::assertTrue( $result->is_successful() );
		self::assertTrue( $result->is_complete() );
		self::assertArrayHasKey( LifecyclePolicy::META_DERIVATIVES, $store->meta[123] );
		self::assertArrayHasKey( 'webp', $store->meta[123][ LifecyclePolicy::META_DERIVATIVES ]['sizes']['full']['formats'] );
		self::assertSame( array(), $store->adds );
	}

	/**
	 * Build a processor with deterministic dependencies.
	 *
	 * @param SourceCollector      $collector Source collector.
	 * @param DerivativeRepository $repository Derivative repository.
	 * @return AttachmentProcessor
	 */
	private function build_processor( SourceCollector $collector, DerivativeRepository $repository ): AttachmentProcessor {
		$settings = new FakeSettingsRepository( array( 'enabled_formats' => array( 'webp' ) ) );

		return new AttachmentProcessor(
			$collector,
			new AttachmentFingerprintBuilder(),
			$repository,
			new SourceImageValidator( new FakeImageFileProbe( array( '/uploads' ) ), new FakeAnimationDetector() ),
			$settings,
			new ConversionPolicy( $settings, FakeFormatSupportProvider::all_supported(), new ResourceGuard( MemoryLimit::from_raw( '256M' ) ) ),
			new ImageConverter( new FakeConversionEditor( new FakeConversionFilesystem() ), new FakeConversionFilesystem() ),
			new DestinationResolver( '/uploads', new FakeImageFileProbe( array( '/uploads' ) ) )
		);
	}

	/**
	 * Build a canonical source image.
	 *
	 * @return SourceImage
	 */
	private function source_image(): SourceImage {
		return new SourceImage(
			123,
			'full',
			SourceImage::ROLE_FULL,
			'2026/07/hero.jpg',
			'/uploads/2026/07/hero.jpg',
			'image/jpeg',
			2400,
			1600,
			1000,
			1783526400
		);
	}
}
