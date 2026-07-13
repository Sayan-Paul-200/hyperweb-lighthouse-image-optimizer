<?php
// phpcs:ignoreFile Universal.Files.SeparateFunctionsFromOO.Mixed, Generic.Files.OneObjectStructurePerFile.MultipleFound -- Test shim intentionally mixes procedural functions and a lightweight class.
/**
 * WordPress function shims for delivery tests.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

require_once dirname( __DIR__ ) . '/Settings/SettingsTestFilterShim.php';

if ( ! function_exists( 'wp_upload_dir' ) ) {
	/**
	 * Minimal test shim for uploads data.
	 *
	 * @param string|null $time Time string.
	 * @param bool        $create_dir Whether directories should be created.
	 * @return mixed
	 */
	function wp_upload_dir( $time = null, $create_dir = true ) {
		unset( $time, $create_dir );

		return $GLOBALS['hwlio_test_wp_upload_dir'] ?? null;
	}
}

if ( ! function_exists( 'is_singular' ) ) {
	/**
	 * Minimal singular-request shim for delivery runtime tests.
	 *
	 * @return bool
	 */
	function is_singular(): bool {
		return ! empty( $GLOBALS['hwlio_test_is_singular'] );
	}
}

if ( ! function_exists( 'get_queried_object_id' ) ) {
	/**
	 * Minimal queried object ID shim for delivery runtime tests.
	 *
	 * @return int
	 */
	function get_queried_object_id(): int {
		return isset( $GLOBALS['hwlio_test_queried_object_id'] ) ? (int) $GLOBALS['hwlio_test_queried_object_id'] : 0;
	}
}

if ( ! function_exists( 'get_post_type' ) ) {
	/**
	 * Minimal post type shim for delivery runtime tests.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	function get_post_type( int $post_id ): string {
		return isset( $GLOBALS['hwlio_test_post_types'][ $post_id ] ) ? (string) $GLOBALS['hwlio_test_post_types'][ $post_id ] : '';
	}
}

if ( ! function_exists( 'get_post_field' ) ) {
	/**
	 * Minimal post field shim for delivery runtime tests.
	 *
	 * @param string $field Field name.
	 * @param int    $post_id Post ID.
	 * @return string
	 */
	function get_post_field( string $field, int $post_id ): string {
		if ( 'post_content' === $field && isset( $GLOBALS['hwlio_test_post_content'][ $post_id ] ) ) {
			return (string) $GLOBALS['hwlio_test_post_content'][ $post_id ];
		}

		return '';
	}
}

if ( ! function_exists( 'current_theme_supports' ) ) {
	/**
	 * Minimal theme-support shim for delivery runtime tests.
	 *
	 * @param string $feature Feature name.
	 * @return bool
	 */
	function current_theme_supports( string $feature ): bool {
		return 'custom-logo' === $feature ? ! empty( $GLOBALS['hwlio_test_theme_supports_custom_logo'] ) : false;
	}
}

if ( ! function_exists( 'get_theme_mod' ) ) {
	/**
	 * Minimal theme-mod shim for delivery runtime tests.
	 *
	 * @param string $name Theme mod name.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	function get_theme_mod( string $name, $default = false ) {
		if ( 'custom_logo' === $name && isset( $GLOBALS['hwlio_test_custom_logo_attachment_id'] ) ) {
			return $GLOBALS['hwlio_test_custom_logo_attachment_id'];
		}

		return $default;
	}
}

if ( ! function_exists( 'is_product' ) ) {
	/**
	 * Minimal WooCommerce single-product shim for integration tests.
	 *
	 * @return bool
	 */
	function is_product(): bool {
		return ! empty( $GLOBALS['hwlio_test_is_product'] );
	}
}

if ( ! function_exists( 'get_post_thumbnail_id' ) ) {
	/**
	 * Minimal post-thumbnail shim for integration tests.
	 *
	 * @param int $post_id Post ID.
	 * @return int
	 */
	function get_post_thumbnail_id( int $post_id ): int {
		return isset( $GLOBALS['hwlio_test_post_thumbnail_ids'][ $post_id ] ) ? (int) $GLOBALS['hwlio_test_post_thumbnail_ids'][ $post_id ] : 0;
	}
}

