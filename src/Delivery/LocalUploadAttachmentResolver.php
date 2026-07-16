<?php
/**
 * Local uploads URL attachment resolver.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Delivery;

use HyperWeb\LighthouseImageOptimizer\Reporting\TrustedAttachmentMarkerParser;

/**
 * Resolves one standalone image fragment's local uploads URL to an attachment ID when safe.
 */
final class LocalUploadAttachmentResolver {

	/**
	 * Uploads base URL callback.
	 *
	 * @var callable():string
	 */
	private $uploads_base_url;

	/**
	 * URL-to-attachment callback.
	 *
	 * @var callable(string):int
	 */
	private $attachment_id_for_url;

	/**
	 * Trusted marker parser.
	 *
	 * @var TrustedAttachmentMarkerParser
	 */
	private $markers;

	/**
	 * Request-local lookup cache.
	 *
	 * @var array<string,int>
	 */
	private $cache = array();

	/**
	 * Create resolver.
	 *
	 * @param callable():string                  $uploads_base_url Uploads base URL callback.
	 * @param callable(string):int               $attachment_id_for_url URL-to-attachment callback.
	 * @param TrustedAttachmentMarkerParser|null $markers Trusted marker parser.
	 */
	public function __construct( callable $uploads_base_url, callable $attachment_id_for_url, ?TrustedAttachmentMarkerParser $markers = null ) {
		$this->uploads_base_url      = $uploads_base_url;
		$this->attachment_id_for_url = $attachment_id_for_url;
		$this->markers               = $markers ?? new TrustedAttachmentMarkerParser();
	}

	/**
	 * Create resolver for WordPress runtime.
	 *
	 * @param TrustedAttachmentMarkerParser|null $markers Trusted marker parser.
	 * @return self
	 */
	public static function for_wordpress( ?TrustedAttachmentMarkerParser $markers = null ): self {
		return new self(
			static function (): string {
				if ( function_exists( 'wp_get_upload_dir' ) ) {
					$uploads = \wp_get_upload_dir();

					if ( is_array( $uploads ) && isset( $uploads['baseurl'] ) && is_string( $uploads['baseurl'] ) ) {
						return trim( $uploads['baseurl'] );
					}
				}

				if ( function_exists( 'wp_upload_dir' ) ) {
					$uploads = \wp_upload_dir( null, false );

					if ( is_array( $uploads ) ) {
						return trim( $uploads['baseurl'] );
					}
				}

				return '';
			},
			static function ( string $url ): int {
				if ( ! function_exists( 'attachment_url_to_postid' ) ) {
					return 0;
				}

				return max( 0, (int) \attachment_url_to_postid( $url ) );
			},
			$markers
		);
	}

	/**
	 * Resolve one image fragment.
	 *
	 * @param string $html Standalone IMG fragment.
	 * @return LocalUploadAttachmentResolution
	 */
	public function resolve( string $html ): LocalUploadAttachmentResolution {
		$src = $this->attribute_value( $html, 'src' );

		if ( null === $src || '' === trim( $src ) ) {
			return LocalUploadAttachmentResolution::unresolved();
		}

		$local = $this->local_upload_url( trim( $src ) );

		if ( null === $local ) {
			return LocalUploadAttachmentResolution::not_local_upload();
		}

		if ( '' === $local['url'] ) {
			return LocalUploadAttachmentResolution::unresolved( $local['relative_path'] );
		}

		$marker_id = $this->markers->parse_attachment_id( $html );

		if ( $marker_id > 0 ) {
			return LocalUploadAttachmentResolution::resolved_trusted_marker( $marker_id, $local['relative_path'] );
		}

		$cache_key = strtolower( $local['url'] );

		if ( ! array_key_exists( $cache_key, $this->cache ) ) {
			$lookup                    = $this->attachment_id_for_url;
			$this->cache[ $cache_key ] = max( 0, (int) $lookup( $local['url'] ) );
		}

		if ( $this->cache[ $cache_key ] > 0 ) {
			return LocalUploadAttachmentResolution::resolved_upload_url( $this->cache[ $cache_key ], $local['relative_path'] );
		}

		return LocalUploadAttachmentResolution::unresolved( $local['relative_path'] );
	}

