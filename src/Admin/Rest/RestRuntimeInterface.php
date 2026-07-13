<?php
/**
 * REST runtime adapter contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\Rest;

/**
 * Describes the WordPress REST APIs needed by the admin controllers.
 */
interface RestRuntimeInterface {

	/**
	 * Register one REST route.
	 *
	 * @param string                $namespace Route namespace.
	 * @param string                $route Route pattern.
	 * @param array<string,mixed>[] $definitions Route definitions.
	 * @return void
	 */
	public function register_route( string $namespace, string $route, array $definitions ): void;

	/**
	 * Check whether the current user has a capability.
	 *
	 * @param string   $capability Capability name.
	 * @param int|null $object_id Optional object ID.
	 * @return bool
	 */
	public function current_user_can( string $capability, ?int $object_id = null ): bool;

	/**
	 * Get the current user ID.
	 *
	 * @return int
	 */
	public function current_user_id(): int;

	/**
	 * Determine whether an attachment exists.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return bool
	 */
	public function attachment_exists( int $attachment_id ): bool;

	/**
	 * Determine whether an attachment is an image.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return bool
	 */
	public function attachment_is_image( int $attachment_id ): bool;

	/**
	 * Build a successful REST response.
	 *
	 * @param array<string,mixed> $data Response payload.
	 * @param int                 $status HTTP status code.
	 * @return mixed
	 */
	public function response( array $data, int $status = 200 );

	/**
	 * Build a REST error response.
	 *
	 * @param string              $code Stable error code.
	 * @param string              $message User-safe message.
	 * @param int                 $status HTTP status code.
	 * @param array<string,mixed> $data Extra error data.
	 * @return mixed
	 */
	public function error( string $code, string $message, int $status, array $data = array() );
}
