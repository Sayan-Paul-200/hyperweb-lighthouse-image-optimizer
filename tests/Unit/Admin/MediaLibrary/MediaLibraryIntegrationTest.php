<?php
/**
 * Tests for the Media Library integration provider.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Admin\MediaLibrary;

use HyperWeb\LighthouseImageOptimizer\Admin\MediaLibrary\AttachmentActionAvailability;
use HyperWeb\LighthouseImageOptimizer\Admin\MediaLibrary\AttachmentStatusReader;
use HyperWeb\LighthouseImageOptimizer\Admin\MediaLibrary\MediaAttachmentPresenter;
use HyperWeb\LighthouseImageOptimizer\Admin\MediaLibrary\MediaAttachmentRenderer;
use HyperWeb\LighthouseImageOptimizer\Admin\MediaLibrary\MediaLibraryIntegration;
use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentStatus;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment\FakeAttachmentMetaStore;
use HyperWeb\LighthouseImageOptimizer\Tests\Unit\Image\FakeSettingsRepository;
use PHPUnit\Framework\TestCase;

/**
 * Verifies lightweight Media Library payloads and markup.
 */
final class MediaLibraryIntegrationTest extends TestCase {

	/**
	 * Test hook registration stays limited to the 6.4 Media Library hooks.
	 *
	 * @return void
	 */
	public function test_register_hooks_adds_only_media_library_hooks(): void {
		$hooks       = new HookRegistrar();
		$integration = $this->provider()['integration'];

		$integration->register_hooks( $hooks );

		self::assertSame(
			array(
				'wp_prepare_attachment_for_js',
				'manage_media_columns',
				'media_row_actions',
				'attachment_fields_to_edit',
			),
			array_map(
				static function ( array $filter ): string {
					return $filter['hook'];
				},
				$hooks->filters()
			)
		);
		self::assertSame( 'manage_media_custom_column', $hooks->actions()[0]['hook'] );
	}

	/**
	 * Test valid image attachments receive a lightweight payload only.
	 *
	 * @return void
	 */
	public function test_prepare_attachment_for_js_injects_lightweight_summary_payload(): void {
		$fixture = $this->provider();
		$fixture['store']->meta[123][ LifecyclePolicy::META_STATUS ] = array(
			'state'      => AttachmentStatus::STATE_QUEUED,
			'formats'    => array( 'webp' ),
			'updated_at' => 1783612800,
			'error_code' => null,
			'excluded'   => false,
		);

		$response = $fixture['integration']->prepare_attachment_for_js(
			array( 'id' => 123 ),
			(object) array( 'ID' => 123 ),
			array()
		);

		self::assertArrayHasKey( 'hwlio', $response );
		self::assertSame( 'queued', $response['hwlio']['state'] );
		self::assertSame( array( 'exclude', 'view-details' ), $response['hwlio']['allowedActions'] );
		self::assertArrayNotHasKey( 'manifest', $response['hwlio'] );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Test assertion for serialized payload safety.
		$json = json_encode( $response['hwlio'] );
		self::assertStringNotContainsString( 'C:/', is_string( $json ) ? $json : '' );
	}

	/**
	 * Test unauthorized attachments do not receive plugin payloads.
	 *
	 * @return void
	 */
	public function test_prepare_attachment_for_js_skips_unauthorized_attachments(): void {
		$fixture = $this->provider();
		$fixture['runtime']->capabilities['edit_post:123'] = false;

		$response = $fixture['integration']->prepare_attachment_for_js(
			array( 'id' => 123 ),
			(object) array( 'ID' => 123 ),
			array()
		);

		self::assertArrayNotHasKey( 'hwlio', $response );
	}

	/**
	 * Test list view rendering shows the expected status badge and format chip.
	 *
	 * @return void
	 */
	public function test_render_media_column_outputs_lightweight_status_markup(): void {
		$fixture = $this->provider();
		$fixture['store']->meta[123][ LifecyclePolicy::META_STATUS ] = array(
			'state'      => AttachmentStatus::STATE_OPTIMIZED,
			'formats'    => array( 'webp' ),
			'updated_at' => 1783612800,
			'error_code' => null,
			'excluded'   => false,
		);

		ob_start();
		$fixture['integration']->render_media_column( MediaLibraryIntegration::COLUMN_KEY, 123 );
		$output = (string) ob_get_clean();

		self::assertStringContainsString( 'hwlio-media-summary', $output );
		self::assertStringContainsString( 'hwlio-media-badge--optimized', $output );
		self::assertStringContainsString( 'WEBP', $output );
	}

	/**
	 * Test attachment edit/media compat fields receive the HWLIO block.
	 *
	 * @return void
	 */
	public function test_attachment_fields_to_edit_adds_hwlio_section(): void {
		$fixture = $this->provider();

		$fields = $fixture['integration']->attachment_fields_to_edit(
			array(),
			(object) array( 'ID' => 123 )
		);

		self::assertArrayHasKey( 'hwlio', $fields );
		self::assertSame( 'html', $fields['hwlio']['input'] );
		self::assertStringContainsString( 'data-attachment-id="123"', $fields['hwlio']['html'] );
		self::assertStringContainsString( 'hwlio-media-summary__details', $fields['hwlio']['html'] );
	}

	/**
	 * Test row actions follow the centralized state policy.
	 *
	 * @return void
	 */
	public function test_filter_row_actions_uses_state_specific_action_mapping(): void {
		$fixture = $this->provider();
		$fixture['store']->meta[123][ LifecyclePolicy::META_STATUS ] = array(
			'state'      => AttachmentStatus::STATE_STALE,
			'formats'    => array(),
			'updated_at' => 1783612800,
			'error_code' => null,
			'excluded'   => false,
		);

		$actions = $fixture['integration']->filter_row_actions(
			array( 'edit' => '<a>Edit</a>' ),
			(object) array( 'ID' => 123 )
		);

		self::assertArrayHasKey( 'hwlio-retry', $actions );
		self::assertArrayHasKey( 'hwlio-reoptimize', $actions );
		self::assertArrayHasKey( 'hwlio-reconcile', $actions );
		self::assertArrayHasKey( 'hwlio-exclude', $actions );
	}

	/**
	 * Test exclusion-disabled settings remove exclude/include controls.
	 *
	 * @return void
	 */
	public function test_summary_payload_respects_exclusion_disabled_settings(): void {
		$fixture = $this->provider(
			array(
				'allow_attachment_exclusion' => false,
			)
		);

		$response = $fixture['integration']->prepare_attachment_for_js(
			array( 'id' => 123 ),
			(object) array( 'ID' => 123 ),
			array()
		);

		self::assertSame(
			array( 'optimize', 'view-details' ),
			$response['hwlio']['allowedActions']
		);
	}

	/**
	 * Build the provider fixture.
	 *
	 * @param array<string,mixed> $settings_overrides Settings overrides.
	 * @return array<string,mixed>
	 */
	private function provider( array $settings_overrides = array() ): array {
		$store       = new FakeAttachmentMetaStore();
		$runtime     = new FakeMediaLibraryRuntime();
		$settings    = new FakeSettingsRepository( $settings_overrides );
		$integration = new MediaLibraryIntegration(
			$settings,
			$runtime,
			new AttachmentStatusReader( $store ),
			new MediaAttachmentPresenter( new AttachmentActionAvailability() ),
			new MediaAttachmentRenderer()
		);

		return array(
			'integration' => $integration,
			'runtime'     => $runtime,
			'store'       => $store,
		);
	}
}
