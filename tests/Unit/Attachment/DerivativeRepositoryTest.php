<?php
/**
 * Tests for derivative repository.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment;

use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentFingerprint;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentStatus;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifest;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifestSanitizer;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeRepository;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeRepositoryResult;
use HyperWeb\LighthouseImageOptimizer\Image\ConversionOutput;
use HyperWeb\LighthouseImageOptimizer\Image\ConversionResult;
use HyperWeb\LighthouseImageOptimizer\Image\ConversionResultCode;
use HyperWeb\LighthouseImageOptimizer\Image\ConversionResultCollection;
use HyperWeb\LighthouseImageOptimizer\Image\ConversionSavings;
use HyperWeb\LighthouseImageOptimizer\Image\DestinationPath;
use HyperWeb\LighthouseImageOptimizer\Image\SourceImage;
use HyperWeb\LighthouseImageOptimizer\Image\SourceMimePolicy;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;
use PHPUnit\Framework\TestCase;

/**
 * Verifies plugin-owned derivative metadata persistence.
 */
final class DerivativeRepositoryTest extends TestCase {

	private const UPLOADS = 'C:/site/wp-content/uploads';
	private const NOW     = 1783526500;

	/**
	 * Test missing metadata returns empty manifest and unprocessed status.
	 *
	 * @return void
	 */
	public function test_missing_metadata_returns_empty_manifest_and_unprocessed_status(): void {
		$result = $this->repository( new FakeAttachmentMetaStore() )->read( 123 );

		self::assertTrue( $result->is_successful() );
		self::assertSame( DerivativeRepositoryResult::CODE_EMPTY, $result->codes()[0] );
		self::assertFalse( $result->manifest()->has_derivatives() );
		self::assertSame( AttachmentStatus::STATE_UNPROCESSED, $result->status()->state() );
	}

	/**
	 * Test saving one successful conversion writes expected manifest shape.
	 *
	 * @return void
	 */
	public function test_saving_one_successful_conversion_writes_exact_manifest_shape(): void {
		$store  = new FakeAttachmentMetaStore();
		$result = $this->repository( $store )->save_results(
			123,
			$this->fingerprint(),
			new ConversionResultCollection( array( $this->success_result() ) )
		);

		$manifest = $store->meta[123][ LifecyclePolicy::META_DERIVATIVES ];
		$status   = $store->meta[123][ LifecyclePolicy::META_STATUS ];

		self::assertTrue( $result->is_successful() );
		self::assertSame( 1, $manifest['schema_version'] );
		self::assertSame( $this->fingerprint()->to_array(), $manifest['fingerprint'] );
		self::assertSame( self::NOW, $manifest['updated_at'] );
		self::assertSame( '2026/07/hero.jpg', $manifest['sizes']['full']['source']['file'] );
		self::assertSame( 2400, $manifest['sizes']['full']['source']['width'] );
		self::assertSame( 1600, $manifest['sizes']['full']['source']['height'] );
		self::assertSame( 'image/jpeg', $manifest['sizes']['full']['source']['mime'] );
		self::assertSame( 1000, $manifest['sizes']['full']['source']['bytes'] );
		self::assertSame( '2026/07/hero.jpg.hwlio.webp', $manifest['sizes']['full']['formats']['webp']['file'] );
		self::assertSame( 'image/webp', $manifest['sizes']['full']['formats']['webp']['mime'] );
		self::assertSame( 300, $manifest['sizes']['full']['formats']['webp']['bytes'] );
		self::assertSame( 82, $manifest['sizes']['full']['formats']['webp']['quality'] );
		self::assertSame( 700, $manifest['sizes']['full']['formats']['webp']['savings_bytes'] );
		self::assertSame( 70.0, $manifest['sizes']['full']['formats']['webp']['savings_percent'] );
		self::assertSame( DerivativeManifest::FORMAT_STATUS_READY, $manifest['sizes']['full']['formats']['webp']['status'] );
		self::assertSame( self::NOW, $status['updated_at'] );
		self::assertSame( AttachmentStatus::STATE_PARTIAL, $status['state'] );
		self::assertSame( array( AttachmentStatus::FORMAT_WEBP ), $status['formats'] );
		self::assertFalse( $store->wrote_core_metadata() );
	}

