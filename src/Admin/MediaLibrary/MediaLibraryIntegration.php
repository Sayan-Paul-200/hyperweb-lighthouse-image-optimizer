<?php
/**
 * Media Library integration provider.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\MediaLibrary;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookProviderInterface;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsRepositoryInterface;

/**
 * Adds lightweight Media Library status and action integration.
 */
final class MediaLibraryIntegration implements HookProviderInterface {

	public const PRIORITY   = 10;
	public const COLUMN_KEY = 'hwlio_optimization';

	/**
	 * Settings repository.
	 *
	 * @var SettingsRepositoryInterface
	 */
	private $settings;

	/**
	 * Media runtime adapter.
	 *
	 * @var MediaLibraryRuntimeInterface
	 */
	private $runtime;

	/**
	 * Status reader.
	 *
	 * @var AttachmentStatusReader
	 */
	private $reader;

	/**
	 * Attachment presenter.
	 *
	 * @var MediaAttachmentPresenter
	 */
	private $presenter;

	/**
	 * Markup renderer.
	 *
	 * @var MediaAttachmentRenderer
	 */
	private $renderer;

	/**
	 * Create the provider.
	 *
	 * @param SettingsRepositoryInterface  $settings Settings repository.
	 * @param MediaLibraryRuntimeInterface $runtime Media runtime.
	 * @param AttachmentStatusReader       $reader Status reader.
	 * @param MediaAttachmentPresenter     $presenter Attachment presenter.
	 * @param MediaAttachmentRenderer      $renderer Markup renderer.
	 */
	public function __construct(
		SettingsRepositoryInterface $settings,
		MediaLibraryRuntimeInterface $runtime,
		AttachmentStatusReader $reader,
		MediaAttachmentPresenter $presenter,
		MediaAttachmentRenderer $renderer
	) {
		$this->settings  = $settings;
		$this->runtime   = $runtime;
		$this->reader    = $reader;
		$this->presenter = $presenter;
		$this->renderer  = $renderer;
	}

	/**
	 * Register only the Media Library hooks needed by Subphase 6.4.
	 *
	 * @param HookRegistrar $hooks Hook registrar.
	 * @return void
	 */
	public function register_hooks( HookRegistrar $hooks ): void {
		$hooks->add_filter( 'wp_prepare_attachment_for_js', array( $this, 'prepare_attachment_for_js' ), self::PRIORITY, 3 );
		$hooks->add_filter( 'manage_media_columns', array( $this, 'add_media_column' ), self::PRIORITY, 1 );
		$hooks->add_action( 'manage_media_custom_column', array( $this, 'render_media_column' ), self::PRIORITY, 2 );
		$hooks->add_filter( 'media_row_actions', array( $this, 'filter_row_actions' ), self::PRIORITY, 3 );
		$hooks->add_filter( 'attachment_fields_to_edit', array( $this, 'attachment_fields_to_edit' ), self::PRIORITY, 2 );
	}

	/**
	 * Add the lightweight optimization column.
	 *
	 * @param array<string,string> $columns Existing columns.
	 * @return array<string,string>
	 */
	public function add_media_column( array $columns ): array {
		if ( ! $this->settings->media_library_controls_enabled() ) {
			return $columns;
		}

		$columns[ self::COLUMN_KEY ] = $this->translate( 'Optimization' );

		return $columns;
	}

	/**
	 * Render the list-table optimization column.
	 *
	 * @param string $column_name Column name.
	 * @param int    $attachment_id Attachment ID.
	 * @return void
	 */
	public function render_media_column( string $column_name, int $attachment_id ): void {
		if ( self::COLUMN_KEY !== $column_name ) {
			return;
		}

		$summary = $this->summary_for( $attachment_id );

		if ( null === $summary ) {
			echo $this->renderer->render_unavailable(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer output is escaped.
			return;
		}

		echo $this->renderer->render_column( $summary ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer output is escaped.
	}

	/**
	 * Add quick row actions.
	 *
	 * @param array<string,string> $actions Existing row actions.
	 * @param mixed                $post Attachment post object.
	 * @param mixed                $detached Detached flag.
	 * @return array<string,string>
	 */
	public function filter_row_actions( array $actions, $post, $detached = null ): array {
		unset( $detached );

		if ( ! is_object( $post ) || ! isset( $post->ID ) || ! is_numeric( $post->ID ) ) {
			return $actions;
		}

		$summary = $this->summary_for( (int) $post->ID );

		if ( null === $summary ) {
			return $actions;
		}

		return array_merge( $actions, $this->renderer->render_row_actions( $summary ) );
	}

	/**
	 * Add the lightweight attachment field/compat section.
	 *
	 * @param array<string,array<string,mixed>> $form_fields Existing fields.
	 * @param mixed                             $post Attachment post object.
	 * @return array<string,array<string,mixed>>
	 */
	public function attachment_fields_to_edit( array $form_fields, $post ): array {
		if ( ! is_object( $post ) || ! isset( $post->ID ) || ! is_numeric( $post->ID ) ) {
			return $form_fields;
		}

		$summary = $this->summary_for( (int) $post->ID );

		if ( null === $summary ) {
			return $form_fields;
		}

		$form_fields['hwlio'] = array(
			'label'        => $this->translate( 'Lighthouse Image Optimizer' ),
			'input'        => 'html',
			'html'         => $this->renderer->render_field( $summary ),
			'show_in_edit' => true,
		);

		return $form_fields;
	}

	/**
	 * Inject lightweight attachment summary data into core media payloads.
	 *
	 * @param array<string,mixed> $response Existing attachment payload.
	 * @param mixed               $attachment Attachment post object.
	 * @param mixed               $meta Attachment metadata.
	 * @return array<string,mixed>
	 */
	public function prepare_attachment_for_js( array $response, $attachment, $meta ): array {
		unset( $meta );

		if ( ! is_object( $attachment ) || ! isset( $attachment->ID ) || ! is_numeric( $attachment->ID ) ) {
			return $response;
		}

		$summary = $this->summary_for( (int) $attachment->ID );

		if ( null === $summary ) {
			return $response;
		}

		$response['hwlio'] = $summary->to_array();

		return $response;
	}

	/**
	 * Build one lightweight attachment summary when the attachment is supported.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return MediaAttachmentSummary|null
	 */
	private function summary_for( int $attachment_id ): ?MediaAttachmentSummary {
		$attachment_id = max( 0, $attachment_id );

		if ( 0 === $attachment_id || ! $this->settings->media_library_controls_enabled() ) {
			return null;
		}

		if ( ! $this->runtime->attachment_exists( $attachment_id ) || ! $this->runtime->attachment_is_image( $attachment_id ) ) {
			return null;
		}

		$can_manage = $this->runtime->current_user_can( 'upload_files' ) && $this->runtime->current_user_can( 'edit_post', $attachment_id );

		if ( ! $can_manage ) {
			return null;
		}

		return $this->presenter->present(
			$attachment_id,
			$this->reader->read( $attachment_id ),
			$can_manage,
			$this->settings->attachment_exclusion_allowed()
		);
	}

	/**
	 * Translate one plugin-owned string.
	 *
	 * @param string $text Raw text.
	 * @return string
	 */
	private function translate( string $text ): string {
		if ( function_exists( '__' ) ) {
			// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- Wrapper accepts only plugin-owned literals provided by calling code.
			return __( $text, 'hyperweb-lighthouse-image-optimizer' );
		}

		return $text;
	}
}
