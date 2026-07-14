<?php
/**
 * Internal inventory occurrence model.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Reporting;

/**
 * Carries one ordered inventory occurrence plus internal-only context.
 */
final class InventoryOccurrence {

	/**
	 * Stable occurrence ID.
	 *
	 * @var string
	 */
	private $id;

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
	 * Public-safe evidence.
	 *
	 * @var array<string,mixed>
	 */
	private $evidence;

	/**
	 * Internal-only raw context.
	 *
	 * @var array<string,mixed>
	 */
	private $context;

	/**
	 * Create occurrence.
	 *
	 * @param string                   $id Stable occurrence ID.
	 * @param string                   $source Source family.
	 * @param string                   $presentation Presentation.
	 * @param string                   $origin Origin.
	 * @param int|null                 $attachment_id Attachment ID.
	 * @param string|null              $url Public URL.
	 * @param array<string,mixed>|null $attachment Lightweight attachment summary.
	 * @param array<string,mixed>      $evidence Public-safe evidence.
	 * @param array<string,mixed>      $context Internal-only context.
	 */
	public function __construct(
		string $id,
		string $source,
		string $presentation,
		string $origin,
		?int $attachment_id = null,
		?string $url = null,
		?array $attachment = null,
		array $evidence = array(),
		array $context = array()
	) {
		$this->id           = $this->normalize_id( $id );
		$this->source       = $source;
		$this->presentation = $presentation;
		$this->origin       = $origin;
		$this->attachment_id = null !== $attachment_id && $attachment_id > 0 ? $attachment_id : null;
		$this->url          = is_string( $url ) && '' !== trim( $url ) ? trim( $url ) : null;
		$this->attachment   = is_array( $attachment ) ? $attachment : null;
		$this->evidence     = $evidence;
		$this->context      = $this->sanitize_context( $context );
	}

	/**
	 * Get occurrence ID.
	 *
	 * @return string
	 */
	public function id(): string {
		return $this->id;
	}

	/**
	 * Get source family.
	 *
	 * @return string
	 */
	public function source(): string {
		return $this->source;
	}

	/**
	 * Get presentation type.
	 *
	 * @return string
	 */
	public function presentation(): string {
		return $this->presentation;
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
	 * Get attachment ID.
	 *
	 * @return int|null
	 */
	public function attachment_id(): ?int {
		return $this->attachment_id;
	}

	/**
	 * Get public URL.
	 *
	 * @return string|null
	 */
	public function url(): ?string {
		return $this->url;
	}

	/**
	 * Get lightweight attachment summary.
	 *
	 * @return array<string,mixed>|null
	 */
	public function attachment(): ?array {
		return $this->attachment;
	}

	/**
	 * Get public-safe evidence.
	 *
	 * @return array<string,mixed>
	 */
	public function evidence(): array {
		return $this->evidence;
	}

	/**
	 * Get internal-only context.
	 *
	 * @return array<string,mixed>
	 */
	public function context(): array {
		return $this->context;
	}

	/**
	 * Convert to the public page-inventory item shape.
	 *
	 * @return PageInventoryItem
	 */
	public function to_page_inventory_item(): PageInventoryItem {
		return new PageInventoryItem(
			$this->id,
			$this->source,
			$this->presentation,
			$this->origin,
			$this->attachment_id,
			$this->url,
			$this->attachment,
			$this->evidence
		);
	}

	/**
	 * Normalize one stable ID.
	 *
	 * @param string $id Raw ID.
	 * @return string
	 */
	private function normalize_id( string $id ): string {
		$id = strtolower( trim( $id ) );
		$id = (string) preg_replace( '/[^a-z0-9_\-]/', '_', $id );
		$id = trim( $id, '_' );

		return '' === $id ? 'occ-0' : substr( $id, 0, 64 );
	}

	/**
	 * Sanitize internal context to stable scalars and arrays.
	 *
	 * @param array<string,mixed> $context Raw context.
	 * @return array<string,mixed>
	 */
	private function sanitize_context( array $context ): array {
		$sanitized = array();

		foreach ( $context as $key => $value ) {
			if ( ! is_string( $key ) || '' === trim( $key ) ) {
				continue;
			}

			$key = strtolower( trim( $key ) );
			$key = (string) preg_replace( '/[^a-z0-9_\-]/', '_', $key );
			$key = trim( $key, '_' );

			if ( '' === $key ) {
				continue;
			}

			if ( is_string( $value ) || is_int( $value ) || is_bool( $value ) || null === $value ) {
				$sanitized[ $key ] = $value;
				continue;
			}

			if ( is_array( $value ) ) {
				$sanitized[ $key ] = $this->sanitize_context( $value );
			}
		}

		return $sanitized;
	}
}
