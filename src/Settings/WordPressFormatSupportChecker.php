<?php
/**
 * WordPress image format support checker.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Settings;

/**
 * Uses WordPress image APIs to check modern format support.
 */
final class WordPressFormatSupportChecker implements FormatSupportCheckerInterface {

	/**
	 * MIME types for plugin-known output formats.
	 *
	 * @var array<string,string>
	 */
	private const MIME_TYPES = array(
		SettingsSchema::FORMAT_WEBP => 'image/webp',
		SettingsSchema::FORMAT_AVIF => 'image/avif',
	);

	/**
	 * Determine whether a target format is supported.
	 *
	 * @param string $format Target format.
	 * @return bool|null
	 */
	public function supports( string $format ): ?bool {
		$format = strtolower( trim( $format ) );

		if ( ! isset( self::MIME_TYPES[ $format ] ) ) {
			return null;
		}

		if ( ! function_exists( 'wp_image_editor_supports' ) ) {
			return null;
		}

		return (bool) \wp_image_editor_supports(
			array(
				'mime_type' => self::MIME_TYPES[ $format ],
			)
		);
	}
}
