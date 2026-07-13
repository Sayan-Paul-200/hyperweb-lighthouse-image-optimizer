<?php
/**
 * Media Library bootstrap configuration.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\MediaLibrary;

/**
 * Carries the client bootstrap payload for Media Library controls.
 */
final class MediaLibraryBootstrapConfig {

	/**
	 * REST root URL.
	 *
	 * @var string
	 */
	private $rest_root;

	/**
	 * REST nonce.
	 *
	 * @var string
	 */
	private $rest_nonce;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Whether automatic optimization is enabled.
	 *
	 * @var bool
	 */
	private $automatic_optimization;

	/**
	 * Whether Media Library controls are enabled.
	 *
	 * @var bool
	 */
	private $controls_enabled;

	/**
	 * Whether exclusion is allowed.
	 *
	 * @var bool
	 */
	private $exclusion_allowed;

	/**
	 * Create the bootstrap payload.
	 *
	 * @param string $rest_root REST root URL.
	 * @param string $rest_nonce REST nonce.
	 * @param string $version Plugin version.
	 * @param bool   $automatic_optimization Whether automatic optimization is enabled.
	 * @param bool   $controls_enabled Whether Media Library controls are enabled.
	 * @param bool   $exclusion_allowed Whether exclusion is allowed.
	 */
	public function __construct(
		string $rest_root,
		string $rest_nonce,
		string $version,
		bool $automatic_optimization,
		bool $controls_enabled,
		bool $exclusion_allowed
	) {
		$this->rest_root              = $rest_root;
		$this->rest_nonce             = $rest_nonce;
		$this->version                = $version;
		$this->automatic_optimization = $automatic_optimization;
		$this->controls_enabled       = $controls_enabled;
		$this->exclusion_allowed      = $exclusion_allowed;
	}

	/**
	 * Convert the payload to an array.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'version'  => $this->version,
			'rest'     => array(
				'root'  => $this->rest_root,
				'nonce' => $this->rest_nonce,
			),
			'settings' => array(
				'automaticOptimization'  => $this->automatic_optimization,
				'mediaLibraryControls'   => $this->controls_enabled,
				'allowAttachmentExclusion' => $this->exclusion_allowed,
			),
			'polling'  => array(
				'activeMs' => 5000,
			),
			'labels'   => array(
				'states'  => array(
					'unprocessed' => $this->translate( 'Unprocessed' ),
					'queued'      => $this->translate( 'Queued' ),
					'processing'  => $this->translate( 'Processing' ),
					'partial'     => $this->translate( 'Partially optimized' ),
					'optimized'   => $this->translate( 'Optimized' ),
					'failed'      => $this->translate( 'Failed' ),
					'stale'       => $this->translate( 'Stale' ),
					'skipped'     => $this->translate( 'Skipped' ),
					'excluded'    => $this->translate( 'Excluded' ),
				),
				'actions' => array(
					'optimize'     => $this->translate( 'Optimize Now' ),
					'retry'        => $this->translate( 'Retry' ),
					'reoptimize'   => $this->translate( 'Re-optimize' ),
					'reconcile'    => $this->translate( 'Reconcile Files' ),
					'exclude'      => $this->translate( 'Exclude from Optimization' ),
					'include'      => $this->translate( 'Include in Optimization' ),
					'view-details' => $this->translate( 'View Details' ),
				),
			),
			'strings'  => array(
				'bootstrapError'  => $this->translate( 'The Media Library controls could not initialize on this screen.' ),
				'requestError'    => $this->translate( 'A Media Library request failed before it could complete.' ),
				'detailsLoading'  => $this->translate( 'Loading attachment details...' ),
				'detailsEmpty'    => $this->translate( 'No attachment optimization details are available yet.' ),
				'reoptimizeConfirm' => $this->translate( 'Re-optimize this attachment using the current quality settings?' ),
				'exclusionNotice' => $this->translate( 'Exclusion prevents future queueing but does not cancel work that is already queued.' ),
				'queuedNotice'    => $this->translate( 'Attachment work has been queued.' ),
				'includeNotice'   => $this->translate( 'Attachment included in optimization.' ),
				'excludeNotice'   => $this->translate( 'Attachment excluded from future optimization queueing.' ),
			),
			'selectors' => array(
				'noticeContainerId' => 'hwlio-media-notices',
				'politeRegionId'    => 'hwlio-media-live-polite',
				'assertiveRegionId' => 'hwlio-media-live-assertive',
			),
		);
	}

	/**
	 * Translate one plugin-owned string.
	 *
	 * @param string $text Raw text.
	 * @return string
	 */
	private function translate( string $text ): string {
		if ( function_exists( '__' ) ) {
			return __( $text, 'hyperweb-lighthouse-image-optimizer' );
		}

		return $text;
	}
}
