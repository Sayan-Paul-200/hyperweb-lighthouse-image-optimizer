<?php
/**
 * Fake REST runtime.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Admin\Rest;

use HyperWeb\LighthouseImageOptimizer\Admin\Rest\RestRuntimeInterface;

/**
 * Records REST runtime interactions for unit tests.
 */
final class FakeRestRuntime implements RestRuntimeInterface {

	/**
	 * Registered routes.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	public $routes = array();

	/**
	 * Capability checks.
	 *
	 * @var string[]
	 */
	public $capability_checks = array();

	/**
	 * Capability map.
	 *
	 * @var array<string,bool>
	 */
	public $capabilities = array(
		'manage_options' => true,
		'upload_files'   => true,
	);

	/**
	 * Attachment existence map.
	 *
	 * @var array<int,bool>
	 */
	public $attachments = array(
		123 => true,
	);

	/**
	 * Attachment image map.
	 *
	 * @var array<int,bool>
	 */
	public $images = array(
		123 => true,
	);

	/**
	 * Current user ID.
	 *
	 * @var int
	 */
	public $current_user_id = 7;

	/**
	 * Register one route.
	 *
	 * @param string                $rest_namespace Route namespace.
	 * @param string                $route Route pattern.
	 * @param array<string,mixed>[] $definitions Route definitions.
	 * @return void
	 */
	public function register_route( string $rest_namespace, string $route, array $definitions ): void {
		$this->routes[] = array(
			'namespace'   => $rest_namespace,
			'route'       => $route,
			'definitions' => $definitions,
		);
	}

	/**
	 * Check whether the current user has a capability.
	 *
	 * @param string   $capability Capability name.
	 * @param int|null $object_id Optional object ID.
	 * @return bool
	 */
	public function current_user_can( string $capability, ?int $object_id = null ): bool {
		$key = null === $object_id ? $capability : $capability . ':' . $object_id;

		$this->capability_checks[] = $key;

		if ( array_key_exists( $key, $this->capabilities ) ) {
			return $this->capabilities[ $key ];
		}

		return $this->capabilities[ $capability ] ?? false;
	}

	/**
	 * Get the current user ID.
	 *
	 * @return int
	 */
	public function current_user_id(): int {
		return $this->current_user_id;
	}

	/**
	 * Determine whether an attachment exists.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return bool
	 */
	public function attachment_exists( int $attachment_id ): bool {
		return $this->attachments[ $attachment_id ] ?? false;
	}

	/**
	 * Determine whether an attachment is an image.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return bool
	 */
	public function attachment_is_image( int $attachment_id ): bool {
		return $this->images[ $attachment_id ] ?? false;
	}

	/**
	 * Build a fake success response.
	 *
	 * @param array<string,mixed> $data Response payload.
	 * @param int                 $status HTTP status code.
	 * @return array<string,mixed>
	 */
	public function response( array $data, int $status = 200 ) {
		return array(
			'type'   => 'response',
			'status' => $status,
			'data'   => $data,
		);
	}

	/**
	 * Build a fake error response.
	 *
	 * @param string              $code Stable error code.
	 * @param string              $message User-safe message.
	 * @param int                 $status HTTP status code.
	 * @param array<string,mixed> $data Extra error data.
	 * @return array<string,mixed>
	 */
	public function error( string $code, string $message, int $status, array $data = array() ) {
		return array(
			'type'    => 'error',
			'code'    => $code,
			'message' => $message,
			'status'  => $status,
			'data'    => $data,
		);
	}
}
