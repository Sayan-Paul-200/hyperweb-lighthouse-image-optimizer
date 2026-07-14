<?php
/**
 * WordPress function shims for infrastructure tests.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

if ( ! function_exists( 'do_action' ) ) {
	/**
	 * Minimal action-dispatch shim for infrastructure tests.
	 *
	 * @param string $hook Hook name.
	 * @param mixed  ...$args Action arguments.
	 * @return void
	 */
	function do_action( string $hook, ...$args ): void {
		if ( ! isset( $GLOBALS['hwlio_test_actions'] ) || ! is_array( $GLOBALS['hwlio_test_actions'] ) ) {
			$GLOBALS['hwlio_test_actions'] = array();
		}

		$GLOBALS['hwlio_test_actions'][] = array(
			'hook' => $hook,
			'args' => $args,
		);
	}
}
