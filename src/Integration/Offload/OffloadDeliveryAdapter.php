<?php
/**
 * Offload delivery adapter provider.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration\Offload;

use HyperWeb\LighthouseImageOptimizer\Delivery\DeliveryHookPolicy;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookProviderInterface;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\HookRegistrar;

/**
 * Rewrites derivative delivery URLs for safely supported offloaded attachments.
 */
final class OffloadDeliveryAdapter implements HookProviderInterface {

	/**
	 * Support service.
	 *
	 * @var OffloadSupportService
	 */
	private $support;

	/**
	 * Create provider.
	 *
	 * @param OffloadSupportService $support Support service.
	 */
	public function __construct( OffloadSupportService $support ) {
		$this->support = $support;
	}

	/**
	 * Register only the generic delivery URL filters.
	 *
	 * @param HookRegistrar $hooks Hook registrar.
	 * @return void
	 */
	public function register_hooks( HookRegistrar $hooks ): void {
		$hooks->add_filter( DeliveryHookPolicy::FILTER_UPLOADS_BASE_URL, array( $this, 'filter_uploads_base_url' ), 10, 6 );
		$hooks->add_filter( DeliveryHookPolicy::FILTER_DERIVATIVE_URL, array( $this, 'filter_derivative_url' ), 10, 6 );
	}

	/**
	 * Rewrite uploads base URL when the attachment is safely offloaded.
	 *
	 * @param string              $base_url Current base URL.
	 * @param string              $relative_path Relative derivative path.
	 * @param int|null            $attachment_id Attachment ID.
	 * @param string|null         $size_name Size name.
	 * @param string|null         $format Format.
	 * @param array<string,mixed> $context Context.
	 * @return string
	 */
	public function filter_uploads_base_url(
		string $base_url,
		string $relative_path,
		?int $attachment_id,
		?string $size_name,
		?string $format,
		array $context
	): string {
		unset( $relative_path, $size_name, $format, $context );

		if ( null === $attachment_id || 0 >= $attachment_id ) {
			return $base_url;
		}

		$support = $this->support->attachment_support( $attachment_id );

		if ( ! $support->is_supported() || ! $support->is_offloaded() || null === $support->remote_base_url() ) {
			return $base_url;
		}

		return $support->remote_base_url();
	}

	/**
	 * Rewrite the final derivative URL when the attachment is safely offloaded.
	 *
	 * @param string              $url Current derivative URL.
	 * @param string              $relative_path Relative derivative path.
	 * @param int|null            $attachment_id Attachment ID.
	 * @param string|null         $size_name Size name.
	 * @param string|null         $format Format.
	 * @param array<string,mixed> $context Context.
	 * @return string
	 */
	public function filter_derivative_url(
		string $url,
		string $relative_path,
		?int $attachment_id,
		?string $size_name,
		?string $format,
		array $context
	): string {
		unset( $size_name, $format, $context );

		if ( null === $attachment_id || 0 >= $attachment_id ) {
			return $url;
		}

		$support = $this->support->attachment_support( $attachment_id );

		if ( ! $support->is_supported() || ! $support->is_offloaded() || null === $support->remote_base_url() ) {
			return $url;
		}

		return rtrim( $support->remote_base_url(), '/' ) . '/' . ltrim( $relative_path, '/' );
	}
}
