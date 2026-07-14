<?php
/**
 * WordPress content inventory runtime.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Reporting;

/**
 * Reads arbitrary content and URL facts through WordPress core APIs.
 */
final class WordPressContentInventoryRuntime implements ContentInventoryRuntimeInterface {

	/**
	 * Whether one content record exists.
	 *
	 * @param int $content_id Content ID.
	 * @return bool
	 */
	public function content_exists( int $content_id ): bool {
		return null !== $this->post( $content_id );
	}

	/**
	 * Get content type.
	 *
	 * @param int $content_id Content ID.
	 * @return string
	 */
	public function content_type( int $content_id ): string {
		$post = $this->post( $content_id );

		return is_object( $post ) && isset( $post->post_type ) && is_string( $post->post_type ) ? $post->post_type : '';
	}

	/**
	 * Get content status.
	 *
	 * @param int $content_id Content ID.
	 * @return string
	 */
	public function content_status( int $content_id ): string {
		$post = $this->post( $content_id );

		return is_object( $post ) && isset( $post->post_status ) && is_string( $post->post_status ) ? $post->post_status : '';
	}

	/**
	 * Get content title.
	 *
	 * @param int $content_id Content ID.
	 * @return string
	 */
	public function content_title( int $content_id ): string {
		$post = $this->post( $content_id );

		return is_object( $post ) && isset( $post->post_title ) && is_string( $post->post_title ) ? $post->post_title : '';
	}

	/**
	 * Get raw stored post content.
	 *
	 * @param int $content_id Content ID.
	 * @return string
	 */
	public function content_body( int $content_id ): string {
		if ( $content_id < 1 || ! function_exists( 'get_post_field' ) ) {
			return '';
		}

		$content = \get_post_field( 'post_content', $content_id );

		return is_string( $content ) ? $content : '';
	}

	/**
	 * Get featured image ID.
	 *
	 * @param int $content_id Content ID.
	 * @return int
	 */
	public function featured_image_id( int $content_id ): int {
		if ( $content_id < 1 || ! function_exists( 'get_post_thumbnail_id' ) ) {
			return 0;
		}

		return max( 0, (int) \get_post_thumbnail_id( $content_id ) );
	}

	/**
	 * Get WooCommerce gallery IDs.
	 *
	 * @param int $content_id Content ID.
	 * @return int[]
	 */
	public function product_gallery_image_ids( int $content_id ): array {
		if ( $content_id < 1 || ! function_exists( 'get_post_meta' ) ) {
			return array();
		}

		$raw = \get_post_meta( $content_id, '_product_image_gallery', true );

		if ( is_array( $raw ) ) {
			$parts = $raw;
		} elseif ( is_string( $raw ) ) {
			$parts = array_filter(
				array_map( 'trim', explode( ',', $raw ) ),
				static function ( string $value ): bool {
					return '' !== $value;
				}
			);
		} else {
			$parts = array();
		}

		$ids = array();

		foreach ( $parts as $value ) {
			if ( is_numeric( $value ) ) {
				$id = max( 0, (int) $value );

				if ( $id > 0 ) {
					$ids[] = $id;
				}
			}
		}

		return array_values( array_unique( $ids ) );
	}

	/**
	 * Get site URL.
	 *
	 * @return string
	 */
	public function site_url(): string {
		return function_exists( 'site_url' ) ? (string) \site_url( '/' ) : '';
	}

	/**
	 * Get home URL.
	 *
	 * @return string
	 */
	public function home_url(): string {
		return function_exists( 'home_url' ) ? (string) \home_url( '/' ) : '';
	}

	/**
	 * Get current uploads base URL.
	 *
	 * @return string
	 */
	public function uploads_base_url(): string {
		if ( function_exists( 'wp_get_upload_dir' ) ) {
			$uploads = \wp_get_upload_dir();

			if ( is_array( $uploads ) && isset( $uploads['baseurl'] ) && is_string( $uploads['baseurl'] ) ) {
				return trim( $uploads['baseurl'] );
			}
		}

		if ( function_exists( 'wp_upload_dir' ) ) {
			$uploads = \wp_upload_dir( null, false );

			if ( is_array( $uploads ) && isset( $uploads['baseurl'] ) && is_string( $uploads['baseurl'] ) ) {
				return trim( $uploads['baseurl'] );
			}
		}

		return '';
	}

	/**
	 * Get one safe public content URL for optional external reporting.
	 *
	 * @param int $content_id Content ID.
	 * @return string
	 */
	public function content_public_url( int $content_id ): string {
		$post = $this->post( $content_id );

		if ( ! is_object( $post ) || ! function_exists( 'get_permalink' ) ) {
			return '';
		}

		if ( function_exists( 'is_post_publicly_viewable' ) && ! \is_post_publicly_viewable( $post ) ) {
			return '';
		}

		if ( isset( $post->post_status ) && is_string( $post->post_status ) && 'publish' !== $post->post_status && ! function_exists( 'is_post_publicly_viewable' ) ) {
			return '';
		}

		$url = \get_permalink( $content_id );

		return is_string( $url ) ? trim( $url ) : '';
	}

	/**
	 * Read one post object.
	 *
	 * @param int $content_id Content ID.
	 * @return object|null
	 */
	private function post( int $content_id ): ?object {
		if ( $content_id < 1 || ! function_exists( 'get_post' ) ) {
			return null;
		}

		$post = \get_post( $content_id );

		return is_object( $post ) ? $post : null;
	}
}
