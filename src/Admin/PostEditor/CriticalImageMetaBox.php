<?php
/**
 * Critical image post editor meta box.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\PostEditor;

use HyperWeb\LighthouseImageOptimizer\Delivery\CriticalImagePostMetaStoreInterface;
use HyperWeb\LighthouseImageOptimizer\Delivery\WordPressCriticalImagePostMetaStore;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookProviderInterface;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;

/**
 * Registers and saves the per-post critical-image side meta box.
 */
final class CriticalImageMetaBox implements HookProviderInterface {

	/**
	 * Meta box ID.
	 *
	 * @var string
	 */
	public const META_BOX_ID = 'hwlio-critical-image';

	/**
	 * Nonce field name.
	 *
	 * @var string
	 */
	public const NONCE_FIELD = '_hwlio_critical_image_nonce';

	/**
	 * Nonce action.
	 *
	 * @var string
	 */
	public const NONCE_ACTION = 'hwlio_save_critical_image';

	/**
	 * Hidden field name.
	 *
	 * @var string
	 */
	public const FIELD_NAME = 'hwlio_critical_image_id';

	/**
	 * Supported post types.
	 *
	 * @var string[]
	 */
	private const SUPPORTED_POST_TYPES = array( 'post', 'page' );

	/**
	 * Runtime seam.
	 *
	 * @var PostEditorRuntimeInterface
	 */
	private $runtime;

	/**
	 * Meta store.
	 *
	 * @var CriticalImagePostMetaStoreInterface
	 */
	private $meta;

	/**
	 * Create provider.
	 *
	 * @param PostEditorRuntimeInterface          $runtime Runtime seam.
	 * @param CriticalImagePostMetaStoreInterface $meta Meta store.
	 */
	public function __construct( PostEditorRuntimeInterface $runtime, CriticalImagePostMetaStoreInterface $meta ) {
		$this->runtime = $runtime;
		$this->meta    = $meta;
	}

	/**
	 * Build the WordPress-backed provider.
	 *
	 * @return self
	 */
	public static function for_wordpress(): self {
		return new self( new WordPressPostEditorRuntime(), new WordPressCriticalImagePostMetaStore() );
	}

