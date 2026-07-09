<?php
/**
 * WordPress sample conversion probe.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Diagnostics;

/**
 * Runs sample conversions through WordPress image editor APIs.
 */
final class WordPressSampleConversionProbe implements SampleConversionProbeInterface {

	/**
	 * Convert a sample source to the target MIME type.
	 *
	 * @param string $source_path Source path.
	 * @param string $destination_path Destination path.
	 * @param string $mime_type Target MIME type.
	 * @return SampleConversionResult
	 */
	public function convert( string $source_path, string $destination_path, string $mime_type ): SampleConversionResult {
		if ( ! function_exists( 'wp_get_image_editor' ) ) {
			return SampleConversionResult::failure(
				'editor_api_unavailable',
				'The WordPress image editor API is unavailable.'
			);
		}

		$editor = \wp_get_image_editor( $source_path );

		if ( function_exists( 'is_wp_error' ) && \is_wp_error( $editor ) ) {
			return SampleConversionResult::failure(
				'editor_load_failed',
				'The sample image could not be loaded by WordPress.',
				array(
					'wp_error_code' => (string) $editor->get_error_code(),
				)
			);
		}

		if ( ! is_object( $editor ) || ! is_callable( array( $editor, 'save' ) ) ) {
			return SampleConversionResult::failure(
				'editor_unavailable',
				'No usable WordPress image editor was returned.'
			);
		}

		try {
			$saved = $editor->save( $destination_path, $mime_type );
		} catch ( \Throwable $throwable ) {
			return SampleConversionResult::failure(
				'conversion_failed',
				'The sample conversion failed.',
				array(
					'exception' => get_class( $throwable ),
				)
			);
		}

		if ( function_exists( 'is_wp_error' ) && \is_wp_error( $saved ) ) {
			return SampleConversionResult::failure(
				'conversion_failed',
				'The sample conversion failed.',
				array(
					'wp_error_code' => (string) $saved->get_error_code(),
				)
			);
		}

		if ( ! is_array( $saved ) ) {
			return SampleConversionResult::failure(
				'conversion_failed',
				'The image editor did not return a valid save result.'
			);
		}

		return SampleConversionResult::success();
	}
}
