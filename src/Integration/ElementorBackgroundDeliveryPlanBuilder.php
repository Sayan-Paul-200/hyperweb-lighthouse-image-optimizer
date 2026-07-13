<?php
/**
 * Elementor background delivery plan builder.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration;

use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifest;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifestSanitizer;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeRepository;
use HyperWeb\LighthouseImageOptimizer\Delivery\DerivativeUrlRequest;
use HyperWeb\LighthouseImageOptimizer\Delivery\DerivativeUrlResolver;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsRepositoryInterface;

/**
 * Resolves safe document-scoped background delivery targets shared by CSS and preload behavior.
 */
final class ElementorBackgroundDeliveryPlanBuilder {

	/**
	 * Discovery service.
	 *
	 * @var ElementorBackgroundDiscovery
	 */
	private $discovery;

	/**
	 * Derivative repository.
	 *
	 * @var DerivativeRepository
	 */
	private $repository;

	/**
	 * Derivative URL resolver.
	 *
	 * @var DerivativeUrlResolver
	 */
	private $resolver;

	/**
	 * Settings repository.
	 *
	 * @var SettingsRepositoryInterface
	 */
	private $settings;

	/**
	 * Path sanitizer.
	 *
	 * @var DerivativeManifestSanitizer
	 */
	private $sanitizer;

	/**
	 * Optional uploads-data provider.
	 *
	 * @var callable|null
	 */
	private $uploads_provider;

	/**
	 * Create builder.
	 *
	 * @param ElementorBackgroundDiscovery $discovery Discovery service.
	 * @param DerivativeRepository         $repository Derivative repository.
	 * @param DerivativeUrlResolver        $resolver Derivative URL resolver.
	 * @param SettingsRepositoryInterface  $settings Settings repository.
	 * @param DerivativeManifestSanitizer  $sanitizer Path sanitizer.
	 * @param callable|null                $uploads_provider Optional uploads provider for tests.
	 */
	public function __construct(
		ElementorBackgroundDiscovery $discovery,
		DerivativeRepository $repository,
		DerivativeUrlResolver $resolver,
		SettingsRepositoryInterface $settings,
		DerivativeManifestSanitizer $sanitizer,
		?callable $uploads_provider = null
	) {
		$this->discovery        = $discovery;
		$this->repository       = $repository;
		$this->resolver         = $resolver;
		$this->settings         = $settings;
		$this->sanitizer        = $sanitizer;
		$this->uploads_provider = $uploads_provider;
	}

	/**
	 * Build safe delivery plans for one document.
	 *
	 * @param int                                   $document_id Document ID.
	 * @param ElementorBackgroundBreakpointMap|null $breakpoint_map Current reliable breakpoint map.
	 * @return ElementorBackgroundDeliveryPlanResult
	 */
	public function build( int $document_id, ?ElementorBackgroundBreakpointMap $breakpoint_map = null ): ElementorBackgroundDeliveryPlanResult {
		$document_id      = max( 0, $document_id );
		$discovery_result = $this->discovery->discover( $document_id );
		$sources          = $discovery_result->supported_sources();

		if ( array() === $sources ) {
			return new ElementorBackgroundDeliveryPlanResult( $document_id, false, false, array() );
		}

		$plans                  = array();
		$breakpoint_map_missing = false;

		foreach ( $this->group_sources( $sources ) as $group ) {
			$plan = $this->build_plan( $document_id, $group, $breakpoint_map );

			if ( ! $plan instanceof ElementorBackgroundDeliveryPlan ) {
				continue;
			}

			$plans[ $plan->key() ] = $plan;

			if ( $plan->breakpoint_map_missing() ) {
				$breakpoint_map_missing = true;
			}
		}

		return new ElementorBackgroundDeliveryPlanResult( $document_id, true, $breakpoint_map_missing, $plans );
	}

	/**
	 * Group supported background sources by selector scope.
	 *
	 * @param array<int,ElementorBackgroundSource> $sources Sources.
	 * @return array<int,array<string,mixed>>
	 */
	private function group_sources( array $sources ): array {
		$grouped = array();

		foreach ( $sources as $source ) {
			$data       = $source->to_array();
			$element_id = isset( $data['element_id'] ) ? trim( (string) $data['element_id'] ) : '';
			$group      = isset( $data['setting_group'] ) ? trim( (string) $data['setting_group'] ) : '';
			$device     = isset( $data['device'] ) ? trim( (string) $data['device'] ) : 'desktop';

			if ( '' === $element_id || '' === $group ) {
				continue;
			}

			$key = $element_id . '|' . $group;

			if ( ! isset( $grouped[ $key ] ) ) {
				$grouped[ $key ] = array(
					'element_id'    => $element_id,
					'setting_group' => $group,
					'devices'       => array(),
				);
			}

			$grouped[ $key ]['devices'][ $device ] = $data;
		}

		return array_values( $grouped );
	}

