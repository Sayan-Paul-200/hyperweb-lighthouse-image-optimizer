<?php
/**
 * Fake admin asset runtime adapter.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Admin;

use HyperWeb\LighthouseImageOptimizer\Admin\AdminAssetRuntimeInterface;

/**
 * Records asset/bootstrap calls for unit tests.
 */
final class FakeAdminAssetRuntime implements AdminAssetRuntimeInterface {

	/**
	 * Enqueued styles.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	public $styles = array();

	/**
	 * Enqueued scripts.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	public $scripts = array();

	/**
	 * Inline scripts.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	public $inline_scripts = array();

	/**
	 * REST URL returned by the fake runtime.
	 *
	 * @var string
	 */
	public $rest_url_base = 'https://example.test/wp-json/';

	/**
	 * Nonce returned by the fake runtime.
	 *
	 * @var string
	 */
	public $nonce = 'rest-nonce';

	/**
	 * Enqueue one stylesheet.
	 *
	 * @param string   $handle Asset handle.
	 * @param string   $src Asset URL.
	 * @param string[] $deps Dependency handles.
	 * @param string   $version Asset version.
	 * @param string   $media Media target.
	 * @return void
	 */
	public function enqueue_style( string $handle, string $src, array $deps, string $version, string $media = 'all' ): void {
		$this->styles[] = array(
			'handle'  => $handle,
			'src'     => $src,
			'deps'    => $deps,
			'version' => $version,
			'media'   => $media,
		);
	}

	/**
	 * Enqueue one script.
	 *
	 * @param string   $handle Asset handle.
	 * @param string   $src Asset URL.
	 * @param string[] $deps Dependency handles.
	 * @param string   $version Asset version.
	 * @param bool     $in_footer Whether to print in the footer.
	 * @return void
	 */
	public function enqueue_script( string $handle, string $src, array $deps, string $version, bool $in_footer ): void {
		$this->scripts[] = array(
			'handle'    => $handle,
			'src'       => $src,
			'deps'      => $deps,
			'version'   => $version,
			'in_footer' => $in_footer,
		);
	}

	/**
	 * Add inline bootstrap code before the given script.
	 *
	 * @param string $handle Script handle.
	 * @param string $data Inline JavaScript.
	 * @return void
	 */
	public function add_inline_script_before( string $handle, string $data ): void {
		$this->inline_scripts[] = array(
			'handle'   => $handle,
			'data'     => $data,
			'position' => 'before',
		);
	}

	/**
	 * Build a REST URL.
	 *
	 * @param string $path REST-relative path.
	 * @return string
	 */
	public function rest_url( string $path ): string {
		return rtrim( $this->rest_url_base, '/' ) . '/' . ltrim( $path, '/' );
	}

	/**
	 * Create a nonce.
	 *
	 * @param string $action Nonce action.
	 * @return string
	 */
	public function create_nonce( string $action ): string {
		unset( $action );

		return $this->nonce;
	}
}
