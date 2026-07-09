<?php
/**
 * Log sanitization.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Logging;

/**
 * Redacts sensitive/path data and bounds log payloads before storage.
 */
final class LogSanitizer {

	public const REDACTED      = '[redacted]';
	public const REDACTED_PATH = '[redacted_path]';
	public const TRUNCATED_KEY = '_hwlio_truncated';

	private const MAX_MESSAGE_LENGTH    = 1000;
	private const MAX_STRING_LENGTH     = 500;
	private const MAX_CONTEXT_DEPTH     = 4;
	private const MAX_CONTEXT_ITEMS     = 50;
	private const MAX_CONTEXT_JSON_SIZE = 16000;

	/**
	 * Sanitize a log entry.
	 *
	 * @param LogEntry $entry Log entry.
	 * @return LogEntry
	 */
	public function sanitize_entry( LogEntry $entry ): LogEntry {
		return new LogEntry(
			$entry->created_at_gmt(),
			LogLevel::normalize( $entry->level() ),
			LogCode::normalize( $entry->code() ),
			$this->sanitize_message( $entry->message() ),
			$this->sanitize_context( $entry->context() ),
			$entry->attachment_id(),
			$this->sanitize_job_id( $entry->job_id() )
		);
	}

	/**
	 * Sanitize a log message.
	 *
	 * @param string $message Message.
	 * @return string
	 */
	public function sanitize_message( string $message ): string {
		return $this->truncate_string(
			$this->redact_paths_in_string( $message ),
			self::MAX_MESSAGE_LENGTH
		);
	}

	/**
	 * Sanitize structured context.
	 *
	 * @param array<mixed> $context Context.
	 * @return array<mixed>
	 */
	public function sanitize_context( array $context ): array {
		$truncated = false;
		$sanitized = $this->sanitize_array( $context, 0, $truncated );

		if ( $truncated ) {
			$sanitized[ self::TRUNCATED_KEY ] = true;
		}

		$json = $this->json_encode( $sanitized );

		if ( null !== $json && strlen( $json ) <= self::MAX_CONTEXT_JSON_SIZE ) {
			return $sanitized;
		}

		return array(
			self::TRUNCATED_KEY => true,
		);
	}

	/**
	 * Sanitize a job ID.
	 *
	 * @param string|null $job_id Job ID.
	 * @return string|null
	 */
	private function sanitize_job_id( ?string $job_id ): ?string {
		if ( null === $job_id ) {
			return null;
		}

		$job_id = $this->truncate_string(
			$this->redact_paths_in_string( $job_id ),
			191
		);

		return '' === $job_id ? null : $job_id;
	}

	/**
	 * Sanitize an array recursively.
	 *
	 * @param array<mixed> $value Context array.
	 * @param int          $depth Current depth.
	 * @param bool         $truncated Whether context was truncated.
	 * @return array<mixed>
	 */
	private function sanitize_array( array $value, int $depth, bool &$truncated ): array {
		if ( $depth >= self::MAX_CONTEXT_DEPTH ) {
			$truncated = true;
			return array();
		}

		$sanitized = array();
		$count     = 0;

		foreach ( $value as $key => $item ) {
			if ( $count >= self::MAX_CONTEXT_ITEMS ) {
				$truncated = true;
				break;
			}

			$context_key = is_int( $key ) ? $key : $this->sanitize_key( (string) $key );

			if ( ! is_int( $context_key ) && $this->is_sensitive_key( $context_key ) ) {
				$sanitized[ $context_key ] = self::REDACTED;
			} else {
				$sanitized[ $context_key ] = $this->sanitize_value( $item, $depth + 1, $truncated );
			}

			++$count;
		}

		return $sanitized;
	}

	/**
	 * Sanitize a scalar or nested context value.
	 *
	 * @param mixed $value Context value.
	 * @param int   $depth Current depth.
	 * @param bool  $truncated Whether context was truncated.
	 * @return mixed
	 */
	private function sanitize_value( $value, int $depth, bool &$truncated ) {
		if ( is_array( $value ) ) {
			return $this->sanitize_array( $value, $depth, $truncated );
		}

		if ( is_string( $value ) ) {
			return $this->truncate_string(
				$this->redact_paths_in_string( $value ),
				self::MAX_STRING_LENGTH
			);
		}

		if ( is_int( $value ) || is_float( $value ) || is_bool( $value ) || null === $value ) {
			return $value;
		}

		$truncated = true;

		return '[unsupported]';
	}

	/**
	 * Sanitize a context key without introducing dependencies on WordPress.
	 *
	 * @param string $key Context key.
	 * @return string
	 */
	private function sanitize_key( string $key ): string {
		$key = strtolower( trim( $key ) );
		$key = (string) preg_replace( '/[^a-z0-9_\-]/', '_', $key );

		return '' === $key ? 'context' : substr( $key, 0, 64 );
	}

	/**
	 * Determine whether a context key is sensitive.
	 *
	 * @param string $key Context key.
	 * @return bool
	 */
	private function is_sensitive_key( string $key ): bool {
		return 1 === preg_match(
			'/(password|passwd|token|secret|cookie|nonce|authorization|api[_-]?key|bearer|auth)/i',
			$key
		);
	}

	/**
	 * Redact likely absolute filesystem paths from a string.
	 *
	 * @param string $value Value.
	 * @return string
	 */
	private function redact_paths_in_string( string $value ): string {
		$value = (string) preg_replace(
			'/(^|[\s({\[=:])(?:[A-Za-z]:[\\\\\/][^\s<>"\')\]}]+)/',
			'$1' . self::REDACTED_PATH,
			$value
		);

		return (string) preg_replace(
			'/(^|[\s({\[=:])\/(?:[A-Za-z0-9._-]+\/?)+[^\s<>"\')\]}]*/',
			'$1' . self::REDACTED_PATH,
			$value
		);
	}

	/**
	 * Truncate a string with a marker.
	 *
	 * @param string $value Value.
	 * @param int    $max_length Maximum length.
	 * @return string
	 */
	private function truncate_string( string $value, int $max_length ): string {
		if ( strlen( $value ) <= $max_length ) {
			return $value;
		}

		return substr( $value, 0, $max_length - 14 ) . '...[truncated]';
	}

	/**
	 * Encode context for size checks.
	 *
	 * @param array<mixed> $value Context.
	 * @return string|null
	 */
	private function json_encode( array $value ): ?string {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- The sanitizer is intentionally independent of WordPress bootstrap.
		$json = json_encode( $value );

		return false === $json ? null : $json;
	}
}
