<?php
/**
 * Derivative delete request.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration\Offload;

/**
 * Carries one remote derivative delete request.
 */
final class DerivativeDeleteRequest {

	/**
	 * Attachment ID.
	 *
	 * @var int
	 */
	private $attachment_id;

	/**
	 * Attachment support.
	 *
	 * @var OffloadAttachmentSupport
	 */
	private $support;

	/**
	 * Relative paths.
	 *
	 * @var string[]
	 */
	private $relative_paths;

	/**
	 * Reason.
	 *
	 * @var string
	 */
	private $reason;

	/**
	 * Create request.
	 *
	 * @param int                      $attachment_id Attachment ID.
	 * @param OffloadAttachmentSupport $support Attachment support.
	 * @param string[]                 $relative_paths Relative derivative paths.
	 * @param string                   $reason Reason.
	 */
	public function __construct( int $attachment_id, OffloadAttachmentSupport $support, array $relative_paths, string $reason ) {
		$this->attachment_id  = max( 0, $attachment_id );
		$this->support        = $support;
		$this->relative_paths = array_values(
			array_filter(
				array_map(
					static function ( $path ): string {
						return is_scalar( $path ) ? ltrim( str_replace( '\\', '/', trim( (string) $path ) ), '/' ) : '';
					},
					$relative_paths
				)
			)
		);
		$this->reason         = trim( $reason );
	}

	/**
	 * Get attachment ID.
	 *
	 * @return int
	 */
	public function attachment_id(): int {
		return $this->attachment_id;
	}

	/**
	 * Get attachment support.
	 *
	 * @return OffloadAttachmentSupport
	 */
	public function support(): OffloadAttachmentSupport {
		return $this->support;
	}

	/**
	 * Get relative paths.
	 *
	 * @return string[]
	 */
	public function relative_paths(): array {
		return $this->relative_paths;
	}

	/**
	 * Get reason.
	 *
	 * @return string
	 */
	public function reason(): string {
		return $this->reason;
	}
}
