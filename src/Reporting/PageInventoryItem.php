<?php
/**
 * Page inventory item.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Reporting;

/**
 * Carries one ordered page-inventory occurrence.
 */
final class PageInventoryItem {

	/**
	 * Stable occurrence ID.
	 *
	 * @var string
	 */
	private $id;

	public const PRESENTATION_INLINE     = 'inline';
	public const PRESENTATION_BACKGROUND = 'background';

	public const ORIGIN_LOCAL_ATTACHMENT   = 'local_attachment';
	public const ORIGIN_LOCAL_UNREGISTERED = 'local_unregistered_url';
	public const ORIGIN_EXTERNAL           = 'external';
	public const ORIGIN_UNKNOWN            = 'unknown';

	/**
	 * Source family.
	 *
	 * @var string
	 */
	private $source;

	/**
	 * Presentation type.
	 *
	 * @var string
	 */
	private $presentation;

	/**
	 * Origin classification.
	 *
	 * @var string
	 */
	private $origin;

	/**
	 * Attachment ID.
	 *
	 * @var int|null
	 */
	private $attachment_id;

	/**
	 * Public URL.
	 *
	 * @var string|null
	 */
	private $url;

	/**
	 * Lightweight attachment summary.
	 *
	 * @var array<string,mixed>|null
	 */
	private $attachment;

	/**
	 * Evidence payload.
	 *
	 * @var array<string,mixed>
	 */
	private $evidence;

	/**
	 * Create item.
	 *
	 * @param string                   $id Stable occurrence ID.
	 * @param string                   $source Source family.
	 * @param string                   $presentation Presentation.
	 * @param string                   $origin Origin.
	 * @param int|null                 $attachment_id Attachment ID.
	 * @param string|null              $url Public URL.
	 * @param array<string,mixed>|null $attachment Lightweight attachment summary.
	 * @param array<string,mixed>      $evidence Safe evidence.
	 */
	public function __construct(
		string $id,
		string $source,
		string $presentation,
		string $origin,
		?int $attachment_id = null,
		?string $url = null,
		?array $attachment = null,
		array $evidence = array()
	) {
		$this->id            = $this->normalize_occurrence_id( $id );
		$this->source        = $this->normalize_key( $source, 'core_content' );
		$this->presentation  = in_array( $presentation, array( self::PRESENTATION_INLINE, self::PRESENTATION_BACKGROUND ), true ) ? $presentation : self::PRESENTATION_INLINE;
		$this->origin        = in_array(
			$origin,
			array(
				self::ORIGIN_LOCAL_ATTACHMENT,
				self::ORIGIN_LOCAL_UNREGISTERED,
				self::ORIGIN_EXTERNAL,
				self::ORIGIN_UNKNOWN,
			),
			true
		) ? $origin : self::ORIGIN_UNKNOWN;
		$this->attachment_id = null !== $attachment_id && $attachment_id > 0 ? $attachment_id : null;
		$this->url           = $this->normalize_url( $url );
		$this->attachment    = $this->sanitize_attachment( $attachment );
		$this->evidence      = $this->sanitize_evidence( $evidence );
	}

	/**
	 * Get attachment ID when present.
	 *
	 * @return int|null
	 */
	public function attachment_id(): ?int {
		return $this->attachment_id;
	}

	/**
	 * Get origin classification.
	 *
	 * @return string
	 */
	public function origin(): string {
		return $this->origin;
	}

	/**
	 * Get presentation classification.
	 *
	 * @return string
	 */
	public function presentation(): string {
		return $this->presentation;
	}

	/**
	 * Serialize item.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'id'            => $this->id,
			'source'        => $this->source,
			'presentation'  => $this->presentation,
			'origin'        => $this->origin,
			'attachment_id' => $this->attachment_id,
			'url'           => $this->url,
			'attachment'    => $this->attachment,
			'evidence'      => $this->evidence,
		);
	}

	/**
	 * Normalize one key.
	 *
	 * @param string $value Value.
	 * @param string $fallback Fallback.
	 * @return string
	 */
	private function normalize_key( string $value, string $fallback ): string {
		$value = strtolower( trim( $value ) );
		$value = (string) preg_replace( '/[^a-z0-9_]/', '_', $value );
		$value = trim( $value, '_' );

		return '' === $value ? $fallback : substr( $value, 0, 64 );
	}

	/**
	 * Normalize one occurrence ID while preserving hyphens.
	 *
	 * @param string $value Raw ID.
	 * @return string
	 */
	private function normalize_occurrence_id( string $value ): string {
		$value = strtolower( trim( $value ) );
		$value = (string) preg_replace( '/[^a-z0-9_\-]/', '_', $value );
		$value = trim( $value, '_' );

		return '' === $value ? 'occ-0' : substr( $value, 0, 64 );
	}

	/**
	 * Normalize one public URL.
	 *
	 * @param string|null $url URL.
	 * @return string|null
	 */
	private function normalize_url( ?string $url ): ?string {
		if ( ! is_string( $url ) ) {
			return null;
		}

		$url = trim( $url );

		return '' === $url ? null : $url;
	}

	/**
	 * Sanitize attachment summary.
	 *
	 * @param array<string,mixed>|null $attachment Raw summary.
	 * @return array<string,mixed>|null
	 */
	private function sanitize_attachment( ?array $attachment ): ?array {
		if ( ! is_array( $attachment ) ) {
			return null;
		}

		return array(
			'state'         => isset( $attachment['state'] ) && is_string( $attachment['state'] ) ? $attachment['state'] : 'unprocessed',
			'ready_formats' => isset( $attachment['ready_formats'] ) && is_array( $attachment['ready_formats'] ) ? array_values( $attachment['ready_formats'] ) : array(),
			'excluded'      => ! empty( $attachment['excluded'] ),
		);
	}

	/**
	 * Sanitize one evidence payload.
	 *
	 * @param array<string,mixed> $evidence Evidence.
	 * @return array<string,mixed>
	 */
	private function sanitize_evidence( array $evidence ): array {
		$sanitized = array();

		foreach ( $evidence as $key => $value ) {
			$key = $this->normalize_key( (string) $key, '' );

			if ( '' === $key ) {
				continue;
			}

			if ( is_bool( $value ) || is_int( $value ) || null === $value ) {
				$sanitized[ $key ] = $value;
				continue;
			}

			if ( is_string( $value ) ) {
				$value             = trim( preg_replace( '/\s+/', ' ', $value ) ?? '' );
				$sanitized[ $key ] = strlen( $value ) > 255 ? substr( $value, 0, 252 ) . '...' : $value;
			}
		}

		return $sanitized;
	}
}
