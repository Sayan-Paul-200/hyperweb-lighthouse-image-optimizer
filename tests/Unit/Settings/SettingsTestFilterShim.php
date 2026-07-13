<?php
/**
 * WordPress filter shim for settings tests.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

if ( ! function_exists( 'apply_filters' ) ) {
	/**
	 * Minimal test shim for WordPress filters.
	 *
	 * @param string $hook Filter hook.
	 * @param mixed  $value Filtered value.
	 * @param mixed  ...$args Additional arguments.
	 * @return mixed
	 */
	function apply_filters( string $hook, $value, ...$args ) {
		if (
			isset( $GLOBALS['hwlio_test_filters'][ $hook ] )
			&& is_callable( $GLOBALS['hwlio_test_filters'][ $hook ] )
		) {
			return call_user_func( $GLOBALS['hwlio_test_filters'][ $hook ], $value, ...$args );
		}

		if (
			'hwlio_default_settings' === $hook
			&& isset( $GLOBALS['hwlio_test_default_settings_filter'] )
			&& is_callable( $GLOBALS['hwlio_test_default_settings_filter'] )
		) {
			return call_user_func( $GLOBALS['hwlio_test_default_settings_filter'], $value, ...$args );
		}

		return $value;
	}
}
