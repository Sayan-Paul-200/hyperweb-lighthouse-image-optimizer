<?php
/**
 * Elementor hero background post editor meta box.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\PostEditor;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookProviderInterface;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorBackgroundDiscovery;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorBackgroundSource;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorHeroBackgroundPostMetaStoreInterface;
use HyperWeb\LighthouseImageOptimizer\Integration\ElementorHeroBackgroundTargetSelection;
use HyperWeb\LighthouseImageOptimizer\Integration\WordPressElementorDocumentDataStore;
use HyperWeb\LighthouseImageOptimizer\Integration\WordPressElementorHeroBackgroundPostMetaStore;

/**
 * Registers and saves the per-post Elementor hero-background selector.
 */
final class ElementorHeroBackgroundMetaBox implements HookProviderInterface {

	/**
	 * Meta box ID.
	 *
	 * @var string
	 */
	public const META_BOX_ID = 'hwlio-elementor-hero-background';

	/**
	 * Nonce field name.
	 *
	 * @var string
	 */
	public const NONCE_FIELD = '_hwlio_elementor_hero_background_nonce';

	/**
	 * Nonce action.
	 *
	 * @var string
	 */
	public const NONCE_ACTION = 'hwlio_save_elementor_hero_background';

	/**
	 * Field name.
	 *
	 * @var string
	 */
	public const FIELD_NAME = 'hwlio_elementor_hero_background_target';

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
	 * Stored selection seam.
	 *
	 * @var ElementorHeroBackgroundPostMetaStoreInterface
	 */
	private $store;

	/**
	 * Discovery service.
	 *
	 * @var ElementorBackgroundDiscovery
	 */
	private $discovery;

	/**
	 * Create provider.
	 *
	 * @param PostEditorRuntimeInterface                    $runtime Runtime seam.
	 * @param ElementorHeroBackgroundPostMetaStoreInterface $store Stored selections.
	 * @param ElementorBackgroundDiscovery                  $discovery Discovery service.
	 */
	public function __construct(
		PostEditorRuntimeInterface $runtime,
		ElementorHeroBackgroundPostMetaStoreInterface $store,
		ElementorBackgroundDiscovery $discovery
	) {
		$this->runtime   = $runtime;
		$this->store     = $store;
		$this->discovery = $discovery;
	}

	/**
	 * Build a WordPress-backed provider.
	 *
	 * @return self
	 */
	public static function for_wordpress(): self {
		return new self(
			new WordPressPostEditorRuntime(),
			new WordPressElementorHeroBackgroundPostMetaStore(),
			new ElementorBackgroundDiscovery( new WordPressElementorDocumentDataStore() )
		);
	}

	/**
	 * Register hooks.
	 *
	 * @param HookRegistrar $hooks Hook registrar.
	 * @return void
	 */
	public function register_hooks( HookRegistrar $hooks ): void {
		$hooks->add_action( 'add_meta_boxes', array( $this, 'register_meta_boxes' ), 10, 2 );
		$hooks->add_action( 'save_post', array( $this, 'save_post' ), 10, 3 );
	}

