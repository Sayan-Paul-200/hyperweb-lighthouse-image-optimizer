<?php
/**
 * Elementor background discovery service.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration;

/**
 * Discovers supported structured Elementor background-image mappings read-only.
 */
final class ElementorBackgroundDiscovery {

	/**
	 * Supported background-group configuration.
	 *
	 * @var array<string,array<string,mixed>>
	 */
	private const GROUPS = array(
		'background'         => array(
			'mode_key'    => 'background_background',
			'desktop_key' => 'background_image',
			'tablet_key'  => 'background_image_tablet',
			'mobile_key'  => 'background_image_mobile',
		),
		'background_overlay' => array(
			'mode_key'    => 'background_overlay_background',
			'desktop_key' => 'background_overlay_image',
			'tablet_key'  => 'background_overlay_image_tablet',
			'mobile_key'  => 'background_overlay_image_mobile',
		),
	);

	/**
	 * Known Elementor-owned CSS text-setting keys.
	 *
	 * @var string[]
	 */
	private const CSS_TEXT_KEYS = array( 'custom_css' );

	/**
	 * Read-only document-data store.
	 *
	 * @var ElementorDocumentDataStoreInterface
	 */
	private $store;

	/**
	 * Create discovery service.
	 *
	 * @param ElementorDocumentDataStoreInterface $store Document-data store.
	 */
	public function __construct( ElementorDocumentDataStoreInterface $store ) {
		$this->store = $store;
	}

	/**
	 * Discover supported structured Elementor background-image mappings.
	 *
	 * @param int $document_id Document/post ID.
	 * @return ElementorBackgroundDiscoveryResult
	 */
	public function discover( int $document_id ): ElementorBackgroundDiscoveryResult {
		$document_id = max( 0, $document_id );

		if ( $document_id < 1 ) {
			return new ElementorBackgroundDiscoveryResult( 0 );
		}

		$data = $this->store->read_document( $document_id );

		return $this->discover_from_document( $document_id, $data );
	}

	/**
	 * Discover supported structured background-image mappings from a pre-read document payload.
	 *
	 * @param int                  $document_id Document/post ID.
	 * @param ElementorDocumentData $data Pre-read document data.
	 * @return ElementorBackgroundDiscoveryResult
	 */
	public function discover_from_document( int $document_id, ElementorDocumentData $data ): ElementorBackgroundDiscoveryResult {
		$document_id = max( 0, $document_id );

		if ( $data->is_missing() ) {
			return new ElementorBackgroundDiscoveryResult( $document_id );
		}

		if ( $data->is_invalid() ) {
			return new ElementorBackgroundDiscoveryResult(
				$document_id,
				array(),
				array(
					new ElementorUnsupportedBackgroundCase(
						ElementorUnsupportedBackgroundCase::CODE_INVALID_DOCUMENT_DATA,
						$document_id,
						'',
						'document',
						'desktop',
						WordPressElementorDocumentDataStore::META_KEY
					),
				)
			);
		}

		$supported   = array();
		$unsupported = array();

		foreach ( $data->elements() as $element ) {
			$this->inspect_element( $document_id, $element, $supported, $unsupported );
		}

		return new ElementorBackgroundDiscoveryResult( $document_id, $supported, $unsupported );
	}

