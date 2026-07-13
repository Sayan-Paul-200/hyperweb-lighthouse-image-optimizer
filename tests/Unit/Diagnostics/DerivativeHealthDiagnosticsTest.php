<?php
// phpcs:ignoreFile Generic.Files.OneObjectStructurePerFile.MultipleFound -- Test-only fake runtime lives with its only consumer.
/**
 * Tests for derivative health diagnostics.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Diagnostics;

use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifestSanitizer;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeRepository;
use HyperWeb\LighthouseImageOptimizer\Attachment\SystemAttachmentClock;
use HyperWeb\LighthouseImageOptimizer\Diagnostics\DerivativeHealthDiagnostics;
use HyperWeb\LighthouseImageOptimizer\Diagnostics\DerivativeHealthRuntimeInterface;
use HyperWeb\LighthouseImageOptimizer\Diagnostics\DiagnosticStatus;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment\FakeAttachmentMetaStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Delivery\FakeUploadsUrlRuntime;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeImageFileProbe;
use PHPUnit\Framework\TestCase;

/**
 * Verifies bounded missing-derivative diagnostics.
 */
final class DerivativeHealthDiagnosticsTest extends TestCase {

	private const UPLOADS = 'C:/site/wp-content/uploads';

	/**
	 * Test present ready derivatives pass.
	 *
	 * @return void
	 */
	public function test_present_ready_derivatives_pass(): void {
		$runtime = new FakeDerivativeHealthRuntime( array( 10 ) );
		$store   = $this->store_with_manifest( 10, $this->manifest() );
		$files   = $this->files( array( self::UPLOADS . '/2026/07/hero.jpg.hwlio.webp' ) );

		$result = $this->diagnostics( $runtime, $store, $files )->run();

		self::assertSame( DiagnosticStatus::PASS, $result->status() );
		self::assertSame( 'delivery_derivatives_ok', $result->code() );
		self::assertSame( 1, $result->details()['scanned_attachments'] );
		self::assertSame( 1, $result->details()['checked_derivatives'] );
		self::assertSame( 0, $result->details()['missing_derivatives'] );
	}

	/**
	 * Test missing ready derivatives warn with bounded samples.
	 *
	 * @return void
	 */
	public function test_missing_ready_derivatives_warn_with_bounded_samples(): void {
		$runtime = new FakeDerivativeHealthRuntime( array( 10 ) );
		$store   = $this->store_with_manifest( 10, $this->manifest() );
		$files   = $this->files();

		$result = $this->diagnostics( $runtime, $store, $files )->run();

		self::assertSame( DiagnosticStatus::WARNING, $result->status() );
		self::assertSame( 'delivery_derivatives_missing', $result->code() );
		self::assertSame( 1, $result->details()['missing_derivatives'] );
		self::assertSame( array( 10 ), $result->details()['sample_attachment_ids'] );
		self::assertSame( array( '2026/07/hero.jpg.hwlio.webp' ), $result->details()['sample_relative_paths'] );
		self::assertFalse( $this->contains_absolute_path( $result->details() ) );
	}

	/**
	 * Test no ready derivatives reports info.
	 *
	 * @return void
	 */
	public function test_no_ready_derivatives_reports_info(): void {
		$runtime = new FakeDerivativeHealthRuntime( array( 10 ) );
		$store   = $this->store_with_manifest( 10, array() );

		$result = $this->diagnostics( $runtime, $store, $this->files() )->run();

		self::assertSame( DiagnosticStatus::INFO, $result->status() );
		self::assertSame( 'delivery_derivatives_none', $result->code() );
		self::assertSame( 0, $result->details()['checked_derivatives'] );
	}

	/**
	 * Test tampered manifest paths are ignored through repository sanitization.
	 *
	 * @return void
	 */
	public function test_tampered_manifest_paths_are_ignored_through_repository_sanitization(): void {
		$manifest = $this->manifest();
		$manifest['sizes']['full']['formats']['webp']['file'] = '../outside.webp';
		$runtime = new FakeDerivativeHealthRuntime( array( 10 ) );
		$store   = $this->store_with_manifest( 10, $manifest );

		$result = $this->diagnostics( $runtime, $store, $this->files() )->run();

		self::assertSame( DiagnosticStatus::INFO, $result->status() );
		self::assertSame( 'delivery_derivatives_none', $result->code() );
		self::assertSame( 0, $result->details()['checked_derivatives'] );
	}

	/**
	 * Test bounded scan truncation warns.
	 *
	 * @return void
	 */
	public function test_bounded_scan_truncation_warns(): void {
		$ids     = range( 1, 1001 );
		$runtime = new FakeDerivativeHealthRuntime( $ids );
		$store   = new FakeAttachmentMetaStore();

		foreach ( $ids as $attachment_id ) {
			$store->meta[ $attachment_id ][ LifecyclePolicy::META_DERIVATIVES ] = array();
		}

		$result = $this->diagnostics( $runtime, $store, $this->files() )->run();

		self::assertSame( DiagnosticStatus::WARNING, $result->status() );
		self::assertSame( 'delivery_derivatives_scan_truncated', $result->code() );
		self::assertSame( 1000, $result->details()['scanned_attachments'] );
		self::assertFalse( $result->details()['scan_complete'] );
	}

