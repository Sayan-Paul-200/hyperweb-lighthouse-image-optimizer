<?php
/**
 * Tests for source image collection.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image;

use HyperWeb\LighthouseImageOptimizer\Image\SourceCollector;
use HyperWeb\LighthouseImageOptimizer\Image\SourceImage;
use HyperWeb\LighthouseImageOptimizer\Image\SourceImageCollection;
use HyperWeb\LighthouseImageOptimizer\Image\SourceImageIssue;
use PHPUnit\Framework\TestCase;

/**
 * Verifies read-only attachment source collection.
 */
final class SourceCollectorTest extends TestCase {

	private const UPLOADS = 'C:/site/wp-content/uploads';

	/**
	 * Test full source collection.
	 *
	 * @return void
	 */
	public function test_collects_full_image_from_attached_file_and_metadata(): void {
		$probe = $this->probe();
		$probe->add_file( self::UPLOADS . '/2026/07/hero.jpg', 920000, 1783526400, 'image/jpeg', 2400, 1600 );

		$collection = $this->collector( $probe )->collect( 123 );

		self::assertCount( 1, $collection->sources() );
		self::assertFalse( $collection->has_issues() );

		$source = $collection->sources()[0];
		self::assertSame( 123, $source->attachment_id() );
		self::assertSame( 'full', $source->size_name() );
		self::assertSame( SourceImage::ROLE_FULL, $source->role() );
		self::assertSame( '2026/07/hero.jpg', $source->relative_path() );
		self::assertSame( self::UPLOADS . '/2026/07/hero.jpg', $source->absolute_path() );
		self::assertSame( 'image/jpeg', $source->mime_type() );
		self::assertSame( 2400, $source->width() );
		self::assertSame( 1600, $source->height() );
		self::assertSame( 920000, $source->bytes() );
		self::assertSame( 1783526400, $source->modified_time() );
		self::assertArrayNotHasKey( 'absolute_path', $source->to_array() );
	}

	/**
	 * Test subsize basename resolution and deterministic order.
	 *
	 * @return void
	 */
	public function test_collects_subsizes_from_metadata_in_order(): void {
		$probe = $this->probe();
		$probe->add_file( self::UPLOADS . '/2026/07/hero.jpg', 920000, 1783526400, 'image/jpeg', 2400, 1600 );
		$probe->add_file( self::UPLOADS . '/2026/07/hero-150x100.jpg', 12000, 1783526401, 'image/jpeg', 150, 100 );
		$probe->add_file( self::UPLOADS . '/2026/07/hero-300x200.jpg', 28000, 1783526402, 'image/jpeg', 300, 200 );

		$metadata = $this->metadata(
			array(
				'thumbnail' => array(
					'file'   => 'hero-150x100.jpg',
					'width'  => 150,
					'height' => 100,
				),
				'medium'    => array(
					'file'   => 'hero-300x200.jpg',
					'width'  => 300,
					'height' => 200,
				),
			)
		);

		$collection = $this->collector( $probe, $metadata )->collect( 123 );

		self::assertSame(
			array( 'full', 'thumbnail', 'medium' ),
			array_map(
				static function ( SourceImage $source ): string {
					return $source->size_name();
				},
				$collection->sources()
			)
		);
		self::assertSame( '2026/07/hero-150x100.jpg', $collection->sources()[1]->relative_path() );
		self::assertSame( '2026/07/hero-300x200.jpg', $collection->sources()[2]->relative_path() );
	}

	/**
	 * Test WordPress scaled image original relationship.
	 *
	 * @return void
	 */
	public function test_collects_scaled_full_and_original_image(): void {
		$probe = $this->probe();
		$probe->add_file( self::UPLOADS . '/2026/07/hero-scaled.jpg', 500000, 1783526400, 'image/jpeg', 1200, 800 );
		$probe->add_file( self::UPLOADS . '/2026/07/hero.jpg', 920000, 1783526300, 'image/jpeg', 2400, 1600 );

		$metadata                   = $this->metadata();
		$metadata['file']           = '2026/07/hero-scaled.jpg';
		$metadata['width']          = 1200;
		$metadata['height']         = 800;
		$metadata['original_image'] = 'hero.jpg';
		$provider                   = new FakeAttachmentSourceProvider(
			self::UPLOADS . '/2026/07/hero-scaled.jpg',
			$metadata,
			self::UPLOADS
		);
		$collection                 = ( new SourceCollector( $provider, $probe ) )->collect( 123 );

		self::assertSame( array( 'full', 'original' ), $this->size_names( $collection ) );
		self::assertSame( SourceImage::ROLE_ORIGINAL, $collection->sources()[1]->role() );
		self::assertSame( '2026/07/hero.jpg', $collection->sources()[1]->relative_path() );
		self::assertSame( 2400, $collection->sources()[1]->width() );
		self::assertSame( 1600, $collection->sources()[1]->height() );
	}

