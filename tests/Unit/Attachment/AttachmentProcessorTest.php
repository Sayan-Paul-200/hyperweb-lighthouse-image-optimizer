<?php
/**
 * Attachment processor tests.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment;

use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentClockInterface;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentFingerprintBuilder;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentLockManager;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentLockTokenGeneratorInterface;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentMetaStoreInterface;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentProcessResult;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentProcessor;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifestSanitizer;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeRepository;
use HyperWeb\LighthouseImageOptimizer\Image\AnimationDetectorInterface;
use HyperWeb\LighthouseImageOptimizer\Image\AttachmentSourceProviderInterface;
use HyperWeb\LighthouseImageOptimizer\Image\ConversionEditorInterface;
use HyperWeb\LighthouseImageOptimizer\Image\ConversionFilesystemInterface;
use HyperWeb\LighthouseImageOptimizer\Image\ConversionPolicy;
use HyperWeb\LighthouseImageOptimizer\Image\DestinationResolver;
use HyperWeb\LighthouseImageOptimizer\Image\ImageConverter;
use HyperWeb\LighthouseImageOptimizer\Image\ImageFileProbeInterface;
use HyperWeb\LighthouseImageOptimizer\Image\ResourceGuard;
use HyperWeb\LighthouseImageOptimizer\Image\SourceCollector;
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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Tests for AttachmentProcessor.
 *
 * @covers \HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentProcessor
 */
final class AttachmentProcessorTest extends TestCase {

	/**
	 * Meta store.
	 *
	 * @var AttachmentMetaStoreInterface|MockObject
	 */
	private $meta;

	/**
	 * Source provider.
	 *
	 * @var AttachmentSourceProviderInterface|MockObject
	 */
	private $source_provider;

	/**
	 * File probe.
	 *
	 * @var ImageFileProbeInterface|MockObject
	 */
	private $probe;

	/**
	 * Clock.
	 *
	 * @var AttachmentClockInterface|MockObject
	 */
	private $clock;

	/**
	 * Token generator.
	 *
	 * @var AttachmentLockTokenGeneratorInterface|MockObject
	 */
	private $token_gen;

	/**
	 * Filesystem.
	 *
	 * @var ConversionFilesystemInterface|MockObject
	 */
	private $fs;

	/**
	 * Editor.
	 *
	 * @var ConversionEditorInterface|MockObject
	 */
	private $editor;

	/**
	 * Format support.
	 *
	 * @var FormatSupportProviderInterface|MockObject
	 */
	private $format_support;

	/**
	 * Animation detector.
	 *
	 * @var AnimationDetectorInterface|MockObject
	 */
	private $animation;

	/**
	 * Settings.
	 *
	 * @var FakeSettingsRepository
	 */
	private $settings;

	/**
	 * Processor.
	 *
	 * @var AttachmentProcessor
	 */
	private $processor;

	/**
	 * Set up.
	 */
	protected function setUp(): void {
		$this->meta            = $this->createMock( AttachmentMetaStoreInterface::class );
		$this->source_provider = $this->createMock( AttachmentSourceProviderInterface::class );
		$this->probe           = $this->createMock( ImageFileProbeInterface::class );
		$this->clock           = $this->createMock( AttachmentClockInterface::class );
		$this->token_gen       = $this->createMock( AttachmentLockTokenGeneratorInterface::class );
		$this->fs              = $this->createMock( ConversionFilesystemInterface::class );
		$this->editor          = $this->createMock( ConversionEditorInterface::class );
		$this->format_support  = $this->createMock( FormatSupportProviderInterface::class );
		$this->animation       = $this->createMock( AnimationDetectorInterface::class );

		$this->settings = new FakeSettingsRepository(
			array(
				'enabled_formats' => array( 'webp' ),
			)
		);

		$locks         = new AttachmentLockManager( $this->meta, $this->token_gen, $this->clock );
		$collector     = new SourceCollector( $this->source_provider, $this->probe );
		$fingerprinter = new AttachmentFingerprintBuilder();
		$repository    = new DerivativeRepository( $this->meta, new DerivativeManifestSanitizer(), $this->clock );
		$validator     = new SourceImageValidator( $this->probe, $this->animation );
		$policy        = new ConversionPolicy( $this->settings, $this->format_support, new ResourceGuard( MemoryLimit::from_raw( '256M' ) ) );
		$converter     = new ImageConverter( $this->editor, $this->fs );
		$resolver      = new DestinationResolver( '/uploads', $this->probe );

		$this->processor = new AttachmentProcessor(
			$locks,
			$collector,
			$fingerprinter,
			$repository,
			$validator,
			$this->settings,
			$policy,
			$converter,
			$resolver
		);
	}