if ( ! function_exists( 'wp_get_attachment_image_url' ) ) {
	/**
	 * Minimal attachment-image URL shim for integration tests.
	 *
	 * @param int    $attachment_id Attachment ID.
	 * @param string $size Requested size.
	 * @return string|false
	 */
	function wp_get_attachment_image_url( int $attachment_id, string $size ) {
		return isset( $GLOBALS['hwlio_test_attachment_image_urls'][ $attachment_id ][ $size ] )
			? (string) $GLOBALS['hwlio_test_attachment_image_urls'][ $attachment_id ][ $size ]
			: false;
	}
}

if ( ! function_exists( 'get_post_meta' ) ) {
	/**
	 * Minimal post-meta shim for integration tests.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key Meta key.
	 * @param bool   $single Whether to return one value.
	 * @return mixed
	 */
	function get_post_meta( int $post_id, string $key, bool $single = false ) {
		$value = $GLOBALS['hwlio_test_post_meta'][ $post_id ][ $key ] ?? ( $single ? '' : array() );

		if ( $single ) {
			return $value;
		}

		return is_array( $value ) ? $value : array( $value );
	}
}

if ( ! function_exists( 'is_admin' ) ) {
	/**
	 * Minimal admin-request shim for delivery and integration runtime tests.
	 *
	 * @return bool
	 */
	function is_admin(): bool {
		return ! empty( $GLOBALS['hwlio_test_is_admin'] );
	}
}

if ( ! function_exists( 'is_feed' ) ) {
	/**
	 * Minimal feed-request shim for delivery and integration runtime tests.
	 *
	 * @return bool
	 */
	function is_feed(): bool {
		return ! empty( $GLOBALS['hwlio_test_is_feed'] );
	}
}

if ( ! function_exists( 'wp_doing_ajax' ) ) {
	/**
	 * Minimal AJAX-request shim for delivery and integration runtime tests.
	 *
	 * @return bool
	 */
	function wp_doing_ajax(): bool {
		return ! empty( $GLOBALS['hwlio_test_wp_doing_ajax'] );
	}
}

if ( ! function_exists( 'wp_is_json_request' ) ) {
	/**
	 * Minimal REST/JSON-request shim for delivery and integration runtime tests.
	 *
	 * @return bool
	 */
	function wp_is_json_request(): bool {
		return ! empty( $GLOBALS['hwlio_test_wp_is_json_request'] );
	}
}

