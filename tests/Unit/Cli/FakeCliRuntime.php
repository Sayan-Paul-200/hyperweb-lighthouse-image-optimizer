<?php
/**
 * Fake CLI runtime for unit tests.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Cli;

use HyperWeb\LighthouseImageOptimizer\Cli\CliRuntimeInterface;

/**
 * Captures CLI command registration and output for tests.
 */
final class FakeCliRuntime implements CliRuntimeInterface {

	/**
	 * Whether the fake runtime is available.
	 *
	 * @var bool
	 */
	public $is_available = true;

	/**
	 * Registered commands.
	 *
	 * @var array<string,object>
	 */
	public $commands = array();

	/**
	 * Plain output lines.
	 *
	 * @var string[]
	 */
	public $lines = array();

	/**
	 * Warning lines.
	 *
	 * @var string[]
	 */
	public $warnings = array();

	/**
	 * Error lines.
	 *
	 * @var string[]
	 */
	public $errors = array();

	/**
	 * Captured table renders.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	public $tables = array();

	/**
	 * Captured JSON payloads.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	public $json_payloads = array();

	/**
	 * Halted exit codes.
	 *
	 * @var int[]
	 */
	public $halted = array();

	/**
	 * Whether output methods should throw.
	 *
	 * @var bool
	 */
	public $throw = false;

	/**
	 * Whether the runtime is available.
	 *
	 * @return bool
	 */
	public function available(): bool {
		return $this->is_available;
	}

	/**
	 * Register a root command.
	 *
	 * @param string $name Command name.
	 * @param object $command Command object.
	 * @return void
	 */
	public function register_command( string $name, object $command ): void {
		$this->commands[ $name ] = $command;
	}

	/**
	 * Output a plain line.
	 *
	 * @param string $message Message.
	 * @return void
	 */
	public function line( string $message ): void {
		if ( $this->throw ) {
			throw new \RuntimeException( 'line failure' );
		}

		$this->lines[] = $message;
	}

	/**
	 * Output a warning.
	 *
	 * @param string $message Message.
	 * @return void
	 */
	public function warning( string $message ): void {
		$this->warnings[] = $message;
	}

	/**
	 * Output an error.
	 *
	 * @param string $message Message.
	 * @return void
	 */
	public function error( string $message ): void {
		$this->errors[] = $message;
	}

	/**
	 * Format items for command output.
	 *
	 * @param string                           $format Output format.
	 * @param array<int,array<string,mixed>>   $items Items.
	 * @param string[]                         $fields Fields.
	 * @return void
	 */
	public function format_items( string $format, array $items, array $fields ): void {
		if ( $this->throw ) {
			throw new \RuntimeException( 'format failure' );
		}

		$this->tables[] = array(
			'format' => $format,
			'items'  => $items,
			'fields' => $fields,
		);
	}

	/**
	 * Output one JSON payload.
	 *
	 * @param array<string,mixed> $data Data.
	 * @return void
	 */
	public function json( array $data ): void {
		if ( $this->throw ) {
			throw new \RuntimeException( 'json failure' );
		}

		$this->json_payloads[] = $data;
	}

	/**
	 * Halt with an exit code.
	 *
	 * @param int $exit_code Exit code.
	 * @return int
	 */
	public function halt( int $exit_code ): int {
		$this->halted[] = $exit_code;

		return $exit_code;
	}
}
