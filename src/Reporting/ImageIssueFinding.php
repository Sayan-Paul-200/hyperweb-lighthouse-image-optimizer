<?php
/**
 * Image issue finding.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Reporting;

/**
 * Carries one conservative page-level image issue finding.
 */
final class ImageIssueFinding {

	public const SEVERITY_LOW    = 'low';
	public const SEVERITY_MEDIUM = 'medium';
	public const SEVERITY_HIGH   = 'high';

	/**
	 * Rule code.
	 *
	 * @var string
	 */
	private $code;

	/**
	 * Severity.
	 *
	 * @var string
	 */
	private $severity;

	/**
	 * Label.
	 *
	 * @var string
	 */
	private $label;

	/**
	 * Message.
	 *
	 * @var string
	 */
	private $message;

	/**
	 * Remediation.
	 *
	 * @var string
	 */
	private $remediation;

	/**
	 * Occurrence IDs.
	 *
	 * @var string[]
	 */
	private $occurrence_ids;

	/**
	 * Attachment IDs.
	 *
	 * @var int[]
	 */
	private $attachment_ids;

	/**
	 * Safe evidence.
	 *
	 * @var array<string,mixed>
	 */
	private $evidence;

	/**
	 * Create finding.
	 *
	 * @param string              $code Rule code.
	 * @param string              $severity Severity.
	 * @param string              $label Label.
	 * @param string              $message Message.
	 * @param string              $remediation Remediation.
	 * @param string[]            $occurrence_ids Occurrence IDs.
	 * @param int[]               $attachment_ids Attachment IDs.
	 * @param array<string,mixed> $evidence Evidence.
	 */
	public function __construct(
		string $code,
		string $severity,
		string $label,
		string $message,
		string $remediation,
		array $occurrence_ids = array(),
		array $attachment_ids = array(),
		array $evidence = array()
	) {
		$this->code           = $this->normalize_code( $code );
		$this->severity       = $this->normalize_severity( $severity );
		$this->label          = trim( $label );
		$this->message        = trim( $message );
		$this->remediation    = trim( $remediation );
		$this->occurrence_ids = $this->normalize_occurrence_ids( $occurrence_ids );
		$this->attachment_ids = $this->normalize_attachment_ids( $attachment_ids );
		$this->evidence       = $this->sanitize_evidence( $evidence );
	}

	/**
	 * Get rule code.
	 *
	 * @return string
	 */
	public function code(): string {
		return $this->code;
	}

	/**
	 * Get severity.
	 *
	 * @return string
	 */
	public function severity(): string {
		return $this->severity;
	}

	/**
	 * Serialize finding.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'code'           => $this->code,
			'severity'       => $this->severity,
			'label'          => $this->label,
			'message'        => $this->message,
			'remediation'    => $this->remediation,
			'occurrence_ids' => $this->occurrence_ids,
			'attachment_ids' => $this->attachment_ids,
			'evidence'       => $this->evidence,
		);
	}

	/**
	 * Normalize one rule code.
	 *
	 * @param string $code Raw code.
	 * @return string
	 */
	private function normalize_code( string $code ): string {
		$code = strtolower( trim( $code ) );
		$code = (string) preg_replace( '/[^a-z0-9_]/', '_', $code );
		$code = trim( $code, '_' );

		return '' === $code ? 'unknown_issue' : substr( $code, 0, 64 );
	}

	/**
	 * Normalize severity.
	 *
	 * @param string $severity Raw severity.
	 * @return string
	 */
	private function normalize_severity( string $severity ): string {
		$severity = strtolower( trim( $severity ) );

		if ( in_array( $severity, array( self::SEVERITY_LOW, self::SEVERITY_MEDIUM, self::SEVERITY_HIGH ), true ) ) {
			return $severity;
		}

		return self::SEVERITY_LOW;
	}

