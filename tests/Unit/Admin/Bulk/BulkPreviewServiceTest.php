<?php
/**
 * Tests for the bulk preview service.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Admin\Bulk;

use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkPreviewService;
use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkScanFilters;
use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkScanSession;
use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkScanSessionAccessDeniedException;
use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\BulkScanSessionNotFoundException;
use HyperWeb\LighthouseImageOptimizer\Admin\Bulk\WordPressTransientBulkScanSessionStore;
use HyperWeb\LighthouseImageOptimizer\Admin\MediaLibrary\AttachmentActionAvailability;
use HyperWeb\LighthouseImageOptimizer\Admin\MediaLibrary\AttachmentStatusReader;
use HyperWeb\LighthouseImageOptimizer\Admin\MediaLibrary\MediaAttachmentPresenter;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment\FakeAttachmentMetaStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeSettingsRepository;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Infrastructure\FakeTransientStore;
use PHPUnit\Framework\TestCase;

/**
 * Verifies session-scoped preview payloads stay lightweight and owned.
 */
final class BulkPreviewServiceTest extends TestCase {

	/**
	 * Test preview returns lightweight normalized rows only.
	 *
	 * @return void
	 */
	public function test_preview_returns_lightweight_rows_only(): void {
		$runtime            = new FakeBulkScannerRuntime();
		$runtime->preview[44] = array(
			'attachment_id'   => 44,
			'title'           => 'Hero Image',
			'filename'        => 'hero.jpg',
			'uploaded_at_gmt' => '2026-07-11 12:00:00',
		);
		$store             = new FakeAttachmentMetaStore();
		$store->meta[44][ LifecyclePolicy::META_STATUS ] = array(
			'state'   => 'tampered-state',
			'formats' => array( 'webp' ),
		);
		$sessions          = new WordPressTransientBulkScanSessionStore( new FakeTransientStore() );
		$session           = BulkScanSession::start(
			'cafebabecafebabecafebabecafebabe',
			7,
			'2026-07-12 00:00:00',
			new BulkScanFilters()
		);
		$session           = $sessions->append_candidate_ids( $session, array( 44 ) );
		$sessions->save( $session );
		$service = new BulkPreviewService(
			$sessions,
			$runtime,
			new AttachmentStatusReader( $store ),
			new MediaAttachmentPresenter( new AttachmentActionAvailability() ),
			new FakeSettingsRepository()
		);

		$page = $service->preview( $session->token(), 7, 1, 20 )->to_array();

		self::assertSame( 1, $page['totalItems'] );
		self::assertSame( 'Hero Image', $page['items'][0]['title'] );
		self::assertSame( 'hero.jpg', $page['items'][0]['filename'] );
		self::assertSame( 'unprocessed', $page['items'][0]['state'] );
		self::assertStringNotContainsString( 'C:/', json_encode( $page ) ?: '' );
		self::assertArrayNotHasKey( 'manifest', $page['items'][0] );
	}

	/**
	 * Test preview rejects missing and foreign sessions.
	 *
	 * @return void
	 */
	public function test_preview_rejects_missing_or_foreign_sessions(): void {
		$sessions = new WordPressTransientBulkScanSessionStore( new FakeTransientStore() );
		$service  = new BulkPreviewService(
			$sessions,
			new FakeBulkScannerRuntime(),
			new AttachmentStatusReader( new FakeAttachmentMetaStore() ),
			new MediaAttachmentPresenter( new AttachmentActionAvailability() ),
			new FakeSettingsRepository()
		);

		$this->expectException( BulkScanSessionNotFoundException::class );
		$service->preview( 'missingtoken', 7, 1, 20 );
	}

	/**
	 * Test preview rejects foreign owners.
	 *
	 * @return void
	 */
	public function test_preview_rejects_foreign_owner(): void {
		$sessions = new WordPressTransientBulkScanSessionStore( new FakeTransientStore() );
		$session  = BulkScanSession::start(
			'11223344556677881122334455667788',
			7,
			'2026-07-12 00:00:00',
			new BulkScanFilters()
		);
		$sessions->save( $session );
		$service  = new BulkPreviewService(
			$sessions,
			new FakeBulkScannerRuntime(),
			new AttachmentStatusReader( new FakeAttachmentMetaStore() ),
			new MediaAttachmentPresenter( new AttachmentActionAvailability() ),
			new FakeSettingsRepository()
		);

		$this->expectException( BulkScanSessionAccessDeniedException::class );
		$service->preview( $session->token(), 9, 1, 20 );
	}
}