	/**
	 * Inspect one element recursively.
	 *
	 * @param int                                           $document_id Document ID.
	 * @param array<string,mixed>                           $element Normalized element.
	 * @param array<int,ElementorBackgroundSource>          $supported Supported-source collector.
	 * @param array<int,ElementorUnsupportedBackgroundCase> $unsupported Unsupported-case collector.
	 * @return void
	 */
	private function inspect_element( int $document_id, array $element, array &$supported, array &$unsupported ): void {
		$element_id   = isset( $element['id'] ) && is_string( $element['id'] ) ? trim( $element['id'] ) : '';
		$element_type = isset( $element['elType'] ) && is_string( $element['elType'] ) ? trim( $element['elType'] ) : '';
		$widget_type  = isset( $element['widgetType'] ) && is_string( $element['widgetType'] ) ? trim( $element['widgetType'] ) : '';
		$settings     = isset( $element['settings'] ) && is_array( $element['settings'] ) ? $element['settings'] : array();

		foreach ( self::GROUPS as $setting_group => $config ) {
			$this->inspect_background_group(
				$document_id,
				$element_id,
				$element_type,
				$widget_type,
				$settings,
				$setting_group,
				$config,
				$supported,
				$unsupported
			);
		}

		$this->inspect_custom_css( $document_id, $element_id, $settings, $unsupported );

		if ( isset( $element['elements'] ) && is_array( $element['elements'] ) ) {
			foreach ( $element['elements'] as $child ) {
				if ( is_array( $child ) ) {
					$this->inspect_element( $document_id, $child, $supported, $unsupported );
				}
			}
		}
	}

	/**
	 * Inspect one supported structured background group.
	 *
	 * @param int                                           $document_id Document ID.
	 * @param string                                        $element_id Elementor element ID.
	 * @param string                                        $element_type Elementor element type.
	 * @param string                                        $widget_type Elementor widget type.
	 * @param array<string,mixed>                           $settings Element settings.
	 * @param string                                        $setting_group Setting group.
	 * @param array<string,mixed>                           $config Group configuration.
	 * @param array<int,ElementorBackgroundSource>          $supported Supported-source collector.
	 * @param array<int,ElementorUnsupportedBackgroundCase> $unsupported Unsupported-case collector.
	 * @return void
	 */
	private function inspect_background_group(
		int $document_id,
		string $element_id,
		string $element_type,
		string $widget_type,
		array $settings,
		string $setting_group,
		array $config,
		array &$supported,
		array &$unsupported
	): void {
		$mode_key = isset( $config['mode_key'] ) ? (string) $config['mode_key'] : '';
		$mode     = $this->normalized_string_setting( $settings, $mode_key );
		$devices  = array(
			'desktop' => isset( $config['desktop_key'] ) ? (string) $config['desktop_key'] : '',
			'tablet'  => isset( $config['tablet_key'] ) ? (string) $config['tablet_key'] : '',
			'mobile'  => isset( $config['mobile_key'] ) ? (string) $config['mobile_key'] : '',
		);

		if ( ! $this->has_any_media_setting( $settings, $devices ) ) {
			return;
		}

		if ( 'classic' !== $mode ) {
			$unsupported[] = new ElementorUnsupportedBackgroundCase(
				ElementorUnsupportedBackgroundCase::CODE_UNSUPPORTED_BACKGROUND_MODE,
				$document_id,
				$element_id,
				$setting_group,
				'desktop',
				$mode_key,
				'' !== $mode ? $mode : null
			);

			return;
		}

		foreach ( $devices as $device => $setting_key ) {
			if ( '' === $setting_key || ! array_key_exists( $setting_key, $settings ) || $this->is_empty_setting_value( $settings[ $setting_key ] ) ) {
				continue;
			}

			$value = $settings[ $setting_key ];

			if ( is_array( $value ) && isset( $value['id'] ) && max( 0, (int) $value['id'] ) > 0 ) {
				$supported[] = new ElementorBackgroundSource(
					$document_id,
					$element_id,
					$element_type,
					'' !== $widget_type ? $widget_type : null,
					$setting_group,
					$device,
					(int) $value['id'],
					isset( $value['url'] ) && is_string( $value['url'] ) ? $value['url'] : null,
					$setting_key
				);

				continue;
			}

			if ( is_scalar( $value ) && max( 0, (int) $value ) > 0 ) {
				$supported[] = new ElementorBackgroundSource(
					$document_id,
					$element_id,
					$element_type,
					'' !== $widget_type ? $widget_type : null,
					$setting_group,
					$device,
					(int) $value,
					null,
					$setting_key
				);

				continue;
			}

			$unsupported[] = new ElementorUnsupportedBackgroundCase(
				ElementorUnsupportedBackgroundCase::CODE_UNSUPPORTED_BACKGROUND_VALUE,
				$document_id,
				$element_id,
				$setting_group,
				$device,
				$setting_key,
				$this->value_hint( $value )
			);
		}
	}