	/**
	 * Test files resolving outside uploads are treated as missing.
	 *
	 * @return void
	 */
	public function test_files_resolving_outside_uploads_are_treated_as_missing(): void {
		$runtime = new FakeDerivativeHealthRuntime( array( 10 ) );
		$store   = $this->store_with_manifest( 10, $this->manifest() );
		$files   = $this->files();
		$files->add_file(
			self::UPLOADS . '/2026/07/hero.jpg.hwlio.webp',
			300,
			100,
			'image/webp',
			2400,
			1600,
			true,
			true,
			'C:/outside/hero.jpg.hwlio.webp'
		);

		$result = $this->diagnostics( $runtime, $store, $files )->run();

		self::assertSame( DiagnosticStatus::WARNING, $result->status() );
		self::assertSame( 1, $result->details()['missing_derivatives'] );
	}

	/**
	 * Build diagnostics.
	 *
	 * @param FakeDerivativeHealthRuntime $runtime Runtime.
	 * @param FakeAttachmentMetaStore     $store Store.
	 * @param FakeImageFileProbe          $files Files.
	 * @return DerivativeHealthDiagnostics
	 */
	private function diagnostics( FakeDerivativeHealthRuntime $runtime, FakeAttachmentMetaStore $store, FakeImageFileProbe $files ): DerivativeHealthDiagnostics {
		$uploads           = new FakeUploadsUrlRuntime();
		$uploads->base_dir = self::UPLOADS;

		return new DerivativeHealthDiagnostics(
			$runtime,
			new DerivativeRepository( $store, new DerivativeManifestSanitizer(), new SystemAttachmentClock() ),
			$uploads,
			$files,
			new DerivativeManifestSanitizer()
		);
	}

	/**
	 * Build fake files.
	 *
	 * @param string[] $paths Existing files.
	 * @return FakeImageFileProbe
	 */
	private function files( array $paths = array() ): FakeImageFileProbe {
		$files = new FakeImageFileProbe(
			array(
				self::UPLOADS,
				self::UPLOADS . '/2026',
				self::UPLOADS . '/2026/07',
			)
		);

		foreach ( $paths as $path ) {
			$files->add_file( $path, 300, 100, 'image/webp', 2400, 1600 );
		}

		return $files;
	}

	/**
	 * Build a store with one manifest.
	 *
	 * @param int                 $attachment_id Attachment ID.
	 * @param array<string,mixed> $manifest Manifest.
	 * @return FakeAttachmentMetaStore
	 */
	private function store_with_manifest( int $attachment_id, array $manifest ): FakeAttachmentMetaStore {
		$store = new FakeAttachmentMetaStore();
		$store->meta[ $attachment_id ][ LifecyclePolicy::META_DERIVATIVES ] = $manifest;

		return $store;
	}

	/**
	 * Build a simple manifest.
	 *
	 * @return array<string,mixed>
	 */
	private function manifest(): array {
		return array(
			'schema_version' => 1,
			'updated_at'     => 1783526500,
			'sizes'          => array(
				'full' => array(
					'source'  => array(
						'file'   => '2026/07/hero.jpg',
						'width'  => 2400,
						'height' => 1600,
						'mime'   => 'image/jpeg',
						'bytes'  => 1000,
					),
					'formats' => array(
						'webp' => array(
							'file'         => '2026/07/hero.jpg.hwlio.webp',
							'mime'         => 'image/webp',
							'bytes'        => 300,
							'quality'      => 82,
							'status'       => 'ready',
							'generated_at' => 1783526500,
						),
					),
				),
			),
		);
	}

	/**
	 * Whether details contain an absolute path.
	 *
	 * @param array<mixed> $details Details.
	 * @return bool
	 */
	private function contains_absolute_path( array $details ): bool {
		foreach ( $details as $value ) {
			if ( is_array( $value ) && $this->contains_absolute_path( $value ) ) {
				return true;
			}

			if ( is_string( $value ) && 1 === preg_match( '/(?:^[A-Za-z]:[\\\\\/]|^\/)/', $value ) ) {
				return true;
			}
		}

		return false;
	}
}

/**
 * Fake bounded derivative-health runtime.
 */
final class FakeDerivativeHealthRuntime implements DerivativeHealthRuntimeInterface {

	/**
	 * Attachment IDs.
	 *
	 * @var int[]
	 */
	private $ids;

	/**
	 * Create runtime.
	 *
	 * @param int[] $ids IDs.
	 */
	public function __construct( array $ids ) {
		$this->ids = array_values( array_filter( array_map( 'intval', $ids ) ) );
		sort( $this->ids );
	}

	/**
	 * Read attachment IDs after cursor.
	 *
	 * @param int $after_id Cursor.
	 * @param int $limit Limit.
	 * @return int[]
	 */
	public function attachment_ids_after( int $after_id, int $limit ): array {
		$ids = array_values(
			array_filter(
				$this->ids,
				static function ( int $id ) use ( $after_id ): bool {
					return $id > $after_id;
				}
			)
		);

		return array_slice( $ids, 0, max( 1, $limit ) );
	}
}