	/**
	 * Test duplicate source paths are skipped with an issue.
	 *
	 * @return void
	 */
	public function test_duplicate_paths_are_reported_once(): void {
		$probe = $this->probe();
		$probe->add_file( self::UPLOADS . '/2026/07/hero.jpg', 920000, 1783526400, 'image/jpeg', 2400, 1600 );

		$collection = $this->collector(
			$probe,
			$this->metadata(
				array(
					'duplicate' => array(
						'file'   => 'hero.jpg',
						'width'  => 2400,
						'height' => 1600,
					),
				)
			)
		)->collect( 123 );

		self::assertCount( 1, $collection->sources() );
		self::assertSame( array( SourceImageIssue::CODE_DUPLICATE_SOURCE ), $this->issue_codes( $collection ) );
	}

	/**
	 * Test missing thumbnail does not invalidate full source.
	 *
	 * @return void
	 */
	public function test_missing_thumbnail_does_not_invalidate_whole_attachment(): void {
		$probe = $this->probe();
		$probe->add_file( self::UPLOADS . '/2026/07/hero.jpg', 920000, 1783526400, 'image/jpeg', 2400, 1600 );

		$collection = $this->collector(
			$probe,
			$this->metadata(
				array(
					'thumbnail' => array(
						'file'   => 'hero-150x100.jpg',
						'width'  => 150,
						'height' => 100,
					),
				)
			)
		)->collect( 123 );

		self::assertSame( array( 'full' ), $this->size_names( $collection ) );
		self::assertSame( array( SourceImageIssue::CODE_SOURCE_MISSING ), $this->issue_codes( $collection ) );
		self::assertSame( 'thumbnail', $collection->issues()[0]->size_name() );
	}

	/**
	 * Test malformed metadata records are reported.
	 *
	 * @return void
	 */
	public function test_malformed_metadata_records_are_reported(): void {
		$probe = $this->probe();
		$probe->add_file( self::UPLOADS . '/2026/07/hero.jpg', 920000, 1783526400, 'image/jpeg', 2400, 1600 );
		$probe->add_file( self::UPLOADS . '/2026/07/medium.jpg', 20000, 1783526401, 'image/jpeg', null, null );

		$metadata = $this->metadata(
			array(
				'bad'    => 'not-an-array',
				'medium' => array(
					'file' => 'medium.jpg',
				),
			)
		);

		$collection = $this->collector( $probe, $metadata )->collect( 123 );

		self::assertSame( array( 'full' ), $this->size_names( $collection ) );
		self::assertSame(
			array(
				SourceImageIssue::CODE_MALFORMED_METADATA,
				SourceImageIssue::CODE_MALFORMED_METADATA,
			),
			$this->issue_codes( $collection )
		);
	}

	/**
	 * Test outside uploads path is rejected.
	 *
	 * @return void
	 */
	public function test_files_outside_uploads_are_rejected(): void {
		$probe = $this->probe();
		$probe->add_file( 'D:/outside/hero.jpg', 1000, 100, 'image/jpeg', 100, 100 );

		$collection = $this->collector(
			$probe,
			$this->metadata(),
			'D:/outside/hero.jpg'
		)->collect( 123 );

		self::assertCount( 0, $collection->sources() );
		self::assertSame( array( SourceImageIssue::CODE_OUTSIDE_UPLOADS ), $this->issue_codes( $collection ) );
	}

	/**
	 * Test unsafe metadata paths are rejected before file probing.
	 *
	 * @return void
	 */
	public function test_unsafe_metadata_paths_are_rejected(): void {
		$probe = $this->probe();
		$probe->add_file( self::UPLOADS . '/2026/07/hero.jpg', 920000, 1783526400, 'image/jpeg', 2400, 1600 );

		$collection = $this->collector(
			$probe,
			$this->metadata(
				array(
					'traversal' => array(
						'file'   => '../evil.jpg',
						'width'  => 10,
						'height' => 10,
					),
					'absolute'  => array(
						'file'   => 'C:/outside/evil.jpg',
						'width'  => 10,
						'height' => 10,
					),
					'url'       => array(
						'file'   => 'https://example.com/evil.jpg',
						'width'  => 10,
						'height' => 10,
					),
					'nullbyte'  => array(
						'file'   => "bad\0file.jpg",
						'width'  => 10,
						'height' => 10,
					),
				)
			)
		)->collect( 123 );

		self::assertSame( array( 'full' ), $this->size_names( $collection ) );
		self::assertSame(
			array(
				SourceImageIssue::CODE_UNSAFE_SOURCE_PATH,
				SourceImageIssue::CODE_UNSAFE_SOURCE_PATH,
				SourceImageIssue::CODE_UNSAFE_SOURCE_PATH,
				SourceImageIssue::CODE_UNSAFE_SOURCE_PATH,
			),
			$this->issue_codes( $collection )
		);
	}

