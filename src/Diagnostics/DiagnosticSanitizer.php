<?php
/**
 * Diagnostic output sanitization.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Diagnostics;

/**
 * Redacts sensitive and server-local data from diagnostic output.
 */
final class DiagnosticSanitizer {

	public const REDACTED      = '[redacted]';
	public const REDACTED_PATH = '[redacted_path]';
	public const TRUNCATED_KEY = '_hwlio_truncated';

	private const MAX_MESSAGE_LENGTH = 1000;
	private const MAX_STRING_LENGTH  = 500;
	private const MAX_DETAILS_DEPTH  = 4;
	private const MAX_DETAILS_ITEMS  = 50;

	/**
	 * Sanitize a result.
	 *
	 * @param DiagnosticResult $result Result.
	 * @return DiagnosticResult
	 */
	public function sanitize_result( DiagnosticResult $result ): DiagnosticResult {
		return new DiagnosticResult(
			$result->id(),
			$result->status(),
			$result->code(),
			$this->sanitize_message( $result->label() ),
			$this->sanitize_message( $result->message() ),
			$this->sanitize_details( $result->details() )
		);
	}

	/**
	 * Sanitize a message.
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
	 * Sanitize structured details.
	 *
	 * @param array<mixed> $details Details.
	 * @return array<mixed>
	 */
	public function sanitize_details( array $details ): array {
		$truncated = false;
		$sanitized = $this->sanitize_array( $details, 0, $truncated );

		if ( $truncated ) {
			$sanitized[ self::TRUNCATED_KEY ] = true;
		}

		return $sanitized;
	}

	/**
	 * Sanitize an array recursively.
	 *
	 * @param array<mixed> $value Value.
	 * @param int          $depth Depth.
	 * @param bool         $truncated Whether details were truncated.
	 * @return array<mixed>
	 */
	private function sanitize_array( array $value, int $depth, bool &$truncated ): array {
		if ( $depth >= self::MAX_DETAILS_DEPTH ) {
			$truncated = true;

			return array();
		}

		$sanitized = array();
		$count     = 0;

		foreach ( $value as $key => $item ) {
			if ( $count >= self::MAX_DETAILS_ITEMS ) {
				$truncated = true;
				break;
			}

			$detail_key = is_int( $key ) ? $key : $this->sanitize_key( (string) $key );

			if ( ! is_int( $detail_key ) && $this->is_sensitive_key( $detail_key ) ) {
				$sanitized[ $detail_key ] = self::REDACTED;
			} else {
				$sanitized[ $detail_key ] = $this->sanitize_value( $item, $depth + 1, $truncated );
			}

			++$count;
		}

		return $sanitized;
	}

	/**
	 * Sanitize one value.
	 *
	 * @param mixed $value Value.
	 * @param int   $depth Depth.
	 * @param bool  $truncated Whether details were truncated.
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
	 * Sanitize a key.
	 *
	 * @param string $key Key.
	 * @return string
	 */
	private function sanitize_key( string $key ): string {
		$key = strtolower( trim( $key ) );
		$key = (string) preg_replace( '/[^a-z0-9_\-]/', '_', $key );

		return '' === $key ? 'detail' : substr( $key, 0, 64 );
	}

	/**
	 * Determine whether a key is sensitive.
	 *
	 * @param string $key Key.
	 * @return bool
	 */
	private function is_sensitive_key( string $key ): bool {
		return 1 === preg_match(
			'/(password|passwd|token|secret|cookie|nonce|authorization|api[_-]?key|bearer|auth)/i',
			$key
		);
	}

	/**
	 * Redact likely absolute paths.
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
	 * Truncate a string.
	 *
	 * @param string $value Value.
	 * @param int    $max_length Max length.
	 * @return string
	 */
	private function truncate_string( string $value, int $max_length ): string {
		if ( strlen( $value ) <= $max_length ) {
			return $value;
		}

		return substr( $value, 0, $max_length - 14 ) . '...[truncated]';
	}
}