	/**
	 * Inspect known Elementor-owned custom CSS text settings for url() tokens.
	 *
	 * @param int                                           $document_id Document ID.
	 * @param string                                        $element_id Elementor element ID.
	 * @param array<string,mixed>                           $settings Element settings.
	 * @param array<int,ElementorUnsupportedBackgroundCase> $unsupported Unsupported-case collector.
	 * @return void
	 */
	private function inspect_custom_css( int $document_id, string $element_id, array $settings, array &$unsupported ): void {
		foreach ( self::CSS_TEXT_KEYS as $setting_key ) {
			if ( ! isset( $settings[ $setting_key ] ) || ! is_string( $settings[ $setting_key ] ) ) {
				continue;
			}

			$css = trim( $settings[ $setting_key ] );

			if ( '' === $css ) {
				continue;
			}

			$found = preg_match_all( '/url\(\s*(["\']?)(.*?)\1\s*\)/i', $css, $matches );

			if ( ! is_int( $found ) || $found < 1 || ! is_array( $matches[2] ) ) {
				continue;
			}

			foreach ( array_unique( $matches[2] ) as $url ) {
				if ( ! is_string( $url ) || '' === trim( $url ) ) {
					continue;
				}

				$unsupported[] = new ElementorUnsupportedBackgroundCase(
					ElementorUnsupportedBackgroundCase::CODE_UNSUPPORTED_CSS_URL,
					$document_id,
					$element_id,
					'custom_css',
					'desktop',
					$setting_key,
					trim( $url )
				);
			}
		}
	}

	/**
	 * Whether any supported media-setting key exists with a non-empty value.
	 *
	 * @param array<string,mixed>  $settings Element settings.
	 * @param array<string,string> $devices Device-to-key map.
	 * @return bool
	 */
	private function has_any_media_setting( array $settings, array $devices ): bool {
		foreach ( $devices as $setting_key ) {
			if ( '' !== $setting_key && array_key_exists( $setting_key, $settings ) && ! $this->is_empty_setting_value( $settings[ $setting_key ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether one setting value is empty for discovery purposes.
	 *
	 * @param mixed $value Setting value.
	 * @return bool
	 */
	private function is_empty_setting_value( $value ): bool {
		if ( null === $value ) {
			return true;
		}

		if ( is_string( $value ) ) {
			return '' === trim( $value );
		}

		if ( is_array( $value ) ) {
			return array() === $value;
		}

		return false;
	}

	/**
	 * Read one normalized string setting.
	 *
	 * @param array<string,mixed> $settings Element settings.
	 * @param string              $key Setting key.
	 * @return string
	 */
	private function normalized_string_setting( array $settings, string $key ): string {
		if ( '' === $key || ! array_key_exists( $key, $settings ) || ! is_scalar( $settings[ $key ] ) ) {
			return '';
		}

		return strtolower( trim( (string) $settings[ $key ] ) );
	}

	/**
	 * Build one safe value hint for unsupported cases.
	 *
	 * @param mixed $value Raw setting value.
	 * @return string|null
	 */
	private function value_hint( $value ): ?string {
		if ( is_string( $value ) ) {
			$value = trim( $value );

			return '' === $value ? null : $value;
		}

		if ( is_array( $value ) && isset( $value['url'] ) && is_string( $value['url'] ) ) {
			$url = trim( $value['url'] );

			return '' === $url ? null : $url;
		}

		if ( is_array( $value ) && isset( $value['id'] ) ) {
			return 'attachment_id:' . max( 0, (int) $value['id'] );
		}

		return is_scalar( $value ) ? trim( (string) $value ) : null;
	}
}