	/**
	 * Test saving WebP then AVIF preserves both formats.
	 *
	 * @return void
	 */
	public function test_saving_webp_then_avif_for_same_size_preserves_both_formats(): void {
		$store      = new FakeAttachmentMetaStore();
		$repository = $this->repository( $store );

		$repository->save_results(
			123,
			$this->fingerprint(),
			new ConversionResultCollection( array( $this->success_result() ) )
		);
		$repository->save_results(
			123,
			$this->fingerprint(),
			new ConversionResultCollection( array( $this->success_result( SourceMimePolicy::TARGET_AVIF ) ) )
		);

		$formats = $store->meta[123][ LifecyclePolicy::META_DERIVATIVES ]['sizes']['full']['formats'];
		$status  = $store->meta[123][ LifecyclePolicy::META_STATUS ];

		self::assertArrayHasKey( AttachmentStatus::FORMAT_WEBP, $formats );
		self::assertArrayHasKey( AttachmentStatus::FORMAT_AVIF, $formats );
		self::assertSame( array( AttachmentStatus::FORMAT_WEBP, AttachmentStatus::FORMAT_AVIF ), $status['formats'] );
	}

	/**
	 * Test saving subsize results later preserves full-size entries.
	 *
	 * @return void
	 */
	public function test_saving_subsize_later_preserves_existing_full_size_entries(): void {
		$store      = new FakeAttachmentMetaStore();
		$repository = $this->repository( $store );

		$repository->save_results(
			123,
			$this->fingerprint(),
			new ConversionResultCollection( array( $this->success_result() ) )
		);
		$repository->save_results(
			123,
			$this->fingerprint(),
			new ConversionResultCollection(
				array(
					$this->success_result(
						SourceMimePolicy::TARGET_WEBP,
						$this->source( 'thumbnail', SourceImage::ROLE_SUBSIZE, '2026/07/hero-150x100.jpg', 150, 100, 125 ),
						'2026/07/hero-150x100.jpg.hwlio.webp'
					),
				)
			)
		);

		$sizes = $store->meta[123][ LifecyclePolicy::META_DERIVATIVES ]['sizes'];

		self::assertArrayHasKey( 'full', $sizes );
		self::assertArrayHasKey( 'thumbnail', $sizes );
		self::assertSame( '2026/07/hero.jpg.hwlio.webp', $sizes['full']['formats']['webp']['file'] );
		self::assertSame( '2026/07/hero-150x100.jpg.hwlio.webp', $sizes['thumbnail']['formats']['webp']['file'] );
	}

	/**
	 * Test repeated save is idempotent.
	 *
	 * @return void
	 */
	public function test_repeated_save_of_same_size_and_format_is_idempotent(): void {
		$store      = new FakeAttachmentMetaStore();
		$repository = $this->repository( $store );
		$results    = new ConversionResultCollection( array( $this->success_result() ) );

		$repository->save_results( 123, $this->fingerprint(), $results );
		$first = $store->meta[123][ LifecyclePolicy::META_DERIVATIVES ];
		$repository->save_results( 123, $this->fingerprint(), $results );
		$second = $store->meta[123][ LifecyclePolicy::META_DERIVATIVES ];

		self::assertSame( $first, $second );
	}

	/**
	 * Test skipped and failed results do not create ready derivative entries.
	 *
	 * @return void
	 */
	public function test_skipped_and_failed_results_do_not_create_ready_derivative_entries(): void {
		$store  = new FakeAttachmentMetaStore();
		$result = $this->repository( $store )->save_results(
			123,
			$this->fingerprint(),
			new ConversionResultCollection(
				array(
					ConversionResult::skipped(
						$this->source(),
						SourceMimePolicy::TARGET_WEBP,
						'image/webp',
						ConversionResultCode::SKIPPED_NOT_SMALLER,
						'Not smaller.',
						new ConversionSavings( 1000, 1000 )
					),
					ConversionResult::failed(
						$this->source(),
						SourceMimePolicy::TARGET_WEBP,
						'image/webp',
						ConversionResultCode::CONVERSION_FAILED,
						'Conversion failed.'
					),
				)
			)
		);

		self::assertTrue( $result->is_successful() );
		self::assertTrue( $result->has_code( DerivativeRepositoryResult::CODE_NO_READY_RESULTS ) );
		self::assertArrayNotHasKey( LifecyclePolicy::META_DERIVATIVES, $store->meta[123] );
		self::assertSame( AttachmentStatus::STATE_FAILED, $store->meta[123][ LifecyclePolicy::META_STATUS ]['state'] );
		self::assertSame( ConversionResultCode::CONVERSION_FAILED, $store->meta[123][ LifecyclePolicy::META_STATUS ]['error_code'] );
	}

