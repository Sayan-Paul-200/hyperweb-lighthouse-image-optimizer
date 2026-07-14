<?php
/**
 * Conflict detector.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration\Conflict;

use HyperWeb\LighthouseImageOptimizer\Integration\Offload\OffloadSiteSupport;
use HyperWeb\LighthouseImageOptimizer\Integration\Offload\OffloadSupportService;
use HyperWeb\LighthouseImageOptimizer\Integration\Offload\WpOffloadMediaAdapter;

/**
 * Detects overlapping optimization capabilities from current-site active plugins.
 */
final class ConflictDetector {

	private const CAPABILITY_GENERATION         = 'generation';
	private const CAPABILITY_DELIVERY           = 'delivery';
	private const CAPABILITY_LAZY_LOADING       = 'lazy_loading';
	private const CAPABILITY_CDN_TRANSFORMATION = 'cdn_transformation';
	private const CAPABILITY_MEDIA_OFFLOAD      = 'media_offload';

	/**
	 * Runtime seam.
	 *
	 * @var ConflictRuntimeInterface
	 */
	private $runtime;

	/**
	 * Offload support service.
	 *
	 * @var OffloadSupportService|null
	 */
	private $offload;

	/**
	 * Create detector.
	 *
	 * @param ConflictRuntimeInterface   $runtime Runtime seam.
	 * @param OffloadSupportService|null $offload Offload support service.
	 */
	public function __construct( ConflictRuntimeInterface $runtime, ?OffloadSupportService $offload = null ) {
		$this->runtime  = $runtime;
		$this->offload  = $offload;
	}

	/**
	 * Build a WordPress-backed detector.
	 *
	 * @return self
	 */
	public static function for_wordpress(): self {
		return new self( new WordPressConflictRuntime() );
	}

	/**
	 * Detect overlapping current-site capabilities.
	 *
	 * @return ConflictReport
	 */
	public function detect(): ConflictReport {
		$active_plugins = array_values(
			array_unique(
				array_merge(
					$this->runtime->active_plugin_basenames(),
					$this->runtime->network_active_plugin_basenames()
				)
			)
		);
		$active_lookup  = array_fill_keys( $active_plugins, true );
		$results        = array();

		foreach ( $this->signature_matrix() as $capability => $plugins ) {
			$matched = array();

			foreach ( $plugins as $basename => $name ) {
				if ( isset( $active_lookup[ $basename ] ) ) {
					$matched[] = $name;
				}
			}

			if ( array() === $matched ) {
				continue;
			}

			if ( self::CAPABILITY_MEDIA_OFFLOAD === $capability ) {
				$offload_result = $this->media_offload_result( $matched, $active_lookup );

				if ( null === $offload_result ) {
					continue;
				}

				$results[] = $offload_result;
				continue;
			}

			$results[] = new ConflictResult(
				'overlap_' . $capability,
				ConflictResult::SEVERITY_WARNING,
				$capability,
				$this->capability_label( $capability ),
				$this->capability_message( $capability ),
				$matched,
				$this->recommended_setting_keys( $capability )
			);
		}

		return new ConflictReport( $results );
	}

	/**
	 * Build the media-offload conflict result with supported WP Offload Media refinement.
	 *
	 * @param string[]          $matched Matched plugin names.
	 * @param array<string,bool> $active_lookup Active plugin lookup.
	 * @return ConflictResult|null
	 */
	private function media_offload_result( array $matched, array $active_lookup ): ?ConflictResult {
		$wp_offload_active = isset( $active_lookup[ WpOffloadMediaAdapter::PLUGIN_BASENAME ] );

		if ( ! $wp_offload_active || null === $this->offload ) {
			return new ConflictResult(
				'overlap_' . self::CAPABILITY_MEDIA_OFFLOAD,
				ConflictResult::SEVERITY_WARNING,
				self::CAPABILITY_MEDIA_OFFLOAD,
				$this->capability_label( self::CAPABILITY_MEDIA_OFFLOAD ),
				$this->capability_message( self::CAPABILITY_MEDIA_OFFLOAD ),
				$matched,
				$this->recommended_setting_keys( self::CAPABILITY_MEDIA_OFFLOAD )
			);
		}

		$site = $this->offload->site_support();

		if ( $site->supported() ) {
			$others = array_values( array_diff( $matched, array( WpOffloadMediaAdapter::PLUGIN_NAME ) ) );

			if ( array() === $others ) {
				return null;
			}

			return new ConflictResult(
				'overlap_' . self::CAPABILITY_MEDIA_OFFLOAD,
				ConflictResult::SEVERITY_WARNING,
				self::CAPABILITY_MEDIA_OFFLOAD,
				$this->capability_label( self::CAPABILITY_MEDIA_OFFLOAD ),
				$this->capability_message( self::CAPABILITY_MEDIA_OFFLOAD ),
				$others,
				$this->recommended_setting_keys( self::CAPABILITY_MEDIA_OFFLOAD )
			);
		}

		if ( OffloadSiteSupport::CODE_UNSUPPORTED === $site->code() ) {
			return new ConflictResult(
				'overlap_media_offload_unsupported',
				ConflictResult::SEVERITY_WARNING,
				self::CAPABILITY_MEDIA_OFFLOAD,
				'Unsupported media offload state',
				$site->message(),
				$matched,
				$this->recommended_setting_keys( self::CAPABILITY_MEDIA_OFFLOAD )
			);
		}

		return new ConflictResult(
			'overlap_' . self::CAPABILITY_MEDIA_OFFLOAD,
			ConflictResult::SEVERITY_WARNING,
			self::CAPABILITY_MEDIA_OFFLOAD,
			$this->capability_label( self::CAPABILITY_MEDIA_OFFLOAD ),
			$this->capability_message( self::CAPABILITY_MEDIA_OFFLOAD ),
			$matched,
			$this->recommended_setting_keys( self::CAPABILITY_MEDIA_OFFLOAD )
		);
	}

