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
	 * @return mixed
	 */
	function apply_filters( string $hook, $value ) {
		if (
			'hwlio_default_settings' === $hook
			&& isset( $GLOBALS['hwlio_test_default_settings_filter'] )
			&& is_callable( $GLOBALS['hwlio_test_default_settings_filter'] )
		) {
			return call_user_func( $GLOBALS['hwlio_test_default_settings_filter'], $value );
		}

		return $value;
	}
}