	/**
	 * Test fingerprint mismatch refuses to overwrite stored manifest.
	 *
	 * @return void
	 */
	public function test_fingerprint_mismatch_refuses_to_overwrite_existing_manifest(): void {
		$store = new FakeAttachmentMetaStore();
		$store->meta[123][ LifecyclePolicy::META_DERIVATIVES ] = $this->stored_manifest( $this->fingerprint() );
		$before = $store->meta[123][ LifecyclePolicy::META_DERIVATIVES ];

		$mismatched = new AttachmentFingerprint( '2026/07/replaced.jpg', 1000, 100, str_repeat( 'b', 64 ) );
		$result     = $this->repository( $store )->save_results(
			123,
			$mismatched,
			new ConversionResultCollection( array( $this->success_result() ) )
		);

		self::assertFalse( $result->is_successful() );
		self::assertTrue( $result->has_warnings() );
		self::assertTrue( $result->has_code( DerivativeRepositoryResult::CODE_FINGERPRINT_MISMATCH ) );
		self::assertSame( $before, $store->meta[123][ LifecyclePolicy::META_DERIVATIVES ] );
		self::assertCount( 0, $store->updates );
	}

	/**
	 * Test invalid stored metadata is sanitized before return.
	 *
	 * @return void
	 */
	public function test_invalid_stored_metadata_is_ignored_or_sanitized(): void {
		$store = new FakeAttachmentMetaStore();
		$store->meta[123][ LifecyclePolicy::META_DERIVATIVES ] = $this->dirty_manifest();

		$result = $this->repository( $store )->read( 123 );
		$sizes  = $result->manifest()->sizes();

		self::assertTrue( $result->has_warnings() );
		self::assertTrue( $result->has_code( DerivativeRepositoryResult::CODE_INVALID_METADATA_IGNORED ) );
		self::assertArrayHasKey( 'full', $sizes );
		self::assertArrayNotHasKey( 'bad_source', $sizes );
		self::assertArrayHasKey( 'webp', $sizes['full']['formats'] );
		self::assertArrayNotHasKey( 'gif', $sizes['full']['formats'] );
		self::assertArrayNotHasKey( 'avif', $sizes['full']['formats'] );
		self::assertArrayNotHasKey( 'pending', $sizes['full']['formats'] );
	}

	/**
	 * Test manifest path sanitizer rejects unsafe path forms.
	 *
	 * @return void
	 */
	public function test_manifest_path_sanitizer_rejects_unsafe_path_forms(): void {
		$sanitizer = new DerivativeManifestSanitizer();

		self::assertSame( '2026/07/hero.jpg.hwlio.webp', $sanitizer->safe_relative_path( '2026/07/hero.jpg.hwlio.webp' ) );
		self::assertSame( '', $sanitizer->safe_relative_path( '/var/www/uploads/hero.jpg.hwlio.webp' ) );
		self::assertSame( '', $sanitizer->safe_relative_path( 'C:\\site\\uploads\\hero.jpg.hwlio.webp' ) );
		self::assertSame( '', $sanitizer->safe_relative_path( 'C:site/uploads/hero.jpg.hwlio.webp' ) );
		self::assertSame( '', $sanitizer->safe_relative_path( '\\\\server\\share\\hero.jpg.hwlio.webp' ) );
		self::assertSame( '', $sanitizer->safe_relative_path( '../hero.jpg.hwlio.webp' ) );
		self::assertSame( '', $sanitizer->safe_relative_path( 'https://example.test/hero.jpg.hwlio.webp' ) );
		self::assertSame( '', $sanitizer->safe_relative_path( "2026/07/hero.jpg\0.hwlio.webp" ) );
	}