	/**
	 * Build one selector-scoped plan.
	 *
	 * @param int                                   $document_id Document ID.
	 * @param array<string,mixed>                   $group Grouped source data.
	 * @param ElementorBackgroundBreakpointMap|null $breakpoint_map Current reliable breakpoint map.
	 * @return ElementorBackgroundDeliveryPlan|null
	 */
	private function build_plan( int $document_id, array $group, ?ElementorBackgroundBreakpointMap $breakpoint_map ): ?ElementorBackgroundDeliveryPlan {
		$devices       = isset( $group['devices'] ) && is_array( $group['devices'] ) ? $group['devices'] : array();
		$element_id    = isset( $group['element_id'] ) ? trim( (string) $group['element_id'] ) : '';
		$setting_group = isset( $group['setting_group'] ) ? trim( (string) $group['setting_group'] ) : '';
		$selector      = $this->selector_for_group( $element_id, $setting_group );

		if ( '' === $selector ) {
			return null;
		}

		$responsive = isset( $devices['tablet'] ) || isset( $devices['mobile'] );

		if ( $responsive && ( ! $breakpoint_map instanceof ElementorBackgroundBreakpointMap || ! $breakpoint_map->is_complete() ) ) {
			return new ElementorBackgroundDeliveryPlan(
				$document_id,
				$element_id,
				$setting_group,
				$selector,
				array(),
				true,
				true
			);
		}

		$variants = array();
		$queries  = array(
			'desktop' => $responsive && $breakpoint_map instanceof ElementorBackgroundBreakpointMap ? $breakpoint_map->desktop_query() : null,
			'tablet'  => $responsive && $breakpoint_map instanceof ElementorBackgroundBreakpointMap ? $breakpoint_map->tablet_query() : null,
			'mobile'  => $responsive && $breakpoint_map instanceof ElementorBackgroundBreakpointMap ? $breakpoint_map->mobile_query() : null,
		);

		foreach ( array( 'desktop', 'tablet', 'mobile' ) as $device ) {
			if ( ! isset( $devices[ $device ] ) || ! is_array( $devices[ $device ] ) ) {
				continue;
			}

			$variant = $this->variant_for_source(
				$document_id,
				$devices[ $device ],
				$responsive ? $queries[ $device ] : null
			);

			if ( $variant instanceof ElementorBackgroundDeliveryVariant ) {
				$variants[ $device ] = $variant;
			}
		}

		return new ElementorBackgroundDeliveryPlan(
			$document_id,
			$element_id,
			$setting_group,
			$selector,
			$variants,
			$responsive,
			false
		);
	}

	/**
	 * Resolve one safe variant from a supported source mapping.
	 *
	 * @param int                 $document_id Document ID.
	 * @param array<string,mixed> $source Supported source data.
	 * @param string|null         $media_query Optional media query.
	 * @return ElementorBackgroundDeliveryVariant|null
	 */
	private function variant_for_source( int $document_id, array $source, ?string $media_query = null ): ?ElementorBackgroundDeliveryVariant {
		unset( $document_id );

		$attachment_id = isset( $source['attachment_id'] ) ? max( 0, (int) $source['attachment_id'] ) : 0;
		$source_url    = isset( $source['url'] ) && is_string( $source['url'] ) ? $this->normalize_url( $source['url'] ) : '';
		$device        = isset( $source['device'] ) ? (string) $source['device'] : 'desktop';

		if ( 1 > $attachment_id || '' === $source_url ) {
			return null;
		}

		$repository_result = $this->repository->read( $attachment_id );
		$manifest          = $repository_result->manifest();

		if ( ! $manifest instanceof DerivativeManifest || ! $manifest->has_derivatives() ) {
			return null;
		}

		$match = $this->matching_manifest_source( $manifest, $source_url );

		if ( ! is_array( $match ) ) {
			return null;
		}

		$formats = $this->preferred_format_urls(
			$attachment_id,
			(string) $match['size_name'],
			isset( $match['formats'] ) && is_array( $match['formats'] ) ? $match['formats'] : array()
		);

		if ( array() === $formats ) {
			return null;
		}

		return new ElementorBackgroundDeliveryVariant(
			$device,
			$source_url,
			isset( $match['source_mime'] ) ? (string) $match['source_mime'] : '',
			$formats,
			$media_query
		);
	}

