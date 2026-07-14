<?php
/**
 * Occurrence asset mapping result.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Reporting;

/**
 * Carries conservative local-asset facts derived from one inventory occurrence.
 */
final class OccurrenceAssetMapping {

	/**
	 * Raw IMG HTML when present.
	 *
	 * @var string
	 */
	private $raw_img_html;

	/**
	 * Attachment image metadata.
	 *
	 * @var array<string,mixed>
	 */
	private $image_meta;

	/**
	 * Extracted source candidates.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	private $source_candidates;

	/**
	 * Matched metadata candidate.
	 *
	 * @var array<string,mixed>|null
	 */
	private $matched_candidate;

	/**
	 * Local file reference.
	 *
	 * @var array<string,string>|null
	 */
	private $local_file_reference;

	/**
	 * Strongest concrete source URL.
	 *
	 * @var string|null
	 */
	private $concrete_source_url;

	/**
	 * Create mapping.
	 *
	 * @param string                   $raw_img_html Raw IMG HTML.
	 * @param array<string,mixed>      $image_meta Attachment metadata.
	 * @param array<int,array<string,mixed>> $source_candidates Extracted sources.
	 * @param array<string,mixed>|null $matched_candidate Matched metadata candidate.
	 * @param array<string,string>|null $local_file_reference Local file reference.
	 * @param string|null              $concrete_source_url Strongest concrete source URL.
	 */
	public function __construct(
		string $raw_img_html,
		array $image_meta,
		array $source_candidates,
		?array $matched_candidate,
		?array $local_file_reference,
		?string $concrete_source_url
	) {
		$this->raw_img_html        = trim( $raw_img_html );
		$this->image_meta          = $image_meta;
		$this->source_candidates   = $source_candidates;
		$this->matched_candidate   = is_array( $matched_candidate ) ? $matched_candidate : null;
		$this->local_file_reference = is_array( $local_file_reference ) ? $local_file_reference : null;
		$this->concrete_source_url = is_string( $concrete_source_url ) && '' !== trim( $concrete_source_url ) ? trim( $concrete_source_url ) : null;
	}

	/**
	 * Get raw IMG HTML.
	 *
	 * @return string
	 */
	public function raw_img_html(): string {
		return $this->raw_img_html;
	}

	/**
	 * Whether raw IMG HTML exists.
	 *
	 * @return bool
	 */
	public function has_raw_img_html(): bool {
		return '' !== $this->raw_img_html;
	}

	/**
	 * Get image metadata.
	 *
	 * @return array<string,mixed>
	 */
	public function image_meta(): array {
		return $this->image_meta;
	}

	/**
	 * Get extracted source candidates.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function source_candidates(): array {
		return $this->source_candidates;
	}

	/**
	 * Get extracted source candidate count.
	 *
	 * @return int
	 */
	public function source_candidate_count(): int {
		return count( $this->source_candidates );
	}

	/**
	 * Get matched metadata candidate.
	 *
	 * @return array<string,mixed>|null
	 */
	public function matched_candidate(): ?array {
		return $this->matched_candidate;
	}

	/**
	 * Get matched size name.
	 *
	 * @return string|null
	 */
	public function matched_size_name(): ?string {
		if ( ! is_array( $this->matched_candidate ) || ! isset( $this->matched_candidate['size_name'] ) || ! is_string( $this->matched_candidate['size_name'] ) ) {
			return null;
		}

		return $this->matched_candidate['size_name'];
	}

	/**
	 * Get local file reference.
	 *
	 * @return array<string,string>|null
	 */
	public function local_file_reference(): ?array {
		return $this->local_file_reference;
	}

	/**
	 * Get local relative path.
	 *
	 * @return string|null
	 */
	public function relative_path(): ?string {
		if ( ! is_array( $this->local_file_reference ) || ! isset( $this->local_file_reference['relative_path'] ) || ! is_string( $this->local_file_reference['relative_path'] ) ) {
			return null;
		}

		return $this->local_file_reference['relative_path'];
	}

	/**
	 * Get local absolute path.
	 *
	 * @return string|null
	 */
	public function absolute_path(): ?string {
		if ( ! is_array( $this->local_file_reference ) || ! isset( $this->local_file_reference['absolute_path'] ) || ! is_string( $this->local_file_reference['absolute_path'] ) ) {
			return null;
		}

		return $this->local_file_reference['absolute_path'];
	}

	/**
	 * Get strongest concrete source URL.
	 *
	 * @return string|null
	 */
	public function concrete_source_url(): ?string {
		return $this->concrete_source_url;
	}
}
