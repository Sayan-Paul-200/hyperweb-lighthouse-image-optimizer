<?php
/**
 * Critical-image registry.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Delivery;

use HyperWeb\LighthouseImageOptimizer\Integration\Multisite\WordPressSiteContextRuntime;
use HyperWeb\LighthouseImageOptimizer\Integration\Multisite\SiteContextRuntimeInterface;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsRepository;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsRepositoryInterface;

/**
 * Resolves one normalized critical-image selection for the current request.
 */
final class CriticalImageRegistry {

	/**
	 * Runtime seam.
	 *
	 * @var AttachmentImageRuntimeInterface
	 */
	private $runtime;

	/**
	 * Settings repository.
	 *
	 * @var SettingsRepositoryInterface
	 */
	private $settings;

	/**
	 * Post meta store.
	 *
	 * @var CriticalImagePostMetaStoreInterface
	 */
	private $post_meta;

	/**
	 * Optional site-context runtime.
	 *
	 * @var SiteContextRuntimeInterface|null
	 */
	private $site_context;

	/**
	 * Cached request selection.
	 *
	 * @var CriticalImageSelection|null
	 */
	private $resolved;

	/**
	 * Site ID that owns the current cached selection.
	 *
	 * @var int|null
	 */
	private $resolved_site_id;

	/**
	 * Build a WordPress-backed registry.
	 *
	 * @return self
	 */
	public static function for_wordpress(): self {
		return new self(
			new WordPressAttachmentImageRuntime(),
			SettingsRepository::for_wordpress(),
			new WordPressCriticalImagePostMetaStore(),
			new WordPressSiteContextRuntime()
		);
	}

	/**
	 * Create registry.
	 *
	 * @param AttachmentImageRuntimeInterface     $runtime Runtime seam.
	 * @param SettingsRepositoryInterface         $settings Settings repository.
	 * @param CriticalImagePostMetaStoreInterface $post_meta Post meta store.
	 * @param SiteContextRuntimeInterface|null    $site_context Optional site-context runtime.
	 */
	public function __construct(
		AttachmentImageRuntimeInterface $runtime,
		SettingsRepositoryInterface $settings,
		CriticalImagePostMetaStoreInterface $post_meta,
		?SiteContextRuntimeInterface $site_context = null
	) {
		$this->runtime          = $runtime;
		$this->settings         = $settings;
		$this->post_meta        = $post_meta;
		$this->site_context     = $site_context;
		$this->resolved         = null;
		$this->resolved_site_id = null;
	}

	/**
	 * Resolve the current request selection.
	 *
	 * @return CriticalImageSelection
	 */
	public function resolve(): CriticalImageSelection {
		$this->sync_site_scope();

		if ( $this->resolved instanceof CriticalImageSelection ) {
			return $this->resolved;
		}

		$context    = $this->request_context();
		$candidates = $this->normalize_candidates( $this->built_in_candidates( $context ) );
		$candidates = $this->normalize_candidates(
			$this->apply_filter( 'hwlio_critical_image_candidates', $candidates, $context )
		);
		$selection  = $this->normalize_candidates(
			$this->apply_filter( 'hwlio_critical_image_selection', $candidates, $context )
		);

		$this->resolved = new CriticalImageSelection(
			$selection['primary_attachment_id'],
			$selection['critical_attachment_ids'],
			$selection['critical_urls'],
			$selection['preload_attachment_id']
		);

		return $this->resolved;
	}

	/**
	 * Reset cached selection after a site switch.
	 *
	 * @return void
	 */
	private function sync_site_scope(): void {
		if ( ! $this->site_context instanceof SiteContextRuntimeInterface ) {
			return;
		}

		$current_site_id = $this->site_context->current_site_id();

		if ( $this->resolved_site_id === $current_site_id ) {
			return;
		}

		$this->resolved_site_id = $current_site_id;
		$this->resolved         = null;
	}