	/**
	 * Register the meta box on supported post types.
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
			function_exists( '__' ) ? __( 'Hero background preload', 'hyperweb-lighthouse-image-optimizer' ) : 'Hero background preload',
			array( $this, 'render_meta_box' ),
			$post_type,
			'side',
			'default'
		);
	}

	/**
	 * Render the selector.
	 *
	 * @param object $post Current post object.
	 * @return void
	 */
	public function render_meta_box( $post ): void {
		$post_id     = is_object( $post ) && isset( $post->ID ) ? max( 0, (int) $post->ID ) : 0;
		$nonce       = $this->runtime->create_nonce( self::NONCE_ACTION );
		$options     = $this->available_options( $post_id );
		$selection   = $this->store->get_selection( $post_id );
		$selected    = $selection instanceof ElementorHeroBackgroundTargetSelection && isset( $options[ $selection->key() ] )
			? $selection->encoded_value()
			: '';
		$has_options = array() !== $options;

		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped through local helpers.
		echo '<div class="hwlio-elementor-hero-background-box">';
		echo '<input type="hidden" name="' . $this->escape_attr( self::NONCE_FIELD ) . '" value="' . $this->escape_attr( $nonce ) . '">';
		echo '<p>' . $this->escape_html( function_exists( '__' ) ? __( 'Choose one supported Elementor background target for optional critical-background preload.', 'hyperweb-lighthouse-image-optimizer' ) : 'Choose one supported Elementor background target for optional critical-background preload.' ) . '</p>';
		echo '<p>';
		echo '<label for="' . $this->escape_attr( self::FIELD_NAME ) . '">' . $this->escape_html( function_exists( '__' ) ? __( 'Selected background target', 'hyperweb-lighthouse-image-optimizer' ) : 'Selected background target' ) . '</label><br>';
		echo '<select name="' . $this->escape_attr( self::FIELD_NAME ) . '" id="' . $this->escape_attr( self::FIELD_NAME ) . '"' . ( $has_options ? '' : ' disabled' ) . '>';
		echo '<option value="">' . $this->escape_html( function_exists( '__' ) ? __( 'No hero background selected', 'hyperweb-lighthouse-image-optimizer' ) : 'No hero background selected' ) . '</option>';

		foreach ( $options as $value => $label ) {
			echo '<option value="' . $this->escape_attr( $value ) . '"' . ( $selected === $value ? ' selected' : '' ) . '>' . $this->escape_html( $label ) . '</option>';
		}

		echo '</select>';
		echo '</p>';

		if ( ! $has_options ) {
			echo '<p>' . $this->escape_html( function_exists( '__' ) ? __( 'No supported Elementor background targets were detected for this document.', 'hyperweb-lighthouse-image-optimizer' ) : 'No supported Elementor background targets were detected for this document.' ) . '</p>';
		}

		echo '</div>';
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Save the selected target.
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

		$selection = $this->posted_selection();
		$options   = $this->available_options( $post_id );

		if ( ! $selection instanceof ElementorHeroBackgroundTargetSelection || ! isset( $options[ $selection->key() ] ) ) {
			$this->store->delete_selection( $post_id );
			return;
		}

		$this->store->update_selection( $post_id, $selection );
	}

	/**
	 * Read the posted selection.
	 *
	 * @return ElementorHeroBackgroundTargetSelection|null
	 */
	private function posted_selection(): ?ElementorHeroBackgroundTargetSelection {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Save handler validates the nonce before persisting.
		if ( ! isset( $_POST[ self::FIELD_NAME ] ) || ! is_scalar( $_POST[ self::FIELD_NAME ] ) ) {
			return null;
		}

		$value = $this->unslash( $_POST[ self::FIELD_NAME ] );
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		return is_scalar( $value )
			? ElementorHeroBackgroundTargetSelection::from_encoded_value( (string) $value )
			: null;
	}

	/**
	 * Build currently available selection options.
	 *
	 * @param int $post_id Post ID.
	 * @return array<string,string>
	 */
	private function available_options( int $post_id ): array {
		$options = array();

		foreach ( $this->discovery->discover( $post_id )->supported_sources() as $source ) {
			$data = $source->to_array();

			$selection = ElementorHeroBackgroundTargetSelection::from_array(
				array(
					'element_id'    => isset( $data['element_id'] ) ? $data['element_id'] : '',
					'setting_group' => isset( $data['setting_group'] ) ? $data['setting_group'] : '',
				)
			);

			if ( ! $selection instanceof ElementorHeroBackgroundTargetSelection ) {
				continue;
			}

			$key = $selection->key();

			if ( isset( $options[ $key ] ) ) {
				continue;
			}

			$options[ $key ] = $this->option_label( $source );
		}

		return $options;
	}

	/**
	 * Build one human-readable option label.
	 *
	 * @param ElementorBackgroundSource $source Supported source.
	 * @return string
	 */
	private function option_label( ElementorBackgroundSource $source ): string {
		$data       = $source->to_array();
		$element_id = isset( $data['element_id'] ) ? trim( (string) $data['element_id'] ) : '';
		$group      = isset( $data['setting_group'] ) && 'background_overlay' === $data['setting_group']
			? ( function_exists( '__' ) ? __( 'Background overlay', 'hyperweb-lighthouse-image-optimizer' ) : 'Background overlay' )
			: ( function_exists( '__' ) ? __( 'Background', 'hyperweb-lighthouse-image-optimizer' ) : 'Background' );

		return $element_id . ' (' . $group . ')';
	}

	/**
	 * Unslash one posted value when available.
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
	 * Escape one attribute.
	 *
	 * @param string $value Value.
	 * @return string
	 */
	private function escape_attr( string $value ): string {
		if ( function_exists( 'esc_attr' ) ) {
			return esc_attr( $value );
		}

		return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * Escape one text node.
	 *
	 * @param string $value Value.
	 * @return string
	 */
	private function escape_html( string $value ): string {
		if ( function_exists( 'esc_html' ) ) {
			return esc_html( $value );
		}

		return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
	}
}
