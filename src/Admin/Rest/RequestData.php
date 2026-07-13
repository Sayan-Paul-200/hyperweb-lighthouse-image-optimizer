<?php
/**
 * REST request helper.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Admin\Rest;

/**
 * Reads request parameters without depending directly on WP_REST_Request.
 */
final class RequestData {

	/**
	 * Determine whether a parameter exists.
	 *
	 * @param mixed  $request Request object or array.
	 * @param string $key Parameter name.
	 * @return bool
	 */
	public static function has_param( $request, string $key ): bool {
		if ( is_array( $request ) ) {
			return array_key_exists( $key, $request );
		}

		if ( is_object( $request ) && method_exists( $request, 'get_params' ) ) {
			$params = $request->get_params();

			return is_array( $params ) && array_key_exists( $key, $params );
		}

		if ( is_object( $request ) && isset( $request->{$key} ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Read one parameter.
	 *
	 * @param mixed  $request Request object or array.
	 * @param string $key Parameter name.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public static function param( $request, string $key, $default = null ) {
		if ( is_array( $request ) ) {
			return array_key_exists( $key, $request ) ? $request[ $key ] : $default;
		}

		if ( is_object( $request ) && method_exists( $request, 'get_param' ) ) {
			$value = $request->get_param( $key );

			return null !== $value ? $value : $default;
		}

		if ( is_object( $request ) && isset( $request->{$key} ) ) {
			return $request->{$key};
		}

		return $default;
	}

	/**
	 * Parse a positive integer parameter.
	 *
	 * @param mixed  $request Request object or array.
	 * @param string $key Parameter name.
	 * @return int
	 */
	public static function positive_int( $request, string $key ): int {
		$value = self::param( $request, $key, 0 );

		return is_numeric( $value ) ? max( 0, (int) $value ) : 0;
	}

	/**
	 * Parse a boolean parameter.
	 *
	 * @param mixed  $request Request object or array.
	 * @param string $key Parameter name.
	 * @return bool|null
	 */
	public static function boolean( $request, string $key ): ?bool {
		return self::normalize_bool( self::param( $request, $key, null ) );
	}

	/**
	 * Normalize a raw boolean-like value.
	 *
	 * @param mixed $value Value.
	 * @return bool|null
	 */
	public static function normalize_bool( $value ): ?bool {
		if ( is_bool( $value ) ) {
			return $value;
		}

		if ( is_numeric( $value ) ) {
			return 1 === (int) $value ? true : ( 0 === (int) $value ? false : null );
		}

		if ( is_string( $value ) ) {
			$value = strtolower( trim( $value ) );

			if ( in_array( $value, array( '1', 'true', 'yes', 'on' ), true ) ) {
				return true;
			}

			if ( in_array( $value, array( '0', 'false', 'no', 'off' ), true ) ) {
				return false;
			}
		}

		return null;
	}
}
