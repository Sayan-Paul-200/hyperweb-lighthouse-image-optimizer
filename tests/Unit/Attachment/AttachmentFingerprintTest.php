<?php
/**
 * Tests for attachment fingerprinting.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment;

use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentFingerprint;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentFingerprintBuilder;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentFingerprintCode;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentFingerprintComparison;
use HyperWeb\LighthouseImageOptimizer\Image\SourceImage;
use HyperWeb\LighthouseImageOptimizer\Image\SourceImageCollection;
use HyperWeb\LighthouseImageOptimizer\Image\SourceImageIssue;
use PHPUnit\Framework\TestCase;

/**
 * Verifies cheap attachment source and metadata fingerprint behavior.
 */
final class AttachmentFingerprintTest extends TestCase {

	private const UPLOADS = 'C:/site/wp-content/uploads';

	/**
	 * Test deterministic fingerprints for unchanged source collections.
	 *
	 * @return void
	 */
	public function test_unchanged_collection_builds_deterministic_fingerprint(): void {
		$collection = $this->collection();
		$first      = $this->fingerprint( $collection );
		$second     = $this->fingerprint( $collection );

		self::assertSame( $first->to_array(), $second->to_array() );
		self::assertSame( '2026/07/hero.jpg', $first->relative_file() );
		self::assertSame( 1000, $first->file_size() );
		self::assertSame( 100, $first->modified_time() );
		self::assertMatchesRegularExpression( '/^[a-f0-9]{64}$/', $first->metadata_hash() );
		self::assertMatchesRegularExpression( '/^[a-f0-9]{20}$/', $first->signature() );
		self::assertSame( 20, AttachmentFingerprint::signature_length() );
	}

	/**
	 * Test source file replacement invalidates stored fingerprint.
	 *
	 * @return void
	 */
	public function test_replacing_full_source_path_invalidates_stored_fingerprint(): void {
		$stored = $this->fingerprint( $this->collection() )->to_array();

		$comparison = $this->builder()->compare_stored(
			$stored,
			$this->collection( '2026/07/hero-replaced.jpg', 1000, 100 )
		);

		self::assertTrue( $comparison->is_stale() );
		self::assertSame( AttachmentFingerprintCode::SOURCE_PATH_CHANGED, $comparison->code() );
	}

	/**
	 * Test byte-size changes invalidate stored fingerprint.
	 *
	 * @return void
	 */
	public function test_full_source_size_change_invalidates_stored_fingerprint(): void {
		$stored = $this->fingerprint( $this->collection() )->to_array();

		$comparison = $this->builder()->compare_stored(
			$stored,
			$this->collection( '2026/07/hero.jpg', 1200, 100 )
		);

		self::assertTrue( $comparison->is_stale() );
		self::assertSame( AttachmentFingerprintCode::SOURCE_BYTES_CHANGED, $comparison->code() );
	}

	/**
	 * Test modified-time changes invalidate stored fingerprint.
	 *
	 * @return void
	 */
	public function test_full_source_modified_time_change_invalidates_stored_fingerprint(): void {
		$stored = $this->fingerprint( $this->collection() )->to_array();

		$comparison = $this->builder()->compare_stored(
			$stored,
			$this->collection( '2026/07/hero.jpg', 1000, 200 )
		);

		self::assertTrue( $comparison->is_stale() );
		self::assertSame( AttachmentFingerprintCode::SOURCE_MODIFIED_CHANGED, $comparison->code() );
	}

	/**
	 * Test regenerated subsizes invalidate the metadata hash.
	 *
	 * @return void
	 */
	public function test_regenerated_subsize_changes_metadata_hash(): void {
		$stored = $this->fingerprint( $this->collection() )->to_array();

		$comparison = $this->builder()->compare_stored(
			$stored,
			$this->collection_with_subsize( '2026/07/hero-300x200.jpg', 300, 200, 275 )
		);

		self::assertTrue( $comparison->is_stale() );
		self::assertSame( AttachmentFingerprintCode::METADATA_HASH_CHANGED, $comparison->code() );
	}