	/**
	 * Normalize occurrence IDs.
	 *
	 * @param string[] $occurrence_ids Raw IDs.
	 * @return string[]
	 */
	private function normalize_occurrence_ids( array $occurrence_ids ): array {
		$normalized = array();

		foreach ( $occurrence_ids as $occurrence_id ) {
			if ( ! is_scalar( $occurrence_id ) ) {
				continue;
			}

			$occurrence_id = strtolower( trim( (string) $occurrence_id ) );
			$occurrence_id = (string) preg_replace( '/[^a-z0-9_\-]/', '_', $occurrence_id );
			$occurrence_id = trim( $occurrence_id, '_' );

			if ( '' !== $occurrence_id && ! in_array( $occurrence_id, $normalized, true ) ) {
				$normalized[] = substr( $occurrence_id, 0, 64 );
			}
		}

		return $normalized;
	}

	/**
	 * Normalize attachment IDs.
	 *
	 * @param int[] $attachment_ids Raw IDs.
	 * @return int[]
	 */
	private function normalize_attachment_ids( array $attachment_ids ): array {
		$normalized = array();

		foreach ( $attachment_ids as $attachment_id ) {
			if ( ! is_numeric( $attachment_id ) ) {
				continue;
			}

			$attachment_id = max( 0, (int) $attachment_id );

			if ( $attachment_id > 0 && ! in_array( $attachment_id, $normalized, true ) ) {
				$normalized[] = $attachment_id;
			}
		}

		return $normalized;
	}

	/**
	 * Sanitize evidence recursively to safe scalar arrays.
	 *
	 * @param array<string,mixed> $evidence Raw evidence.
	 * @return array<string,mixed>
	 */
	private function sanitize_evidence( array $evidence ): array {
		$sanitized = array();

		foreach ( $evidence as $key => $value ) {
			if ( ! is_string( $key ) || '' === trim( $key ) ) {
				continue;
			}

			$key = strtolower( trim( $key ) );
			$key = (string) preg_replace( '/[^a-z0-9_\-]/', '_', $key );
			$key = trim( $key, '_' );

			if ( '' === $key ) {
				continue;
			}

			if ( is_string( $value ) ) {
				$value = trim( preg_replace( '/\s+/', ' ', $value ) ?? '' );
				$sanitized[ $key ] = strlen( $value ) > 255 ? substr( $value, 0, 252 ) . '...' : $value;
				continue;
			}

			if ( is_int( $value ) || is_float( $value ) || is_bool( $value ) || null === $value ) {
				$sanitized[ $key ] = $value;
				continue;
			}

			if ( is_array( $value ) ) {
				$sanitized[ $key ] = $this->sanitize_array_values( $value );
			}
		}

		return $sanitized;
	}

	/**
	 * Sanitize nested array values.
	 *
	 * @param array<mixed> $values Raw values.
	 * @return array<mixed>
	 */
	private function sanitize_array_values( array $values ): array {
		$sanitized = array();

		foreach ( $values as $key => $value ) {
			if ( is_string( $value ) ) {
				$value       = trim( preg_replace( '/\s+/', ' ', $value ) ?? '' );
				$sanitized[] = strlen( $value ) > 255 ? substr( $value, 0, 252 ) . '...' : $value;
				continue;
			}

			if ( is_int( $value ) || is_float( $value ) || is_bool( $value ) || null === $value ) {
				$sanitized[] = $value;
				continue;
			}

			if ( is_array( $value ) ) {
				$nested = array();

				foreach ( $value as $nested_key => $nested_value ) {
					if ( ! is_string( $nested_key ) ) {
						continue;
					}

					if ( is_string( $nested_value ) || is_int( $nested_value ) || is_float( $nested_value ) || is_bool( $nested_value ) || null === $nested_value ) {
						$nested[ $nested_key ] = $nested_value;
					}
				}

				$sanitized[] = $nested;
			}
		}

		return $sanitized;
	}
}