	/**
	 * Test invalid schema is ignored.
	 *
	 * @return void
	 */
	public function test_invalid_stored_schema_is_ignored(): void {
		$store = new FakeAttachmentMetaStore();
		$store->meta[123][ LifecyclePolicy::META_DERIVATIVES ] = array(
			'schema_version' => 99,
			'sizes'          => array( 'full' => array() ),
		);

		$result = $this->repository( $store )->read( 123 );

		self::assertFalse( $result->manifest()->has_derivatives() );
		self::assertTrue( $result->has_code( DerivativeRepositoryResult::CODE_INVALID_METADATA_IGNORED ) );
	}

	/**
	 * Test status summary normalization.
	 *
	 * @return void
	 */
	public function test_status_summary_normalizes_invalid_values(): void {
		$status = AttachmentStatus::from_stored(
			array(
				'state'      => 'wild',
				'formats'    => array( 'avif', 'gif', 'webp', 'webp' ),
				'updated_at' => -5,
				'error_code' => 'Bad Code!',
				'excluded'   => 1,
			)
		);

		self::assertSame( AttachmentStatus::STATE_UNPROCESSED, $status->state() );
		self::assertSame( array( AttachmentStatus::FORMAT_WEBP, AttachmentStatus::FORMAT_AVIF ), $status->formats_ready() );
		self::assertSame( 0, $status->updated_at() );
		self::assertSame( 'bad_code', $status->error_code() );
		self::assertTrue( $status->excluded() );
	}

	/**
	 * Test repository can save status independently.
	 *
	 * @return void
	 */
	public function test_save_status_persists_status_summary_only(): void {
		$store  = new FakeAttachmentMetaStore();
		$status = new AttachmentStatus( AttachmentStatus::STATE_QUEUED, array( 'webp' ), self::NOW );

		$result = $this->repository( $store )->save_status( 123, $status );

		self::assertTrue( $result->is_successful() );
		self::assertSame( $status->to_array(), $store->meta[123][ LifecyclePolicy::META_STATUS ] );
		self::assertArrayNotHasKey( LifecyclePolicy::META_DERIVATIVES, $store->meta[123] );
		self::assertFalse( $store->wrote_core_metadata() );
	}

	/**
	 * Test begin reconciliation resets the active manifest to the current fingerprint.
	 *
	 * @return void
	 */
	public function test_begin_reconciliation_resets_active_manifest_to_current_fingerprint(): void {
		$store = new FakeAttachmentMetaStore();
		$store->meta[123][ LifecyclePolicy::META_DERIVATIVES ] = $this->stored_manifest(
			new AttachmentFingerprint( '2026/07/hero.jpg', 1000, 100, str_repeat( 'b', 64 ) )
		);

		$result = $this->repository( $store )->begin_reconciliation( 123, $this->fingerprint() );

		self::assertTrue( $result->is_successful() );
		self::assertTrue( $result->has_code( DerivativeRepositoryResult::CODE_RECONCILIATION_STARTED ) );
		self::assertSame( $this->fingerprint()->to_array(), $store->meta[123][ LifecyclePolicy::META_DERIVATIVES ]['fingerprint'] );
		self::assertSame( array(), $store->meta[123][ LifecyclePolicy::META_DERIVATIVES ]['sizes'] );
	}

	/**
	 * Build repository.
	 *
	 * @param FakeAttachmentMetaStore $store Store.
	 * @return DerivativeRepository
	 */
	private function repository( FakeAttachmentMetaStore $store ): DerivativeRepository {
		return new DerivativeRepository(
			$store,
			new DerivativeManifestSanitizer(),
			new FixedAttachmentClock( self::NOW )
		);
	}

	/**
	 * Build fingerprint.
	 *
	 * @return AttachmentFingerprint
	 */
	private function fingerprint(): AttachmentFingerprint {
		return new AttachmentFingerprint( '2026/07/hero.jpg', 1000, 100, str_repeat( 'a', 64 ) );
	}