if ( ! function_exists( 'wp_enqueue_style' ) ) {
	/**
	 * Minimal stylesheet-enqueue shim for integration runtime tests.
	 *
	 * @param string       $handle Style handle.
	 * @param string|false $src Style URL.
	 * @param string[]     $deps Dependencies.
	 * @param string|bool|null $ver Version.
	 * @param string       $media Media target.
	 * @return void
	 */
	function wp_enqueue_style( string $handle, $src = false, array $deps = array(), $ver = false, string $media = 'all' ): void {
		if ( ! isset( $GLOBALS['hwlio_test_enqueued_styles'] ) || ! is_array( $GLOBALS['hwlio_test_enqueued_styles'] ) ) {
			$GLOBALS['hwlio_test_enqueued_styles'] = array();
		}

		$GLOBALS['hwlio_test_enqueued_styles'][ $handle ] = array(
			'src'   => $src,
			'deps'  => $deps,
			'ver'   => $ver,
			'media' => $media,
		);
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	/**
	 * Minimal JSON-encode shim for unit tests.
	 *
	 * @param mixed $value Value to encode.
	 * @return string|false
	 */
	function wp_json_encode( $value ) {
		return json_encode( $value );
	}
}

if ( ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
	/**
	 * Minimal HTML tag processor shim for delivery tests.
	 */
	final class WP_HTML_Tag_Processor {

		/**
		 * Raw markup.
		 *
		 * @var string
		 */
		private $html;

		/**
		 * Parsed tags.
		 *
		 * @var array<int,array<string,mixed>>
		 */
		private $tags = array();

		/**
		 * Current tag index.
		 *
		 * @var int
		 */
		private $index = -1;

		/**
		 * Whether parsing found an incomplete token.
		 *
		 * @var bool
		 */
		private $paused = false;

		/**
		 * Create shim.
		 *
		 * @param string $html Markup fragment.
		 */
		public function __construct( string $html ) {
			$this->html = $html;
			$this->parse();
		}

		/**
		 * Advance to the next tag.
		 *
		 * @return bool
		 */
		public function next_tag(): bool {
			++$this->index;

			return isset( $this->tags[ $this->index ] );
		}

		/**
		 * Get current tag name.
		 *
		 * @return string
		 */
		public function get_tag(): string {
			return isset( $this->tags[ $this->index ]['tag'] ) ? (string) $this->tags[ $this->index ]['tag'] : '';
		}

		/**
		 * Whether the current tag is a closer.
		 *
		 * @return bool
		 */
		public function is_tag_closer(): bool {
			return ! empty( $this->tags[ $this->index ]['closer'] );
		}

		/**
		 * Get one attribute value.
		 *
		 * @param string $name Attribute name.
		 * @return string|null
		 */
		public function get_attribute( string $name ): ?string {
			if ( ! isset( $this->tags[ $this->index ]['attributes'] ) ) {
				return null;
			}

			$attributes = $this->tags[ $this->index ]['attributes'];

			return isset( $attributes[ strtolower( $name ) ] ) ? (string) $attributes[ strtolower( $name ) ] : null;
		}

		/**
		 * Whether parsing paused at an incomplete token.
		 *
		 * @return bool
		 */
		public function paused_at_incomplete_token(): bool {
			return $this->paused;
		}

		/**
		 * Parse tags conservatively.
		 *
		 * @return void
		 */
		private function parse(): void {
			$pattern = '/<\s*(\/)?\s*([a-zA-Z][\w:-]*)([^<>]*?)(\/?)>/s';
			$offset  = 0;
			$found   = preg_match_all( $pattern, $this->html, $matches, PREG_OFFSET_CAPTURE );

			if ( ! is_int( $found ) || $found < 1 ) {
				$this->paused = false;
				return;
			}

			for ( $index = 0; $index < $found; ++$index ) {
				$full      = $matches[0][ $index ][0];
				$position  = (int) $matches[0][ $index ][1];
				$tag       = strtoupper( (string) $matches[2][ $index ][0] );
				$closer    = '' !== (string) $matches[1][ $index ][0];
				$attribute = (string) $matches[3][ $index ][0];

				$this->tags[] = array(
					'tag'        => $tag,
					'closer'     => $closer,
					'attributes' => $this->parse_attributes( $attribute ),
					'full'       => $full,
					'offset'     => $position,
				);

				$offset = $position + strlen( $full );
			}

			$remaining    = substr( $this->html, $offset );
			$this->paused = false !== strpos( $remaining, '<' ) && false === strpos( $remaining, '>' );
		}

		/**
		 * Parse attributes from one tag body.
		 *
		 * @param string $attributes Raw attribute content.
		 * @return array<string,string>
		 */
		private function parse_attributes( string $attributes ): array {
			$parsed = array();
			$found  = preg_match_all(
				'/([a-zA-Z_:][\w:.-]*)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'=<>`]+))/',
				$attributes,
				$matches,
				PREG_SET_ORDER
			);

			if ( ! is_int( $found ) || $found < 1 ) {
				return $parsed;
			}

			foreach ( $matches as $match ) {
				$value = '';

				foreach ( array( 2, 3, 4 ) as $index ) {
					if ( isset( $match[ $index ] ) && '' !== $match[ $index ] ) {
						$value = (string) $match[ $index ];
						break;
					}
				}

				$parsed[ strtolower( (string) $match[1] ) ] = $value;
			}

			return $parsed;
		}
	}
}
