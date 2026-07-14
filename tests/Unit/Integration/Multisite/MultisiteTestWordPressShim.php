<?php
// phpcs:ignoreFile -- Test shim intentionally mirrors WordPress signatures, including reserved parameter names.
/**
 * WordPress multisite test shims.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

if ( ! function_exists( 'get_current_blog_id' ) ) {
	/**
	 * Minimal current-site shim for multisite-aware unit tests.
	 *
	 * @return int
	 */
	function get_current_blog_id(): int {
		return isset( $GLOBALS['hwlio_test_current_blog_id'] ) ? (int) $GLOBALS['hwlio_test_current_blog_id'] : 1;
	}
}

if ( ! function_exists( 'is_multisite' ) ) {
	/**
	 * Minimal multisite-state shim.
	 *
	 * @return bool
	 */
	function is_multisite(): bool {
		return ! empty( $GLOBALS['hwlio_test_is_multisite'] );
	}
}

if ( ! function_exists( 'switch_to_blog' ) ) {
	/**
	 * Minimal site-switch shim.
	 *
	 * @param int $site_id Site ID.
	 * @return bool
	 */
	function switch_to_blog( int $site_id ): bool {
		if ( ! isset( $GLOBALS['hwlio_test_blog_stack'] ) || ! is_array( $GLOBALS['hwlio_test_blog_stack'] ) ) {
			$GLOBALS['hwlio_test_blog_stack'] = array();
		}

		$GLOBALS['hwlio_test_blog_stack'][]     = get_current_blog_id();
		$GLOBALS['hwlio_test_current_blog_id']  = $site_id;
		$GLOBALS['hwlio_test_switched_sites'][] = $site_id;

		return true;
	}
}

if ( ! function_exists( 'restore_current_blog' ) ) {
	/**
	 * Minimal site-restore shim.
	 *
	 * @return bool
	 */
	function restore_current_blog(): bool {
		if ( isset( $GLOBALS['hwlio_test_blog_stack'] ) && is_array( $GLOBALS['hwlio_test_blog_stack'] ) && array() !== $GLOBALS['hwlio_test_blog_stack'] ) {
			$GLOBALS['hwlio_test_current_blog_id'] = (int) array_pop( $GLOBALS['hwlio_test_blog_stack'] );
		}

		if ( ! isset( $GLOBALS['hwlio_test_restore_count'] ) ) {
			$GLOBALS['hwlio_test_restore_count'] = 0;
		}

		++$GLOBALS['hwlio_test_restore_count'];

		return true;
	}
}

if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
	/**
	 * Minimal network-active plugin shim.
	 *
	 * @param string $plugin_basename Plugin basename.
	 * @return bool
	 */
	function is_plugin_active_for_network( string $plugin_basename ): bool {
		return isset( $GLOBALS['hwlio_test_network_active_plugins'] )
			&& is_array( $GLOBALS['hwlio_test_network_active_plugins'] )
			&& in_array( $plugin_basename, $GLOBALS['hwlio_test_network_active_plugins'], true );
	}
}

if ( ! function_exists( 'get_site_option' ) ) {
	/**
	 * Minimal site-option shim.
	 *
	 * @param string $option Option name.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	function get_site_option( string $option, $default = false ) {
		if ( 'active_sitewide_plugins' === $option ) {
			$plugins = $GLOBALS['hwlio_test_network_active_plugins'] ?? array();

			if ( is_array( $plugins ) ) {
				return array_fill_keys( $plugins, time() );
			}
		}

		return $GLOBALS['hwlio_test_site_options'][ $option ] ?? $default;
	}
}

if ( ! function_exists( 'get_option' ) ) {
	/**
	 * Minimal site-local option read shim.
	 *
	 * @param string $option Option name.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	function get_option( string $option, $default = false ) {
		$site_id = get_current_blog_id();

		return $GLOBALS['hwlio_test_options_by_site'][ $site_id ][ $option ] ?? $default;
	}
}

if ( ! function_exists( 'add_option' ) ) {
	/**
	 * Minimal site-local option add shim.
	 *
	 * @param string $option Option name.
	 * @param mixed  $value Option value.
	 * @param string $deprecated Deprecated.
	 * @param bool   $autoload Autoload flag.
	 * @return bool
	 */
	function add_option( string $option, $value, string $deprecated = '', bool $autoload = true ): bool {
		unset( $deprecated );

		$site_id = get_current_blog_id();

		if ( isset( $GLOBALS['hwlio_test_options_by_site'][ $site_id ][ $option ] ) ) {
			return false;
		}

		$GLOBALS['hwlio_test_options_by_site'][ $site_id ][ $option ]  = $value;
		$GLOBALS['hwlio_test_autoload_by_site'][ $site_id ][ $option ] = $autoload ? 'yes' : 'no';

		return true;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	/**
	 * Minimal site-local option update shim.
	 *
	 * @param string    $option Option name.
	 * @param mixed     $value Option value.
	 * @param bool|null $autoload Optional autoload flag.
	 * @return bool
	 */
	function update_option( string $option, $value, ?bool $autoload = null ): bool {
		$site_id = get_current_blog_id();

		$GLOBALS['hwlio_test_options_by_site'][ $site_id ][ $option ] = $value;

		if ( null !== $autoload ) {
			$GLOBALS['hwlio_test_autoload_by_site'][ $site_id ][ $option ] = $autoload ? 'yes' : 'no';
		}

		return true;
	}
}

if ( ! function_exists( 'delete_option' ) ) {
	/**
	 * Minimal site-local option delete shim.
	 *
	 * @param string $option Option name.
	 * @return bool
	 */
	function delete_option( string $option ): bool {
		$site_id = get_current_blog_id();

		if ( ! isset( $GLOBALS['hwlio_test_options_by_site'][ $site_id ][ $option ] ) ) {
			return false;
		}

		unset( $GLOBALS['hwlio_test_options_by_site'][ $site_id ][ $option ], $GLOBALS['hwlio_test_autoload_by_site'][ $site_id ][ $option ] );

		return true;
	}
}
