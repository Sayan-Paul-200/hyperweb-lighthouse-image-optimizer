<?php
/**
 * WP-CLI runtime contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Cli;

/**
 * Wraps WP-CLI command registration and output behavior for testability.
 */
interface CliRuntimeInterface {

	/**
	 * Whether WP-CLI is available in the current process.
	 *
	 * @return bool
	 */
	public function available(): bool;

	/**
	 * Register a root WP-CLI command.
	 *
	 * @param string $name Command name.
	 * @param object $command Command object.
	 * @return void
	 */
	public function register_command( string $name, object $command ): void;

	/**
	 * Output a plain line.
	 *
	 * @param string $message Message.
	 * @return void
	 */
	public function line( string $message ): void;

	/**
	 * Output a warning.
	 *
	 * @param string $message Message.
	 * @return void
	 */
	public function warning( string $message ): void;

	/**
	 * Output an error message without forcing process termination.
	 *
	 * @param string $message Message.
	 * @return void
	 */
	public function error( string $message ): void;

	/**
	 * Format items for command output.
	 *
	 * @param string                         $format Output format.
	 * @param array<int,array<string,mixed>> $items Items.
	 * @param string[]                       $fields Fields.
	 * @return void
	 */
	public function format_items( string $format, array $items, array $fields ): void;

	/**
	 * Output one JSON payload.
	 *
	 * @param array<string,mixed> $data Data.
	 * @return void
	 */
	public function json( array $data ): void;

	/**
	 * Halt the command with an exit code.
	 *
	 * @param int $exit_code Exit code.
	 * @return int
	 */
	public function halt( int $exit_code ): int;
}