	/**
	 * Test bails when locked.
	 */
	public function test_bails_when_locked(): void {
		$this->clock->method( 'now' )->willReturn( 1000 );
		$this->token_gen->method( 'generate' )->willReturn( 'my_token' );

		$this->meta->method( 'add_unique' )->willReturn( false );
		$this->meta->method( 'get' )->willReturn(
			array(
				'token'      => 'other_token',
				'created_at' => 900,
				'expires_at' => 1500,
			)
		);

		$result = $this->processor->process( 123 );

		self::assertTrue( $result->is_locked() );
		self::assertFalse( $result->is_successful() );
	}

	/**
	 * Test bails when no sources.
	 */
	public function test_bails_when_no_sources(): void {
		$this->expect_lock_acquired();
		$this->source_provider->method( 'uploads_base_dir' )->willReturn( '/uploads' );
		$this->source_provider->method( 'attached_file' )->willReturn( null );
		$this->source_provider->method( 'metadata' )->willReturn( null );
		$this->probe->method( 'realpath' )->with( '/uploads' )->willReturn( '/uploads' );

		$this->meta->expects( self::once() )
			->method( 'update' )
			->with(
				123,
				LifecyclePolicy::META_STATUS,
				self::callback(
					function ( $status ) {
						return 'skipped' === $status['state'] && 'skipped_no_sources' === $status['error_code'];
					}
				)
			)
			->willReturn( true );

		$result = $this->processor->process( 123 );

		self::assertTrue( $result->is_successful() );
		self::assertSame( array( AttachmentProcessResult::CODE_SKIPPED_NO_SOURCES ), $result->codes() );
	}

	/**
	 * Test bails when fingerprint fails.
	 */
	public function test_bails_when_fingerprint_fails(): void {
		$this->expect_lock_acquired();
		$this->source_provider->method( 'uploads_base_dir' )->willReturn( '/uploads' );
		$this->source_provider->method( 'attached_file' )->willReturn( null );
		$this->source_provider->method( 'metadata' )->willReturn(
			array(
				'file'  => '2026/07/hero.jpg',
				'sizes' => array(
					'thumbnail' => array(
						'file'   => 'hero-150x150.jpg',
						'width'  => 150,
						'height' => 150,
					),
				),
			)
		);
		$this->probe->method( 'realpath' )->willReturnMap(
			array(
				array( '/uploads', '/uploads' ),
				array( '/uploads/2026/07/hero-150x150.jpg', '/uploads/2026/07/hero-150x150.jpg' ),
			)
		);
		$this->probe->method( 'exists' )->with( '/uploads/2026/07/hero-150x150.jpg' )->willReturn( true );
		$this->probe->method( 'is_file' )->with( '/uploads/2026/07/hero-150x150.jpg' )->willReturn( true );
		$this->probe->method( 'is_readable' )->with( '/uploads/2026/07/hero-150x150.jpg' )->willReturn( true );
		$this->probe->method( 'file_size' )->with( '/uploads/2026/07/hero-150x150.jpg' )->willReturn( 100 );
		$this->probe->method( 'modified_time' )->with( '/uploads/2026/07/hero-150x150.jpg' )->willReturn( 1000 );
		$this->probe->method( 'mime_type' )->with( '/uploads/2026/07/hero-150x150.jpg' )->willReturn( 'image/jpeg' );

		$this->meta->expects( self::once() )->method( 'update' )->willReturn( true );

		$result = $this->processor->process( 123 );

		self::assertFalse( $result->is_successful() );
		self::assertSame( array( AttachmentProcessResult::CODE_FINGERPRINT_FAILED ), $result->codes() );
	}