	/**
	 * Test source collection issues affect metadata hash without blocking full fingerprinting.
	 *
	 * @return void
	 */
	public function test_collection_issues_affect_metadata_hash_without_blocking_full_source(): void {
		$clean      = $this->fingerprint( $this->collection() );
		$with_issue = $this->fingerprint(
			new SourceImageCollection(
				$this->collection()->sources(),
				array(
					new SourceImageIssue(
						123,
						'thumbnail',
						SourceImage::ROLE_SUBSIZE,
						SourceImageIssue::CODE_SOURCE_MISSING,
						'Missing thumbnail at C:/site/wp-content/uploads/2026/07/missing.jpg',
						array( 'relative_path' => '2026/07/missing.jpg' )
					),
				)
			)
		);

		self::assertNotSame( $clean->metadata_hash(), $with_issue->metadata_hash() );

		$comparison = $this->builder()->compare_stored( $clean->to_array(), $this->collection_with_issue() );

		self::assertSame( AttachmentFingerprintCode::METADATA_HASH_CHANGED, $comparison->code() );
	}

	/**
	 * Test missing full source returns a safe invalid comparison result.
	 *
	 * @return void
	 */
	public function test_missing_full_source_returns_safe_invalid_comparison(): void {
		$comparison = $this->builder()->compare_signature(
			str_repeat( 'a', AttachmentFingerprint::signature_length() ),
			new SourceImageCollection(
				array(
					$this->source( '2026/07/hero-150x100.jpg', SourceImage::ROLE_SUBSIZE, 'thumbnail', 150, 100, 100, 100 ),
				)
			)
		);

		self::assertTrue( $comparison->is_invalid() );
		self::assertSame( AttachmentFingerprintCode::FINGERPRINT_MISSING, $comparison->code() );
		self::assertNull( $comparison->current_fingerprint() );
	}

	/**
	 * Test queued signature matching and mismatch behavior.
	 *
	 * @return void
	 */
	public function test_queued_signature_match_and_mismatch_are_reported(): void {
		$collection  = $this->collection();
		$fingerprint = $this->fingerprint( $collection );

		$match = $this->builder()->compare_signature( $fingerprint->signature(), $collection );
		$stale = $this->builder()->compare_signature( str_repeat( 'b', AttachmentFingerprint::signature_length() ), $collection );

		self::assertTrue( $match->is_match() );
		self::assertSame( AttachmentFingerprintCode::FINGERPRINT_MATCH, $match->code() );
		self::assertTrue( $stale->is_stale() );
		self::assertSame( AttachmentFingerprintCode::FINGERPRINT_MISMATCH, $stale->code() );
		self::assertSame( str_repeat( 'b', 20 ), $stale->details()['queued_signature'] );
	}

	/**
	 * Test malformed stored fingerprint payloads are rejected.
	 *
	 * @return void
	 */
	public function test_malformed_stored_fingerprint_is_rejected(): void {
		$comparison = $this->builder()->compare_stored(
			array(
				'relative_file' => '../hero.jpg',
				'file_size'     => 1000,
				'modified_time' => 100,
				'metadata_hash' => str_repeat( 'a', 64 ),
			),
			$this->collection()
		);

		self::assertTrue( $comparison->is_invalid() );
		self::assertSame( AttachmentFingerprintCode::FINGERPRINT_INVALID, $comparison->code() );
	}

	/**
	 * Test master-plan-compatible stored fingerprints without signatures compare successfully.
	 *
	 * @return void
	 */
	public function test_stored_fingerprint_without_signature_is_accepted(): void {
		$stored = $this->fingerprint( $this->collection() )->to_array();
		unset( $stored['signature'] );

		$comparison = $this->builder()->compare_stored( $stored, $this->collection() );

		self::assertTrue( $comparison->is_match() );
		self::assertSame( AttachmentFingerprintCode::FINGERPRINT_MATCH, $comparison->code() );
	}

	/**
	 * Test serialization does not expose absolute paths.
	 *
	 * @return void
	 */
	public function test_serialization_omits_absolute_paths(): void {
		$comparison = $this->builder()->compare_stored(
			$this->fingerprint( $this->collection() )->to_array(),
			$this->collection()
		);

		$serialized = $comparison->to_array();

		self::assertIsArray( $serialized['current'] );
		self::assertArrayNotHasKey( 'absolute_path', $serialized['current'] );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Unit test intentionally avoids WordPress bootstrap.
		$json = (string) json_encode( $serialized );

		self::assertStringNotContainsString( self::UPLOADS, $json );
		self::assertStringNotContainsString( 'absolute_path', $json );
	}