	/**
	 * Extract one raw attribute value exactly from the image fragment.
	 *
	 * @param string $html Markup fragment.
	 * @param string $attribute Attribute name.
	 * @return string|null
	 */
	private function attribute_value( string $html, string $attribute ): ?string {
		$pattern = sprintf(
			'/\b%s\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s"\'=<>`]+))/i',
			preg_quote( $attribute, '/' )
		);

		if ( 1 !== preg_match( $pattern, $html, $matches ) ) {
			return null;
		}

		foreach ( array( 1, 2, 3 ) as $index ) {
			if ( array_key_exists( $index, $matches ) ) {
				return $matches[ $index ];
			}
		}

		return null;
	}

	/**
	 * Resolve and validate one local uploads URL.
	 *
	 * @param string $url Raw URL.
	 * @return array{url:string,relative_path:string}|null
	 */
	private function local_upload_url( string $url ): ?array {
		if ( '' === $url || false !== strpos( $url, "\0" ) || false !== strpos( $url, '\\' ) ) {
			return null;
		}

		$base = trim( (string) call_user_func( $this->uploads_base_url ) );

		if ( '' === $base ) {
			return null;
		}

		$url_parts  = $this->parse_url_parts( $url );
		$base_parts = $this->parse_url_parts( $base );

		if ( ! is_array( $url_parts ) || ! is_array( $base_parts ) || empty( $base_parts['path'] ) ) {
			return null;
		}

		if ( isset( $url_parts['host'] ) ) {
			if ( ! isset( $base_parts['host'] ) || strtolower( (string) $url_parts['host'] ) !== strtolower( (string) $base_parts['host'] ) ) {
				return null;
			}

			if ( ! isset( $url_parts['scheme'] ) || ! in_array( strtolower( (string) $url_parts['scheme'] ), array( 'http', 'https' ), true ) ) {
				return null;
			}
		} elseif ( 0 !== strpos( $url, '/' ) || 0 === strpos( $url, '//' ) ) {
			return null;
		}

		$path      = isset( $url_parts['path'] ) && is_string( $url_parts['path'] ) ? rawurldecode( $url_parts['path'] ) : '';
		$base_path = rawurldecode( (string) $base_parts['path'] );
		$path      = '/' . ltrim( $path, '/' );
		$base_path = '/' . trim( $base_path, '/' );

		if ( $path !== $base_path && 0 !== strpos( $path, $base_path . '/' ) ) {
			return null;
		}

		$relative = ltrim( substr( $path, strlen( $base_path ) ), '/' );

		if ( ! $this->is_safe_image_relative_path( $relative ) ) {
			return array(
				'url'           => '',
				'relative_path' => '',
			);
		}

		$absolute_url = rtrim( $this->base_origin( $base_parts ), '/' ) . $path;

		return array(
			'url'           => $absolute_url,
			'relative_path' => $relative,
		);
	}

	/**
	 * Build base URL origin.
	 *
	 * @param array<string,mixed> $base_parts Parsed base URL.
	 * @return string
	 */
	private function base_origin( array $base_parts ): string {
		$scheme = isset( $base_parts['scheme'] ) && is_string( $base_parts['scheme'] ) ? strtolower( $base_parts['scheme'] ) : 'https';
		$host   = isset( $base_parts['host'] ) && is_string( $base_parts['host'] ) ? $base_parts['host'] : '';
		$port   = isset( $base_parts['port'] ) && is_numeric( $base_parts['port'] ) ? ':' . (int) $base_parts['port'] : '';

		return $scheme . '://' . $host . $port;
	}

	/**
	 * Parse a URL using WordPress when available.
	 *
	 * @param string $url URL.
	 * @return array<string,mixed>|false
	 */
	private function parse_url_parts( string $url ) {
		if ( function_exists( 'wp_parse_url' ) ) {
			return \wp_parse_url( $url );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Safe fallback outside WordPress bootstrap.
		return parse_url( $url );
	}

	/**
	 * Whether an uploads-relative path is safe and image-like.
	 *
	 * @param string $relative Relative path.
	 * @return bool
	 */
	private function is_safe_image_relative_path( string $relative ): bool {
		$relative = trim( str_replace( '\\', '/', $relative ) );

		if ( '' === $relative || false !== strpos( $relative, "\0" ) ) {
			return false;
		}

		if ( preg_match( '#^[a-z][a-z0-9+.-]*://#i', $relative ) || 0 === strpos( $relative, '/' ) ) {
			return false;
		}

		$segments = explode( '/', $relative );

		foreach ( $segments as $segment ) {
			if ( '' === $segment || '.' === $segment || '..' === $segment ) {
				return false;
			}
		}

		return 1 === preg_match( '/\.(?:jpe?g|png|webp|avif)$/i', $relative );
	}
}
