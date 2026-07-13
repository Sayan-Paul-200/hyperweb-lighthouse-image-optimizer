<?php
/**
 * WordPress REST runtime adapter.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\Rest;

/**
 * Calls WordPress REST APIs on behalf of the admin controllers.
 */
final class WordPressRestRuntime implements RestRuntimeInterface {

	/**
	 * Register one REST route.
	 *
	 * @param string                $namespace Route namespace.
	 * @param string                $route Route pattern.
	 * @param array<string,mixed>[] $definitions Route definitions.
	 * @return void
	 */
	public function register_route( string $namespace, string $route, array $definitions ): void {
		\register_rest_route( $namespace, $route, $definitions );
	}

	/**
	 * Check whether the current user has a capability.
	 *
	 * @param string   $capability Capability name.
	 * @param int|null $object_id Optional object ID.
	 * @return bool
	 */
	public function current_user_can( string $capability, ?int $object_id = null ): bool {
		if ( null === $object_id ) {
			return \current_user_can( $capability );
		}

		return \current_user_can( $capability, $object_id );
	}

	/**
	 * Get the current user ID.
	 *
	 * @return int
	 */
	public function current_user_id(): int {
		return function_exists( 'get_current_user_id' ) ? (int) \get_current_user_id() : 0;
	}

	/**
	 * Determine whether an attachment exists.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return bool
	 */
	public function attachment_exists( int $attachment_id ): bool {
		return null !== \get_post( max( 0, $attachment_id ) );
	}

	/**
	 * Determine whether an attachment is an image.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return bool
	 */
	public function attachment_is_image( int $attachment_id ): bool {
		return \wp_attachment_is_image( max( 0, $attachment_id ) );
	}

	/**
	 * Build a successful REST response.
	 *
	 * @param array<string,mixed> $data Response payload.
	 * @param int                 $status HTTP status code.
	 * @return \WP_REST_Response
	 */
	public function response( array $data, int $status = 200 ) {
		return new \WP_REST_Response( $data, $status );
	}

	/**
	 * Build a REST error response.
	 *
	 * @param string              $code Stable error code.
	 * @param string              $message User-safe message.
	 * @param int                 $status HTTP status code.
	 * @param array<string,mixed> $data Extra error data.
	 * @return \WP_Error
	 */
	public function error( string $code, string $message, int $status, array $data = array() ) {
		$data['status'] = $status;

		return new \WP_Error( $code, $message, $data );
	}
}
