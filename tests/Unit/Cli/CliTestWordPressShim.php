<?php
// phpcs:ignoreFile Universal.Files.SeparateFunctionsFromOO.Mixed -- Test shim intentionally mixes namespaces and a lightweight class.
/**
 * WP-CLI shims for CLI runtime tests.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace {
	if ( ! class_exists( 'WP_CLI' ) ) {
		/**
		 * Minimal WP-CLI shim for runtime tests.
		 */
		final class WP_CLI {
			public static $commands = array();
			public static $lines    = array();
			public static $warnings = array();
			public static $halts    = array();

			public static function add_command( string $name, $command ): void {
				self::$commands[ $name ] = $command;
			}

			public static function line( string $message ): void {
				self::$lines[] = $message;
			}

			public static function warning( string $message ): void {
				self::$warnings[] = $message;
			}

			public static function halt( int $exit_code ): void {
				self::$halts[] = $exit_code;
			}
		}
	}

	if ( ! function_exists( 'wp_json_encode' ) ) {
		/**
		 * Minimal JSON shim for CLI runtime tests.
		 *
		 * @param mixed $value Value.
		 * @return string|false
		 */
		function wp_json_encode( $value ) {
			return json_encode( $value );
		}
	}
}

namespace WP_CLI\Utils {
	/**
	 * Minimal format_items shim for CLI runtime tests.
	 *
	 * @param string                         $format Output format.
	 * @param array<int,array<string,mixed>> $items Items.
	 * @param string[]                       $fields Fields.
	 * @return void
	 */
	function format_items( string $format, array $items, array $fields ): void {
		if ( ! isset( $GLOBALS['hwlio_test_cli_tables'] ) || ! is_array( $GLOBALS['hwlio_test_cli_tables'] ) ) {
			$GLOBALS['hwlio_test_cli_tables'] = array();
		}

		$GLOBALS['hwlio_test_cli_tables'][] = array(
			'format' => $format,
			'items'  => $items,
			'fields' => $fields,
		);
	}
}
