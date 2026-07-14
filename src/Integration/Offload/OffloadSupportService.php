<?php
/**
 * Offload support service.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration\Offload;

use HyperWeb\LighthouseImageOptimizer\Integration\Multisite\SiteContextRuntimeInterface;

/**
 * Selects the active offload adapter and reports current support state.
 */
final class OffloadSupportService {

	/**
	 * Adapter.
	 *
	 * @var WpOffloadMediaAdapter
	 */
	private $adapter;

	/**
	 * Site-context runtime.
	 *
	 * @var SiteContextRuntimeInterface|null
	 */
	private $site_context;

	/**
	 * Cached site support.
	 *
	 * @var OffloadSiteSupport|null
	 */
	private $site_support;

	/**
	 * Attachment support cache.
	 *
	 * @var array<int,OffloadAttachmentSupport>
	 */
	private $attachment_support = array();

	/**
	 * Site ID that owns the current cache.
	 *
	 * @var int|null
	 */
	private $resolved_site_id;

	/**
	 * Create service.
	 *
	 * @param WpOffloadMediaAdapter            $adapter Adapter.
	 * @param SiteContextRuntimeInterface|null $site_context Optional site-context runtime.
	 */
	public function __construct( WpOffloadMediaAdapter $adapter, ?SiteContextRuntimeInterface $site_context = null ) {
		$this->adapter          = $adapter;
		$this->site_context     = $site_context;
		$this->resolved_site_id = null;
	}

	/**
	 * Get current site support.
	 *
	 * @return OffloadSiteSupport
	 */
	public function site_support(): OffloadSiteSupport {
		$this->sync_site_scope();

		if ( ! $this->site_support instanceof OffloadSiteSupport ) {
			$this->site_support = $this->adapter->site_support();
		}

		return $this->site_support;
	}

	/**
	 * Get current attachment support facts.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return OffloadAttachmentSupport
	 */
	public function attachment_support( int $attachment_id ): OffloadAttachmentSupport {
		$this->sync_site_scope();

		$attachment_id = max( 0, $attachment_id );

		if ( ! isset( $this->attachment_support[ $attachment_id ] ) ) {
			$this->attachment_support[ $attachment_id ] = $this->adapter->attachment_support( $attachment_id, $this->site_support() );
		}

		return $this->attachment_support[ $attachment_id ];
	}

	/**
	 * Whether the current site blocks bulk/offloaded operations.
	 *
	 * @return bool
	 */
	public function blocks_site_operations(): bool {
		return $this->site_support()->blocks_operations();
	}

	/**
	 * Reset cached site facts after a site switch.
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

		$this->resolved_site_id   = $current_site_id;
		$this->site_support       = null;
		$this->attachment_support = array();
	}
}