	/**
	 * Get the curated capability signature matrix.
	 *
	 * @return array<string,array<string,string>>
	 */
	private function signature_matrix(): array {
		return array(
			self::CAPABILITY_GENERATION         => array(
				'ewww-image-optimizer/ewww-image-optimizer.php' => 'EWWW Image Optimizer',
				'imagify/imagify.php'           => 'Imagify',
				'shortpixel-image-optimiser/wp-shortpixel.php' => 'ShortPixel Image Optimizer',
				'webp-express/webp-express.php' => 'WebP Express',
				'webp-converter-for-media/webp-converter-for-media.php' => 'Converter for Media',
				'wp-smushit/wp-smush.php'       => 'Smush',
				'optimole-wp/optimole-wp.php'   => 'Optimole',
			),
			self::CAPABILITY_DELIVERY           => array(
				'webp-express/webp-express.php' => 'WebP Express',
				'webp-converter-for-media/webp-converter-for-media.php' => 'Converter for Media',
				'shortpixel-adaptive-images/shortpixel-adaptive-images.php' => 'ShortPixel Adaptive Images',
				'optimole-wp/optimole-wp.php'   => 'Optimole',
			),
			self::CAPABILITY_LAZY_LOADING       => array(
				'a3-lazy-load/a3-lazy-load.php'         => 'a3 Lazy Load',
				'rocket-lazy-load/rocket-lazy-load.php' => 'Rocket Lazy Load',
				'wp-rocket/wp-rocket.php'               => 'WP Rocket',
				'autoptimize/autoptimize.php'           => 'Autoptimize',
				'siteground-optimizer/siteground-optimizer.php' => 'SiteGround Optimizer',
				'jetpack/jetpack.php'                   => 'Jetpack',
			),
			self::CAPABILITY_CDN_TRANSFORMATION => array(
				'shortpixel-adaptive-images/shortpixel-adaptive-images.php' => 'ShortPixel Adaptive Images',
				'optimole-wp/optimole-wp.php' => 'Optimole',
				'jetpack/jetpack.php'         => 'Jetpack',
			),
			self::CAPABILITY_MEDIA_OFFLOAD      => array(
				'amazon-s3-and-cloudfront/wordpress-s3.php' => 'WP Offload Media',
				'wp-stateless/wp-stateless.php' => 'WP Stateless',
				'media-cloud/media-cloud.php'   => 'Media Cloud',
			),
		);
	}

	/**
	 * Get a user-facing capability label.
	 *
	 * @param string $capability Capability key.
	 * @return string
	 */
	private function capability_label( string $capability ): string {
		switch ( $capability ) {
			case self::CAPABILITY_GENERATION:
				return 'Overlapping generation features';
			case self::CAPABILITY_DELIVERY:
				return 'Overlapping frontend delivery features';
			case self::CAPABILITY_LAZY_LOADING:
				return 'Overlapping lazy-loading features';
			case self::CAPABILITY_CDN_TRANSFORMATION:
				return 'Overlapping CDN image transformation features';
			case self::CAPABILITY_MEDIA_OFFLOAD:
				return 'Overlapping media offload features';
			default:
				return 'Compatibility warning';
		}
	}

	/**
	 * Get a capability-first warning message.
	 *
	 * @param string $capability Capability key.
	 * @return string
	 */
	private function capability_message( string $capability ): string {
		switch ( $capability ) {
			case self::CAPABILITY_GENERATION:
				return 'Overlapping modern-image generation features are active on this site. Disable plugin-owned generation if another optimizer already manages derivative generation.';
			case self::CAPABILITY_DELIVERY:
				return 'Overlapping frontend modern-format delivery features are active on this site. Disable plugin-owned delivery if another plugin already rewrites image markup or URLs.';
			case self::CAPABILITY_LAZY_LOADING:
				return 'Overlapping lazy-loading or loading-priority features are active on this site. Disable plugin-owned loading overrides or preloads if another plugin already manages them.';
			case self::CAPABILITY_CDN_TRANSFORMATION:
				return 'Overlapping CDN image transformation features are active on this site. Disable plugin-owned delivery modules when a CDN layer already transforms or serves image variants.';
			case self::CAPABILITY_MEDIA_OFFLOAD:
				return 'Overlapping media offload features are active on this site. Disable plugin-owned generation or delivery modules until offload compatibility is configured.';
			default:
				return 'An overlapping image-optimization capability is active on this site.';
		}
	}

	/**
	 * Get recommended plugin-owned settings to disable for one capability.
	 *
	 * @param string $capability Capability key.
	 * @return string[]
	 */
	private function recommended_setting_keys( string $capability ): array {
		switch ( $capability ) {
			case self::CAPABILITY_GENERATION:
				return array( 'automatic_optimization' );
			case self::CAPABILITY_DELIVERY:
				return array( 'delivery_enabled' );
			case self::CAPABILITY_LAZY_LOADING:
				return array(
					'loading_attribute_overrides_enabled',
					'responsive_preload_enabled',
					'critical_background_preload_enabled',
				);
			case self::CAPABILITY_CDN_TRANSFORMATION:
				return array(
					'delivery_enabled',
					'elementor_background_delivery_enabled',
					'responsive_preload_enabled',
					'critical_background_preload_enabled',
				);
			case self::CAPABILITY_MEDIA_OFFLOAD:
				return array(
					'automatic_optimization',
					'delivery_enabled',
					'elementor_background_delivery_enabled',
				);
			default:
				return array();
		}
	}
}
