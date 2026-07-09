<?php
/**
 * Main logger.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Logging;

/**
 * Sanitizes and writes log entries without throwing to callers.
 */
final class Logger implements LoggerInterface {

	/**
	 * Log writer.
	 *
	 * @var LogWriterInterface
	 */
	private $writer;

	/**
	 * Log sanitizer.
	 *
	 * @var LogSanitizer
	 */
	private $sanitizer;

	/**
	 * Clock callback returning GMT datetime text.
	 *
	 * @var callable|null
	 */
	private $clock;

	/**
	 * Create a WordPress-backed logger.
	 *
	 * @return self
	 */
	public static function for_wordpress(): self {
		return new self(
			DatabaseLogWriter::for_wordpress(),
			new LogSanitizer()
		);
	}

	/**
	 * Create the logger.
	 *
	 * @param LogWriterInterface $writer Log writer.
	 * @param LogSanitizer       $sanitizer Log sanitizer.
	 * @param callable|null      $clock Optional clock returning GMT datetime text.
	 */
	public function __construct( LogWriterInterface $writer, LogSanitizer $sanitizer, ?callable $clock = null ) {
		$this->writer    = $writer;
		$this->sanitizer = $sanitizer;
		$this->clock     = $clock;
	}

	/**
	 * Record a log entry.
	 *
	 * @param string       $level Log level.
	 * @param string       $code Stable machine-readable code.
	 * @param string       $message Human-readable message.
	 * @param array<mixed> $context Structured diagnostic context.
	 * @param int|null     $attachment_id Attachment ID, when relevant.
	 * @param string|null  $job_id Queue job ID, when relevant.
	 * @return bool
	 */
	public function log(
		string $level,
		string $code,
		string $message,
		array $context = array(),
		?int $attachment_id = null,
		?string $job_id = null
	): bool {
		try {
			$entry = new LogEntry(
				$this->now(),
				$level,
				$code,
				$message,
				$context,
				$attachment_id,
				$job_id
			);

			return $this->writer->write( $this->sanitizer->sanitize_entry( $entry ) );
		} catch ( \Throwable $throwable ) {
			unset( $throwable );

			return false;
		}
	}

	/**
	 * Record a debug entry.
	 *
	 * @param string       $code Stable machine-readable code.
	 * @param string       $message Human-readable message.
	 * @param array<mixed> $context Structured diagnostic context.
	 * @param int|null     $attachment_id Attachment ID, when relevant.
	 * @param string|null  $job_id Queue job ID, when relevant.
	 * @return bool
	 */
	public function debug(
		string $code,
		string $message,
		array $context = array(),
		?int $attachment_id = null,
		?string $job_id = null
	): bool {
		return $this->log( LogLevel::DEBUG, $code, $message, $context, $attachment_id, $job_id );
	}

	/**
	 * Record an informational entry.
	 *
	 * @param string       $code Stable machine-readable code.
	 * @param string       $message Human-readable message.
	 * @param array<mixed> $context Structured diagnostic context.
	 * @param int|null     $attachment_id Attachment ID, when relevant.
	 * @param string|null  $job_id Queue job ID, when relevant.
	 * @return bool
	 */
	public function info(
		string $code,
		string $message,
		array $context = array(),
		?int $attachment_id = null,
		?string $job_id = null
	): bool {
		return $this->log( LogLevel::INFO, $code, $message, $context, $attachment_id, $job_id );
	}

	/**
	 * Record a warning entry.
	 *
	 * @param string       $code Stable machine-readable code.
	 * @param string       $message Human-readable message.
	 * @param array<mixed> $context Structured diagnostic context.
	 * @param int|null     $attachment_id Attachment ID, when relevant.
	 * @param string|null  $job_id Queue job ID, when relevant.
	 * @return bool
	 */
	public function warning(
		string $code,
		string $message,
		array $context = array(),
		?int $attachment_id = null,
		?string $job_id = null
	): bool {
		return $this->log( LogLevel::WARNING, $code, $message, $context, $attachment_id, $job_id );
	}

	/**
	 * Record an error entry.
	 *
	 * @param string       $code Stable machine-readable code.
	 * @param string       $message Human-readable message.
	 * @param array<mixed> $context Structured diagnostic context.
	 * @param int|null     $attachment_id Attachment ID, when relevant.
	 * @param string|null  $job_id Queue job ID, when relevant.
	 * @return bool
	 */
	public function error(
		string $code,
		string $message,
		array $context = array(),
		?int $attachment_id = null,
		?string $job_id = null
	): bool {
		return $this->log( LogLevel::ERROR, $code, $message, $context, $attachment_id, $job_id );
	}

	/**
	 * Get current GMT datetime text.
	 *
	 * @return string
	 */
	private function now(): string {
		if ( null !== $this->clock ) {
			return (string) call_user_func( $this->clock );
		}

		return gmdate( 'Y-m-d H:i:s' );
	}
}