	/**
	 * Build the built-in candidates for the current request.
	 *
	 * @param array<string,mixed> $context Request context.
	 * @return array<string,mixed>
	 */
	private function built_in_candidates( array $context ): array {
		$primary_attachment_id   = null;
		$critical_attachment_ids = array();

		$post_id   = (int) $context['post_id'];
		$post_type = (string) $context['post_type'];

		if ( $post_id > 0 && in_array( $post_type, array( 'post', 'page' ), true ) ) {
			$selected_id = $this->post_meta->get_critical_image_id( $post_id );

			if ( $selected_id > 0 && $this->runtime->attachment_is_image( $selected_id ) ) {
				$primary_attachment_id     = $selected_id;
				$critical_attachment_ids[] = $selected_id;
			}
		}

		if ( $this->settings->critical_logo_enabled() ) {
			$logo_attachment_id = (int) $context['custom_logo_attachment_id'];

			if (
				$logo_attachment_id > 0
				&& $logo_attachment_id !== $primary_attachment_id
				&& $this->runtime->attachment_is_image( $logo_attachment_id )
			) {
				$critical_attachment_ids[] = $logo_attachment_id;
			}
		}

		return array(
			'primary_attachment_id'   => $primary_attachment_id,
			'critical_attachment_ids' => $critical_attachment_ids,
			'critical_urls'           => array(),
			'preload_attachment_id'   => $primary_attachment_id,
		);
	}

	/**
	 * Normalize one candidate payload.
	 *
	 * @param mixed $payload Candidate payload.
	 * @return array{primary_attachment_id:int|null,critical_attachment_ids:int[],critical_urls:string[],preload_attachment_id:int|null}
	 */
	private function normalize_candidates( $payload ): array {
		if ( ! is_array( $payload ) ) {
			$payload = array();
		}

		$primary = null;

		if ( isset( $payload['primary_attachment_id'] ) ) {
			$maybe_primary = max( 0, (int) $payload['primary_attachment_id'] );

			if ( $maybe_primary > 0 && $this->runtime->attachment_is_image( $maybe_primary ) ) {
				$primary = $maybe_primary;
			}
		}

		$attachment_ids = array();

		if ( isset( $payload['critical_attachment_ids'] ) && is_array( $payload['critical_attachment_ids'] ) ) {
			foreach ( $payload['critical_attachment_ids'] as $attachment_id ) {
				$attachment_id = max( 0, (int) $attachment_id );

				if ( $attachment_id > 0 && $this->runtime->attachment_is_image( $attachment_id ) ) {
					$attachment_ids[] = $attachment_id;
				}
			}
		}

		if ( null !== $primary && ! in_array( $primary, $attachment_ids, true ) ) {
			array_unshift( $attachment_ids, $primary );
		}

		$urls = array();

		if ( isset( $payload['critical_urls'] ) && is_array( $payload['critical_urls'] ) ) {
			foreach ( $payload['critical_urls'] as $url ) {
				if ( ! is_string( $url ) ) {
					continue;
				}

				$url = trim( $url );

				if ( '' === $url || false === filter_var( $url, FILTER_VALIDATE_URL ) ) {
					continue;
				}

				$urls[] = $url;
			}
		}

		$preload_attachment_id = null;

		if ( isset( $payload['preload_attachment_id'] ) ) {
			$maybe_preload = max( 0, (int) $payload['preload_attachment_id'] );

			if ( $maybe_preload > 0 && $this->runtime->attachment_is_image( $maybe_preload ) ) {
				$preload_attachment_id = $maybe_preload;
			}
		}

		return array(
			'primary_attachment_id'   => $primary,
			'critical_attachment_ids' => array_values( array_unique( $attachment_ids ) ),
			'critical_urls'           => array_values( array_unique( $urls ) ),
			'preload_attachment_id'   => $preload_attachment_id,
		);
	}

	/**
	 * Build request context for selection filters.
	 *
	 * @return array<string,mixed>
	 */
	private function request_context(): array {
		return array(
			'request_context'           => $this->runtime->request_context(),
			'post_id'                   => $this->runtime->current_singular_post_id(),
			'post_type'                 => $this->runtime->current_singular_post_type(),
			'custom_logo_attachment_id' => $this->runtime->custom_logo_attachment_id(),
		);
	}

	/**
	 * Apply one developer override filter when available.
	 *
	 * @param string              $hook Hook name.
	 * @param array<string,mixed> $payload Payload.
	 * @param array<string,mixed> $context Request context.
	 * @return mixed
	 */
	private function apply_filter( string $hook, array $payload, array $context ) {
		if ( function_exists( 'apply_filters' ) ) {
			return \apply_filters( $hook, $payload, $context );
		}

		return $payload;
	}
}