	/**
	 * Register post editor hooks.
	 *
	 * @param HookRegistrar $hooks Hook registrar.
	 * @return void
	 */
	public function register_hooks( HookRegistrar $hooks ): void {
		$hooks->add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ), 10, 2 );
		$hooks->add_action( 'save_post', array( $this, 'save_post' ), 10, 3 );
	}

	/**
	 * Register the side meta box on supported post types only.
	 *
	 * @param string $post_type Current post type.
	 * @param mixed  $post Current post object.
	 * @return void
	 */
	public function register_meta_boxes( string $post_type, $post ): void {
		unset( $post );

		if ( ! in_array( $post_type, self::SUPPORTED_POST_TYPES, true ) ) {
			return;
		}

		$this->runtime->add_meta_box(
			self::META_BOX_ID,
			$this->translate( 'Critical image' ),
			array( $this, 'render_meta_box' ),
			$post_type,
			'side',
			'default'
		);
	}

	/**
	 * Render the critical-image side meta box.
	 *
	 * @param object $post Current post object.
	 * @return void
	 */
	public function render_meta_box( $post ): void {
		$post_id       = is_object( $post ) && isset( $post->ID ) ? max( 0, (int) $post->ID ) : 0;
		$attachment_id = $this->meta->get_critical_image_id( $post_id );
		$title         = $attachment_id > 0 ? $this->runtime->attachment_title( $attachment_id ) : '';
		$preview_url   = $attachment_id > 0 ? $this->runtime->attachment_preview_url( $attachment_id ) : '';
		$nonce         = $this->runtime->create_nonce( self::NONCE_ACTION );

		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped through local helpers.
		echo '<div class="hwlio-critical-image-box" data-hwlio-critical-image-box="1">';
		echo '<input type="hidden" name="' . $this->escape_attr( self::NONCE_FIELD ) . '" value="' . $this->escape_attr( $nonce ) . '">';
		echo '<input type="hidden" name="' . $this->escape_attr( self::FIELD_NAME ) . '" value="' . $this->escape_attr( (string) $attachment_id ) . '" data-hwlio-critical-image-input="1">';
		echo '<div class="hwlio-critical-image-summary" data-hwlio-critical-image-summary="1">';
		echo '<p data-hwlio-critical-image-preview="1"' . ( '' !== $preview_url ? '' : ' hidden' ) . '>';

		if ( '' !== $preview_url ) {
			echo '<img src="' . $this->escape_attr( $preview_url ) . '" alt="" style="max-width:100%;height:auto;">';
		}

		echo '</p>';
		echo '<p data-hwlio-critical-image-title="1">' . $this->escape_html(
			'' !== $title ? $title : $this->translate( 'No critical image selected.' )
		) . '</p>';
		echo '</div>';
		echo '<p class="hwlio-critical-image-actions">';
		echo '<button type="button" class="button button-secondary" data-hwlio-critical-image-action="select">' . $this->escape_html(
			$attachment_id > 0 ? $this->translate( 'Replace image' ) : $this->translate( 'Select image' )
		) . '</button> ';
		echo '<button type="button" class="button-link-delete" data-hwlio-critical-image-action="clear"' . ( $attachment_id > 0 ? '' : ' hidden' ) . '>' . $this->escape_html( $this->translate( 'Clear' ) ) . '</button>';
		echo '</p>';
		echo '</div>';
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Save the selected critical-image attachment ID.
	 *
	 * @param int   $post_id Post ID.
	 * @param mixed $post Post object.
	 * @param bool  $update Whether this is an existing post update.
	 * @return void
	 */
	public function save_post( int $post_id, $post, bool $update ): void {
		unset( $update );

		$post_type = is_object( $post ) && isset( $post->post_type ) && is_string( $post->post_type )
			? $post->post_type
			: '';

		if ( ! in_array( $post_type, self::SUPPORTED_POST_TYPES, true ) ) {
			return;
		}

		if ( $this->runtime->is_autosave( $post_id ) || $this->runtime->is_revision( $post_id ) ) {
			return;
		}

		if ( ! $this->runtime->current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Save handler validates the nonce explicitly.
		$nonce = isset( $_POST[ self::NONCE_FIELD ] ) && is_string( $_POST[ self::NONCE_FIELD ] )
			? $this->unslash( $_POST[ self::NONCE_FIELD ] )
			: '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( '' === $nonce || ! $this->runtime->verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return;
		}

		$attachment_id = $this->posted_attachment_id();

		if ( $attachment_id < 1 || ! $this->runtime->attachment_is_image( $attachment_id ) ) {
			$this->meta->delete_critical_image_id( $post_id );
			return;
		}

		$this->meta->update_critical_image_id( $post_id, $attachment_id );
	}

	/**
	 * Read the submitted attachment ID from the request.
	 *
	 * @return int
	 */
	private function posted_attachment_id(): int {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Save handler validates the nonce before persisting.
		if ( ! isset( $_POST[ self::FIELD_NAME ] ) ) {
			return 0;
		}

		$value = $this->unslash( $_POST[ self::FIELD_NAME ] );
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		return is_scalar( $value ) ? max( 0, (int) $value ) : 0;
	}

	/**
	 * Unslash one posted value when WordPress provides the helper.
	 *
	 * @param mixed $value Posted value.
	 * @return mixed
	 */
	private function unslash( $value ) {
		if ( function_exists( 'wp_unslash' ) ) {
			return wp_unslash( $value );
		}

		return $value;
	}

	/**
	 * Escape one HTML attribute.
	 *
	 * @param string $value Attribute value.
	 * @return string
	 */
	private function escape_attr( string $value ): string {
		if ( function_exists( 'esc_attr' ) ) {
			return esc_attr( $value );
		}

		return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * Escape one HTML text node.
	 *
	 * @param string $value Text value.
	 * @return string
	 */
	private function escape_html( string $value ): string {
		if ( function_exists( 'esc_html' ) ) {
			return esc_html( $value );
		}

		return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
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