	/**
	 * Find the manifest source entry matching the configured local background URL.
	 *
	 * @param DerivativeManifest $manifest Manifest.
	 * @param string             $source_url Configured local source URL.
	 * @return array<string,mixed>|null
	 */
	private function matching_manifest_source( DerivativeManifest $manifest, string $source_url ): ?array {
		$base_url = $this->uploads_base_url();

		if ( '' === $base_url ) {
			return null;
		}

		foreach ( $manifest->sizes() as $size_name => $size ) {
			if ( ! is_array( $size ) || ! isset( $size['source'] ) || ! is_array( $size['source'] ) ) {
				continue;
			}

			$relative_source = isset( $size['source']['file'] ) && is_scalar( $size['source']['file'] )
				? $this->sanitizer->safe_relative_path( (string) $size['source']['file'] )
				: '';

			if ( '' === $relative_source ) {
				continue;
			}

			$candidate_url = $this->normalize_url( rtrim( $base_url, '/' ) . '/' . ltrim( $relative_source, '/' ) );

			if ( '' === $candidate_url || $candidate_url !== $source_url ) {
				continue;
			}

			return array(
				'size_name'   => (string) $size_name,
				'source_mime' => isset( $size['source']['mime'] ) && is_string( $size['source']['mime'] ) ? strtolower( trim( $size['source']['mime'] ) ) : '',
				'formats'     => isset( $size['formats'] ) && is_array( $size['formats'] ) ? $size['formats'] : array(),
			);
		}

		return null;
	}

	/**
	 * Resolve preferred ready derivative URLs in settings order.
	 *
	 * @param int                               $attachment_id Attachment ID.
	 * @param string                            $size_name Size name.
	 * @param array<string,array<string,mixed>> $formats Stored format entries.
	 * @return array<int,array<string,string>>
	 */
	private function preferred_format_urls( int $attachment_id, string $size_name, array $formats ): array {
		$resolved = array();

		foreach ( $this->settings->format_preference() as $format ) {
			$format = strtolower( trim( $format ) );

			if ( ! isset( $formats[ $format ] ) ) {
				continue;
			}

			$relative_file = isset( $formats[ $format ]['file'] ) && is_scalar( $formats[ $format ]['file'] )
				? $this->sanitizer->safe_relative_path( (string) $formats[ $format ]['file'] )
				: '';

			if ( '' === $relative_file ) {
				continue;
			}

			$resolution = $this->resolver->resolve(
				new DerivativeUrlRequest( $relative_file, $attachment_id, $size_name, $format )
			);

			if ( ! $resolution->is_successful() || null === $resolution->url() ) {
				continue;
			}

			$mime = isset( $formats[ $format ]['mime'] ) && is_string( $formats[ $format ]['mime'] )
				? strtolower( trim( $formats[ $format ]['mime'] ) )
				: $this->sanitizer->expected_mime( $format );

			$resolved[] = array(
				'format' => $format,
				'mime'   => $mime,
				'url'    => $resolution->url(),
			);
		}

		return $resolved;
	}

	/**
	 * Build one supported selector.
	 *
	 * @param string $element_id Elementor element ID.
	 * @param string $setting_group Setting group.
	 * @return string
	 */
	private function selector_for_group( string $element_id, string $setting_group ): string {
		$element_id = trim( $element_id );

		if ( '' === $element_id || ! preg_match( '/^[A-Za-z0-9_-]+$/', $element_id ) ) {
			return '';
		}

		if ( 'background_overlay' === $setting_group ) {
			return '.elementor-element.elementor-element-' . $element_id . ' > .elementor-background-overlay';
		}

		return '.elementor-element.elementor-element-' . $element_id;
	}

	/**
	 * Normalize one URL.
	 *
	 * @param string $url Raw URL.
	 * @return string
	 */
	private function normalize_url( string $url ): string {
		$url = trim( $url );

		return false !== filter_var( $url, FILTER_VALIDATE_URL ) ? $url : '';
	}

	/**
	 * Read uploads base URL.
	 *
	 * @return string
	 */
	private function uploads_base_url(): string {
		$uploads = null;

		if ( null !== $this->uploads_provider ) {
			$uploads = call_user_func( $this->uploads_provider );
		} elseif ( function_exists( 'wp_upload_dir' ) ) {
			$uploads = \wp_upload_dir( null, false );
		}

		if ( ! is_array( $uploads ) || ! isset( $uploads['baseurl'] ) || ! is_string( $uploads['baseurl'] ) ) {
			return '';
		}

		$base_url = trim( $uploads['baseurl'] );

		if ( '' === $base_url || ( isset( $uploads['error'] ) && is_string( $uploads['error'] ) && '' !== trim( $uploads['error'] ) ) ) {
			return '';
		}

		return rtrim( $base_url, '/' );
	}
}
