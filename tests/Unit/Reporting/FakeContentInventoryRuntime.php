<?php
// phpcs:ignoreFile -- Test fake intentionally keeps interface methods compact.
/**
 * Fake content inventory runtime.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Reporting;

use HyperWeb\LighthouseImageOptimizer\Reporting\ContentInventoryRuntimeInterface;

/**
 * Provides deterministic arbitrary-content facts for inventory tests.
 */
final class FakeContentInventoryRuntime implements ContentInventoryRuntimeInterface {

	/**
	 * Content records.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	public $content = array();

	/**
	 * Site URL.
	 *
	 * @var string
	 */
	public $site = 'https://example.test/wp/';

	/**
	 * Home URL.
	 *
	 * @var string
	 */
	public $home = 'https://example.test/';

	/**
	 * Uploads base URL.
	 *
	 * @var string
	 */
	public $uploads = 'https://example.test/wp-content/uploads';

	/**
	 * Public URL overrides.
	 *
	 * @var array<int,string>
	 */
	public $public_urls = array();

	/**
	 * {@inheritDoc}
	 */
	public function content_exists( int $content_id ): bool {
		return isset( $this->content[ $content_id ] );
	}

	/**
	 * {@inheritDoc}
	 */
	public function content_type( int $content_id ): string {
		return isset( $this->content[ $content_id ]['type'] ) ? (string) $this->content[ $content_id ]['type'] : '';
	}

	/**
	 * {@inheritDoc}
	 */
	public function content_status( int $content_id ): string {
		return isset( $this->content[ $content_id ]['status'] ) ? (string) $this->content[ $content_id ]['status'] : '';
	}

	/**
	 * {@inheritDoc}
	 */
	public function content_title( int $content_id ): string {
		return isset( $this->content[ $content_id ]['title'] ) ? (string) $this->content[ $content_id ]['title'] : '';
	}

	/**
	 * {@inheritDoc}
	 */
	public function content_body( int $content_id ): string {
		return isset( $this->content[ $content_id ]['body'] ) ? (string) $this->content[ $content_id ]['body'] : '';
	}

	/**
	 * {@inheritDoc}
	 */
	public function featured_image_id( int $content_id ): int {
		return isset( $this->content[ $content_id ]['featured_image_id'] ) ? max( 0, (int) $this->content[ $content_id ]['featured_image_id'] ) : 0;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return int[]
	 */
	public function product_gallery_image_ids( int $content_id ): array {
		return isset( $this->content[ $content_id ]['gallery_ids'] ) && is_array( $this->content[ $content_id ]['gallery_ids'] )
			? array_values( $this->content[ $content_id ]['gallery_ids'] )
			: array();
	}

	/**
	 * {@inheritDoc}
	 */
	public function site_url(): string {
		return $this->site;
	}

	/**
	 * {@inheritDoc}
	 */
	public function home_url(): string {
		return $this->home;
	}

	/**
	 * {@inheritDoc}
	 */
	public function uploads_base_url(): string {
		return $this->uploads;
	}

	/**
	 * {@inheritDoc}
	 */
	public function content_public_url( int $content_id ): string {
		if ( isset( $this->public_urls[ $content_id ] ) ) {
			return (string) $this->public_urls[ $content_id ];
		}

		return isset( $this->content[ $content_id ] ) ? $this->home . '?p=' . $content_id : '';
	}
}
