<?php
/**
 * Derivative URL resolver.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Delivery;

use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifestSanitizer;

/**
 * Converts safe uploads-relative derivative paths into runtime URLs.
 */
final class DerivativeUrlResolver {

	/**
	 * Uploads runtime.
	 *
	 * @var UploadsRuntimeInterface
	 */
	private $runtime;

	/**
	 * Path sanitizer.
	 *
	 * @var DerivativeManifestSanitizer
	 */
	private $sanitizer;

	/**
	 * Build a WordPress-backed resolver.
	 *
	 * @return self
	 */
	public static function for_wordpress(): self {
		return new self(
			new WordPressUploadsRuntime(),
			new DerivativeManifestSanitizer()
		);
	}

	/**
	 * Create resolver.
	 *
	 * @param UploadsRuntimeInterface     $runtime Uploads runtime.
	 * @param DerivativeManifestSanitizer $sanitizer Path sanitizer.
	 */
	public function __construct(
		UploadsRuntimeInterface $runtime,
		DerivativeManifestSanitizer $sanitizer
	) {
		$this->runtime   = $runtime;
		$this->sanitizer = $sanitizer;
	}

	/**
	 * Resolve a derivative URL from a safe relative path.
	 *
	 * @param DerivativeUrlRequest $request Request.
	 * @return DerivativeUrlResolutionResult
	 */
	public function resolve( DerivativeUrlRequest $request ): DerivativeUrlResolutionResult {
		$relative_path = $this->sanitizer->safe_relative_path( $request->relative_path() );

		if ( '' === $relative_path ) {
			return DerivativeUrlResolutionResult::invalid(
				$request,
				DerivativeUrlResolutionResult::CODE_INVALID_RELATIVE_PATH
			);
		}

		$request  = $request->with_relative_path( $relative_path );
		$base_url = $this->normalize_base_url( $this->runtime->uploads_base_url( $request ) );

		if ( '' === $base_url ) {
			return DerivativeUrlResolutionResult::invalid(
				$request,
				DerivativeUrlResolutionResult::CODE_UPLOADS_URL_UNAVAILABLE
			);
		}

		$url          = $this->join_url( $base_url, $relative_path );
		$filtered_url = trim( $this->runtime->filter_derivative_url( $url, $request ) );

		if ( '' !== $filtered_url ) {
			$url = $filtered_url;
		}

		return DerivativeUrlResolutionResult::resolved( $request, $url );
	}

	/**
	 * Normalize a base URL.
	 *
	 * @param string|null $base_url Base URL.
	 * @return string
	 */
	private function normalize_base_url( ?string $base_url ): string {
		if ( null === $base_url ) {
			return '';
		}

		return trim( $base_url );
	}

	/**
	 * Join a base URL and relative path with exactly one slash.
	 *
	 * @param string $base_url Base URL.
	 * @param string $relative_path Relative path.
	 * @return string
	 */
	private function join_url( string $base_url, string $relative_path ): string {
		return rtrim( $base_url, '/' ) . '/' . ltrim( $relative_path, '/' );
	}
}
