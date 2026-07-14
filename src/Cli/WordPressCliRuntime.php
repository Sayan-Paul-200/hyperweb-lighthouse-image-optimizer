<?php
/**
 * WordPress WP-CLI runtime adapter.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Cli;

/**
 * Bridges command registration and output to WP-CLI.
 */
final class WordPressCliRuntime implements CliRuntimeInterface {

	/**
	 * Whether WP-CLI is available.
	 *
	 * @return bool
	 */
	public function available(): bool {
		return class_exists( '\WP_CLI' );
	}

	/**
	 * Register a root command.
	 *
	 * @param string $name Command name.
	 * @param object $command Command object.
	 * @return void
	 */
	public function register_command( string $name, object $command ): void {
		if ( $this->available() ) {
			\WP_CLI::add_command( $name, $command );
		}
	}

	/**
	 * Output a plain line.
	 *
	 * @param string $message Message.
	 * @return void
	 */
	public function line( string $message ): void {
		if ( $this->available() ) {
			\WP_CLI::line( $message );
		}
	}

	/**
	 * Output a warning.
	 *
	 * @param string $message Message.
	 * @return void
	 */
	public function warning( string $message ): void {
		if ( $this->available() ) {
			\WP_CLI::warning( $message );
		}
	}

	/**
	 * Output an error message without forcing process termination.
	 *
	 * @param string $message Message.
	 * @return void
	 */
	public function error( string $message ): void {
		if ( $this->available() ) {
			\WP_CLI::line( 'Error: ' . $message );
		}
	}

	/**
	 * Format items for command output.
	 *
	 * @param string                         $format Output format.
	 * @param array<int,array<string,mixed>> $items Items.
	 * @param string[]                       $fields Fields.
	 * @return void
	 */
	public function format_items( string $format, array $items, array $fields ): void {
		if ( $this->available() ) {
			\WP_CLI\Utils\format_items( $format, $items, $fields );
		}
	}

	/**
	 * Output one JSON payload.
	 *
	 * @param array<string,mixed> $data Data.
	 * @return void
	 */
	public function json( array $data ): void {
		$json = function_exists( 'wp_json_encode' )
			? \wp_json_encode( $data )
			// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Safe fallback outside WordPress bootstrap.
			: json_encode( $data );

		$this->line( is_string( $json ) ? $json : '{}' );
	}

	/**
	 * Halt the command with an exit code.
	 *
	 * @param int $exit_code Exit code.
	 * @return int
	 */
	public function halt( int $exit_code ): int {
		if ( $this->available() ) {
			\WP_CLI::halt( $exit_code );
		}

		return $exit_code;
	}
}
