<?php
/**
 * WordPress conversion editor adapter.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Image;

/**
 * Converts images through WordPress image editor APIs.
 */
final class WordPressConversionEditor implements ConversionEditorInterface {

	/**
	 * Save a converted derivative to the request temporary path.
	 *
	 * @param SourceImage     $source Source image.
	 * @param DestinationPath $destination Destination path.
	 * @param int             $quality Conversion quality.
	 * @return ConversionEditorResult
	 */
	public function save( SourceImage $source, DestinationPath $destination, int $quality ): ConversionEditorResult {
		if ( ! function_exists( 'wp_get_image_editor' ) ) {
			return ConversionEditorResult::failure(
				ConversionResultCode::EDITOR_UNAVAILABLE,
				'The WordPress image editor API is unavailable.'
			);
		}

		try {
			$editor = \wp_get_image_editor( $source->absolute_path() );
		} catch ( \Throwable $throwable ) {
			return ConversionEditorResult::failure(
				ConversionResultCode::EDITOR_LOAD_FAILED,
				'The source image could not be loaded by WordPress.',
				array(
					'exception' => get_class( $throwable ),
				)
			);
		}

		if ( function_exists( 'is_wp_error' ) && \is_wp_error( $editor ) ) {
			return ConversionEditorResult::failure(
				ConversionResultCode::EDITOR_LOAD_FAILED,
				'The source image could not be loaded by WordPress.',
				array(
					'wp_error_code' => (string) $editor->get_error_code(),
				)
			);
		}

		if ( ! is_object( $editor ) || ! is_callable( array( $editor, 'set_quality' ) ) || ! is_callable( array( $editor, 'save' ) ) ) {
			return ConversionEditorResult::failure(
				ConversionResultCode::EDITOR_UNAVAILABLE,
				'No usable WordPress image editor was returned.'
			);
		}

		try {
			$quality_result = $editor->set_quality( max( 1, min( 100, $quality ) ) );
		} catch ( \Throwable $throwable ) {
			return ConversionEditorResult::failure(
				ConversionResultCode::CONVERSION_FAILED,
				'The image editor could not apply the requested quality.',
				array(
					'exception' => get_class( $throwable ),
				)
			);
		}

		if ( function_exists( 'is_wp_error' ) && \is_wp_error( $quality_result ) ) {
			return ConversionEditorResult::failure(
				ConversionResultCode::CONVERSION_FAILED,
				'The image editor could not apply the requested quality.',
				array(
					'wp_error_code' => (string) $quality_result->get_error_code(),
				)
			);
		}

		try {
			$saved = $editor->save( $destination->temporary_absolute_path(), $destination->target_mime() );
		} catch ( \Throwable $throwable ) {
			return ConversionEditorResult::failure(
				ConversionResultCode::CONVERSION_FAILED,
				'The image editor failed while saving the derivative.',
				array(
					'exception' => get_class( $throwable ),
				)
			);
		}

		if ( function_exists( 'is_wp_error' ) && \is_wp_error( $saved ) ) {
			return ConversionEditorResult::failure(
				ConversionResultCode::CONVERSION_FAILED,
				'The image editor failed while saving the derivative.',
				array(
					'wp_error_code' => (string) $saved->get_error_code(),
				)
			);
		}

		if ( ! is_array( $saved ) ) {
			return ConversionEditorResult::failure(
				ConversionResultCode::CONVERSION_FAILED,
				'The image editor did not return a valid save result.'
			);
		}

		$output_path = isset( $saved['path'] ) && is_scalar( $saved['path'] ) ? (string) $saved['path'] : '';

		return ConversionEditorResult::success( array(), $output_path );
	}
}
