<?php
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

			$remaining = substr( $this->html, $offset );
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