	/**
	 * Test catches unexpected exceptions.
	 */
	public function test_catches_unexpected_exceptions(): void {
		$this->expect_lock_acquired();
		$this->source_provider->method( 'uploads_base_dir' )->willThrowException( new \RuntimeException( 'Boom' ) );

		$result = $this->processor->process( 123 );

		self::assertFalse( $result->is_successful() );
		self::assertSame( array( AttachmentProcessResult::CODE_UNEXPECTED_ERROR ), $result->codes() );
		self::assertStringContainsString( 'Boom', $result->messages()[0] );
	}

	/**
	 * Test one target format is processed and persisted.
	 */
	public function test_process_format_converts_one_format_and_saves_manifest(): void {
		$store  = new FakeAttachmentMetaStore();
		$clock  = new FixedAttachmentClock( 1783526500 );
		$probe  = new FakeImageFileProbe( array( '/uploads' ) );
		$fs     = new FakeConversionFilesystem();
		$editor = new FakeConversionEditor( $fs );

		$probe->add_file( '/uploads/2026/07/hero.jpg', 1000, 1783526400, 'image/jpeg', 2400, 1600 );
		$fs->add_file( '/uploads/2026/07/hero.jpg', 1000, 'image/jpeg', 2400, 1600 );
		$editor->output( 300, 'image/webp', 2400, 1600 );

		$provider = new FakeAttachmentSourceProvider(
			'/uploads/2026/07/hero.jpg',
			array(
				'file'   => '2026/07/hero.jpg',
				'width'  => 2400,
				'height' => 1600,
				'sizes'  => array(),
			),
			'/uploads'
		);

		$settings  = new FakeSettingsRepository( array( 'enabled_formats' => array( 'webp', 'avif' ) ) );
		$processor = new AttachmentProcessor(
			new AttachmentLockManager( $store, new FixedAttachmentLockTokenGenerator( array( 'lock-token' ) ), $clock ),
			new SourceCollector( $provider, $probe ),
			new AttachmentFingerprintBuilder(),
			new DerivativeRepository( $store, new DerivativeManifestSanitizer(), $clock ),
			new SourceImageValidator( $probe, new FakeAnimationDetector() ),
			$settings,
			new ConversionPolicy( $settings, FakeFormatSupportProvider::all_supported(), new ResourceGuard( MemoryLimit::from_raw( '256M' ) ) ),
			new ImageConverter( $editor, $fs, null, null ),
			new DestinationResolver( '/uploads', $probe )
		);

		$result = $processor->process_format( 123, 'webp' );

		self::assertTrue( $result->is_successful() );
		self::assertTrue( $result->is_complete() );
		self::assertSame( 'webp', $result->target_format() );
		self::assertSame( 1, $result->next_cursor() );
		self::assertSame( 'image/webp', $editor->target_mime );
		self::assertArrayHasKey( LifecyclePolicy::META_DERIVATIVES, $store->meta[123] );
		self::assertArrayHasKey( 'webp', $store->meta[123][ LifecyclePolicy::META_DERIVATIVES ]['sizes']['full']['formats'] );
		self::assertArrayNotHasKey( 'avif', $store->meta[123][ LifecyclePolicy::META_DERIVATIVES ]['sizes']['full']['formats'] );
		self::assertArrayNotHasKey( LifecyclePolicy::META_LOCK, $store->meta[123] );
	}

	/**
	 * Setup mock to expect lock acquired.
	 */
	private function expect_lock_acquired(): void {
		$this->clock->method( 'now' )->willReturn( 1000 );
		$this->token_gen->method( 'generate' )->willReturn( 'my_token' );
		$this->meta->method( 'add_unique' )->willReturn( true );
		$this->meta->method( 'get' )->willReturn(
			array(
				'token'      => 'my_token',
				'created_at' => 1000,
				'expires_at' => 1600,
			)
		);
		$this->meta->method( 'delete_value' )->willReturn( true );
	}
}
