<?php
/**
 * WordPress uploads runtime.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Delivery;

/**
 * Reads uploads facts and delivery filters through WordPress APIs.
 */
final class WordPressUploadsRuntime implements UploadsRuntimeInterface {

	/**
	 * Read the current uploads base URL.
	 *
	 * @param DerivativeUrlRequest $request Resolver request context.
	 * @return string|null
	 */
	public function uploads_base_url( DerivativeUrlRequest $request ): ?string {
		$uploads = $this->uploads();

		if ( ! is_array( $uploads ) ) {
			return null;
		}

		$base_url = isset( $uploads['baseurl'] ) && is_string( $uploads['baseurl'] )
			? trim( $uploads['baseurl'] )
			: '';

		if ( '' === $base_url ) {
			return null;
		}

		if ( function_exists( 'apply_filters' ) ) {
			$filtered = \apply_filters(
				'hwlio_delivery_uploads_base_url',
				$base_url,
				$request->relative_path(),
				$request->attachment_id(),
				$request->size_name(),
				$request->format()
			);

			if ( is_scalar( $filtered ) ) {
				$base_url = trim( (string) $filtered );
			}
		}

		return '' !== $base_url ? $base_url : null;
	}

	/**
	 * Read the current uploads base directory.
	 *
	 * @return string|null
	 */
	public function uploads_base_dir(): ?string {
		$uploads = $this->uploads();

		if ( ! is_array( $uploads ) ) {
			return null;
		}

		$base_dir = isset( $uploads['basedir'] ) && is_string( $uploads['basedir'] )
			? trim( $uploads['basedir'] )
			: '';

		return '' !== $base_dir ? $base_dir : null;
	}

	/**
	 * Allow runtime filters to rewrite a resolved derivative URL.
	 *
	 * @param string               $url Resolved derivative URL.
	 * @param DerivativeUrlRequest $request Resolver request context.
	 * @return string
	 */
	public function filter_derivative_url( string $url, DerivativeUrlRequest $request ): string {
		if ( ! function_exists( 'apply_filters' ) ) {
			return $url;
		}

		$filtered = \apply_filters(
			'hwlio_delivery_derivative_url',
			$url,
			$request->relative_path(),
			$request->attachment_id(),
			$request->size_name(),
			$request->format()
		);

		if ( ! is_scalar( $filtered ) ) {
			return $url;
		}

		$filtered = trim( (string) $filtered );

		return '' !== $filtered ? $filtered : $url;
	}

	/**
	 * Read uploads data from WordPress.
	 *
	 * @return array<string,mixed>|null
	 */
	private function uploads(): ?array {
		if ( ! function_exists( 'wp_upload_dir' ) ) {
			return null;
		}

		$uploads = \wp_upload_dir( null, false );

		if ( ! is_array( $uploads ) ) {
			return null;
		}

		if ( is_string( $uploads['error'] ) && '' !== trim( $uploads['error'] ) ) {
			return null;
		}

		return $uploads;
	}
}