	/**
	 * Test realpaths resolving outside uploads are rejected.
	 *
	 * @return void
	 */
	public function test_realpath_outside_uploads_is_rejected(): void {
		$probe = $this->probe();
		$probe->add_file(
			self::UPLOADS . '/2026/07/hero.jpg',
			920000,
			1783526400,
			'image/jpeg',
			2400,
			1600,
			true,
			true,
			'D:/outside/hero.jpg'
		);

		$collection = $this->collector( $probe )->collect( 123 );

		self::assertCount( 0, $collection->sources() );
		self::assertSame( array( SourceImageIssue::CODE_OUTSIDE_UPLOADS ), $this->issue_codes( $collection ) );
	}

	/**
	 * Test unreadable and non-file paths are reported.
	 *
	 * @return void
	 */
	public function test_unreadable_or_non_file_sources_are_reported(): void {
		$probe = $this->probe();
		$probe->add_file( self::UPLOADS . '/2026/07/hero.jpg', 920000, 1783526400, 'image/jpeg', 2400, 1600, false );
		$probe->add_file( self::UPLOADS . '/2026/07/thumb.jpg', 1000, 1783526401, 'image/jpeg', 10, 10, true, false );

		$collection = $this->collector(
			$probe,
			$this->metadata(
				array(
					'thumbnail' => array(
						'file'   => 'thumb.jpg',
						'width'  => 10,
						'height' => 10,
					),
				)
			)
		)->collect( 123 );

		self::assertCount( 0, $collection->sources() );
		self::assertSame(
			array(
				SourceImageIssue::CODE_SOURCE_UNREADABLE,
				SourceImageIssue::CODE_SOURCE_UNREADABLE,
			),
			$this->issue_codes( $collection )
		);
	}

	/**
	 * Test issue serialization does not expose absolute paths.
	 *
	 * @return void
	 */
	public function test_issue_serialization_redacts_absolute_paths(): void {
		$issue = new SourceImageIssue(
			123,
			'full',
			SourceImage::ROLE_FULL,
			SourceImageIssue::CODE_SOURCE_MISSING,
			'Missing C:\site\uploads\hero.jpg.',
			array(
				'path' => 'C:\site\uploads\hero.jpg',
			)
		);

		self::assertStringNotContainsString( 'C:\site', $issue->to_array()['message'] );
		self::assertSame( '[redacted_path]', $issue->to_array()['details']['path'] );
	}

	/**
	 * Build collector.
	 *
	 * @param FakeImageFileProbe       $probe Probe.
	 * @param array<string,mixed>|null $metadata Metadata.
	 * @param string|null              $attached_file Attached file.
	 * @return SourceCollector
	 */
	private function collector(
		FakeImageFileProbe $probe,
		?array $metadata = null,
		?string $attached_file = self::UPLOADS . '/2026/07/hero.jpg'
	): SourceCollector {
		return new SourceCollector(
			new FakeAttachmentSourceProvider( $attached_file, $metadata ?? $this->metadata(), self::UPLOADS ),
			$probe
		);
	}

	/**
	 * Build fake probe.
	 *
	 * @return FakeImageFileProbe
	 */
	private function probe(): FakeImageFileProbe {
		return new FakeImageFileProbe( array( self::UPLOADS ) );
	}

	/**
	 * Build metadata.
	 *
	 * @param array<string,mixed> $sizes Sizes.
	 * @return array<string,mixed>
	 */
	private function metadata( array $sizes = array() ): array {
		return array(
			'file'   => '2026/07/hero.jpg',
			'width'  => 2400,
			'height' => 1600,
			'sizes'  => $sizes,
		);
	}

	/**
	 * Get collection size names.
	 *
	 * @param SourceImageCollection $collection Collection.
	 * @return string[]
	 */
	private function size_names( SourceImageCollection $collection ): array {
		return array_map(
			static function ( SourceImage $source ): string {
				return $source->size_name();
			},
			$collection->sources()
		);
	}

	/**
	 * Get issue codes.
	 *
	 * @param SourceImageCollection $collection Collection.
	 * @return string[]
	 */
	private function issue_codes( SourceImageCollection $collection ): array {
		return array_map(
			static function ( SourceImageIssue $issue ): string {
				return $issue->code();
			},
			$collection->issues()
		);
	}
}
