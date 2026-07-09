<?php
/**
 * Source image collection.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Image;

/**
 * Carries normalized source images and non-fatal collection issues.
 */
final class SourceImageCollection {

	/**
	 * Sources.
	 *
	 * @var SourceImage[]
	 */
	private $sources;

	/**
	 * Issues.
	 *
	 * @var SourceImageIssue[]
	 */
	private $issues;

	/**
	 * Create collection.
	 *
	 * @param SourceImage[]      $sources Sources.
	 * @param SourceImageIssue[] $issues Issues.
	 */
	public function __construct( array $sources = array(), array $issues = array() ) {
		$this->sources = array_values(
			array_filter(
				$sources,
				static function ( $source ): bool {
					return $source instanceof SourceImage;
				}
			)
		);
		$this->issues  = array_values(
			array_filter(
				$issues,
				static function ( $issue ): bool {
					return $issue instanceof SourceImageIssue;
				}
			)
		);
	}

	/**
	 * Get sources.
	 *
	 * @return SourceImage[]
	 */
	public function sources(): array {
		return $this->sources;
	}

	/**
	 * Get issues.
	 *
	 * @return SourceImageIssue[]
	 */
	public function issues(): array {
		return $this->issues;
	}

	/**
	 * Whether issues exist.
	 *
	 * @return bool
	 */
	public function has_issues(): bool {
		return array() !== $this->issues;
	}

	/**
	 * Serialize collection without absolute paths.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'sources' => array_map(
				static function ( SourceImage $source ): array {
					return $source->to_array();
				},
				$this->sources
			),
			'issues'  => array_map(
				static function ( SourceImageIssue $issue ): array {
					return $issue->to_array();
				},
				$this->issues
			),
		);
	}
}
