<?php
/**
 * Local source resolution request.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration\Offload;

/**
 * Carries one authoritative offloaded source that may need a temporary local copy.
 */
final class LocalSourceResolutionRequest {

	/**
	 * Attachment ID.
	 *
	 * @var int
	 */
	private $attachment_id;

	/**
	 * Size name.
	 *
	 * @var string
	 */
	private $size_name;

	/**
	 * Source role.
	 *
	 * @var string
	 */
	private $role;

	/**
	 * Relative path.
	 *
	 * @var string
	 */
	private $relative_path;

	/**
	 * Remote URL.
	 *
	 * @var string
	 */
	private $remote_url;

	/**
	 * Width hint.
	 *
	 * @var int|null
	 */
	private $width;

	/**
	 * Height hint.
	 *
	 * @var int|null
	 */
	private $height;

	/**
	 * Attachment support.
	 *
	 * @var OffloadAttachmentSupport
	 */
	private $support;

	/**
	 * Create request.
	 *
	 * @param int                      $attachment_id Attachment ID.
	 * @param string                   $size_name Size name.
	 * @param string                   $role Source role.
	 * @param string                   $relative_path Authoritative uploads-relative path.
	 * @param string                   $remote_url Remote source URL.
	 * @param int|null                 $width Width hint.
	 * @param int|null                 $height Height hint.
	 * @param OffloadAttachmentSupport $support Attachment support facts.
	 */
	public function __construct(
		int $attachment_id,
		string $size_name,
		string $role,
		string $relative_path,
		string $remote_url,
		?int $width,
		?int $height,
		OffloadAttachmentSupport $support
	) {
		$this->attachment_id = max( 0, $attachment_id );
		$this->size_name     = '' === trim( $size_name ) ? 'unknown' : trim( $size_name );
		$this->role          = trim( $role );
		$this->relative_path = ltrim( str_replace( '\\', '/', trim( $relative_path ) ), '/' );
		$this->remote_url    = trim( $remote_url );
		$this->width         = null !== $width && 0 < $width ? $width : null;
		$this->height        = null !== $height && 0 < $height ? $height : null;
		$this->support       = $support;
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
	 * Get size name.
	 *
	 * @return string
	 */
	public function size_name(): string {
		return $this->size_name;
	}

	/**
	 * Get role.
	 *
	 * @return string
	 */
	public function role(): string {
		return $this->role;
	}

	/**
	 * Get relative path.
	 *
	 * @return string
	 */
	public function relative_path(): string {
		return $this->relative_path;
	}

	/**
	 * Get remote URL.
	 *
	 * @return string
	 */
	public function remote_url(): string {
		return $this->remote_url;
	}

	/**
	 * Get width hint.
	 *
	 * @return int|null
	 */
	public function width(): ?int {
		return $this->width;
	}

	/**
	 * Get height hint.
	 *
	 * @return int|null
	 */
	public function height(): ?int {
		return $this->height;
	}

	/**
	 * Get attachment support facts.
	 *
	 * @return OffloadAttachmentSupport
	 */
	public function support(): OffloadAttachmentSupport {
		return $this->support;
	}
}