	/**
	 * Test code taxonomy contains required codes.
	 *
	 * @return void
	 */
	public function test_fingerprint_code_taxonomy_contains_required_codes(): void {
		$expected = array(
			AttachmentFingerprintCode::FINGERPRINT_MATCH,
			AttachmentFingerprintCode::FINGERPRINT_MISSING,
			AttachmentFingerprintCode::FINGERPRINT_INVALID,
			AttachmentFingerprintCode::FINGERPRINT_MISMATCH,
			AttachmentFingerprintCode::SOURCE_PATH_CHANGED,
			AttachmentFingerprintCode::SOURCE_BYTES_CHANGED,
			AttachmentFingerprintCode::SOURCE_MODIFIED_CHANGED,
			AttachmentFingerprintCode::METADATA_HASH_CHANGED,
		);

		foreach ( $expected as $code ) {
			self::assertContains( $code, AttachmentFingerprintCode::all_codes() );
		}
	}

	/**
	 * Get builder.
	 *
	 * @return AttachmentFingerprintBuilder
	 */
	private function builder(): AttachmentFingerprintBuilder {
		return new AttachmentFingerprintBuilder();
	}

	/**
	 * Build and assert fingerprint.
	 *
	 * @param SourceImageCollection $collection Collection.
	 * @return AttachmentFingerprint
	 */
	private function fingerprint( SourceImageCollection $collection ): AttachmentFingerprint {
		$fingerprint = $this->builder()->build( $collection );

		if ( ! $fingerprint instanceof AttachmentFingerprint ) {
			self::fail( 'Expected attachment fingerprint.' );
		}

		return $fingerprint;
	}

	/**
	 * Build a default collection.
	 *
	 * @param string $full_path Full path.
	 * @param int    $full_bytes Full bytes.
	 * @param int    $full_mtime Full modified time.
	 * @return SourceImageCollection
	 */
	private function collection(
		string $full_path = '2026/07/hero.jpg',
		int $full_bytes = 1000,
		int $full_mtime = 100
	): SourceImageCollection {
		return new SourceImageCollection(
			array(
				$this->source( $full_path, SourceImage::ROLE_FULL, 'full', 2400, 1600, $full_bytes, $full_mtime ),
				$this->source( '2026/07/hero-150x100.jpg', SourceImage::ROLE_SUBSIZE, 'thumbnail', 150, 100, 125, 100 ),
				$this->source( '2026/07/hero-original.jpg', SourceImage::ROLE_ORIGINAL, 'original', 2400, 1600, 1100, 90 ),
			)
		);
	}

	/**
	 * Build a collection with regenerated subsize data.
	 *
	 * @param string $subsize_path Subsize path.
	 * @param int    $width Width.
	 * @param int    $height Height.
	 * @param int    $bytes Bytes.
	 * @return SourceImageCollection
	 */
	private function collection_with_subsize( string $subsize_path, int $width, int $height, int $bytes ): SourceImageCollection {
		return new SourceImageCollection(
			array(
				$this->source( '2026/07/hero.jpg', SourceImage::ROLE_FULL, 'full', 2400, 1600, 1000, 100 ),
				$this->source( $subsize_path, SourceImage::ROLE_SUBSIZE, 'thumbnail', $width, $height, $bytes, 150 ),
				$this->source( '2026/07/hero-original.jpg', SourceImage::ROLE_ORIGINAL, 'original', 2400, 1600, 1100, 90 ),
			)
		);
	}

	/**
	 * Build a collection with one source issue.
	 *
	 * @return SourceImageCollection
	 */
	private function collection_with_issue(): SourceImageCollection {
		return new SourceImageCollection(
			$this->collection()->sources(),
			array(
				new SourceImageIssue(
					123,
					'thumbnail',
					SourceImage::ROLE_SUBSIZE,
					SourceImageIssue::CODE_SOURCE_MISSING,
					'Missing thumbnail.',
					array( 'relative_path' => '2026/07/missing.jpg' )
				),
			)
		);
	}

	/**
	 * Build source image.
	 *
	 * @param string $relative_path Relative path.
	 * @param string $role Role.
	 * @param string $size_name Size name.
	 * @param int    $width Width.
	 * @param int    $height Height.
	 * @param int    $bytes Bytes.
	 * @param int    $modified_time Modified time.
	 * @return SourceImage
	 */
	private function source(
		string $relative_path,
		string $role,
		string $size_name,
		int $width,
		int $height,
		int $bytes,
		int $modified_time
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
			$modified_time
		);
	}
}