	/**
	 * Build source image.
	 *
	 * @param string $size_name Size name.
	 * @param string $role Source role.
	 * @param string $relative_path Relative path.
	 * @param int    $width Width.
	 * @param int    $height Height.
	 * @param int    $bytes Bytes.
	 * @return SourceImage
	 */
	private function source(
		string $size_name = 'full',
		string $role = SourceImage::ROLE_FULL,
		string $relative_path = '2026/07/hero.jpg',
		int $width = 2400,
		int $height = 1600,
		int $bytes = 1000
	): SourceImage {
		return new SourceImage(
			123,
			$size_name,
			$role,
			$relative_path,
			self::UPLOADS . '/' . $relative_path,
			'image/jpeg',
			$width,
			$height,
			$bytes,
			100
		);
	}

	/**
	 * Build successful conversion result.
	 *
	 * @param string           $format Target format.
	 * @param SourceImage|null $source Source image.
	 * @param string|null      $relative_path Output path.
	 * @return ConversionResult
	 */
	private function success_result(
		string $format = SourceMimePolicy::TARGET_WEBP,
		?SourceImage $source = null,
		?string $relative_path = null
	): ConversionResult {
		$source = $source instanceof SourceImage ? $source : $this->source();
		$mime   = SourceMimePolicy::TARGET_AVIF === $format ? 'image/avif' : 'image/webp';
		$path   = null === $relative_path ? '2026/07/hero.jpg.hwlio.' . $format : $relative_path;
		$output = new ConversionOutput( $path, $mime, $source->width(), $source->height(), 300, 82, self::NOW );

		return ConversionResult::success(
			$source,
			$this->destination( $format, $mime, $path ),
			$output,
			ConversionSavings::from_source_and_output( $source, $output, 5.0 )
		);
	}

	/**
	 * Build destination path.
	 *
	 * @param string $format Target format.
	 * @param string $mime MIME.
	 * @param string $relative_path Relative path.
	 * @return DestinationPath
	 */
	private function destination( string $format, string $mime, string $relative_path ): DestinationPath {
		return new DestinationPath(
			$format,
			$mime,
			$relative_path,
			self::UPLOADS . '/' . $relative_path,
			$relative_path . '.tmp',
			self::UPLOADS . '/' . $relative_path . '.tmp'
		);
	}

	/**
	 * Build stored manifest.
	 *
	 * @param AttachmentFingerprint $fingerprint Fingerprint.
	 * @return array<string,mixed>
	 */
	private function stored_manifest( AttachmentFingerprint $fingerprint ): array {
		return array(
			'schema_version' => 1,
			'fingerprint'    => $fingerprint->to_array(),
			'updated_at'     => self::NOW,
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
							'file'            => '2026/07/hero.jpg.hwlio.webp',
							'mime'            => 'image/webp',
							'bytes'           => 300,
							'quality'         => 82,
							'savings_bytes'   => 700,
							'savings_percent' => 70.0,
							'status'          => 'ready',
							'generated_at'    => self::NOW,
						),
					),
				),
			),
		);
	}

	/**
	 * Build dirty manifest with unsafe and invalid entries.
	 *
	 * @return array<string,mixed>
	 */
	private function dirty_manifest(): array {
		$manifest                                        = $this->stored_manifest( $this->fingerprint() );
		$manifest['sizes']['bad_source']                 = array(
			'source'  => array(
				'file'   => '../hero.jpg',
				'width'  => 1,
				'height' => 1,
				'mime'   => 'image/jpeg',
				'bytes'  => 1,
			),
			'formats' => array(
				'webp' => array(
					'file'   => '2026/07/bad.webp',
					'mime'   => 'image/webp',
					'status' => 'ready',
				),
			),
		);
		$manifest['sizes']['full']['formats']['gif']     = array(
			'file'   => '2026/07/hero.gif',
			'mime'   => 'image/gif',
			'status' => 'ready',
		);
		$manifest['sizes']['full']['formats']['avif']    = array(
			'file'   => '2026/07/hero.jpg.hwlio.avif',
			'mime'   => 'image/webp',
			'status' => 'ready',
		);
		$manifest['sizes']['full']['formats']['pending'] = array(
			'file'   => '2026/07/hero.jpg.hwlio.webp',
			'mime'   => 'image/webp',
			'status' => 'pending',
		);

		return $manifest;
	}
}
