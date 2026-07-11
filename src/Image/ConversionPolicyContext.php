<?php
/**
 * Conversion policy context value object.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Image;

use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentFingerprint;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifest;

/**
 * Carries contextual state for a conversion policy decision.
 *
 * This is an immutable value object passed to ConversionPolicy::should_convert()
 * along with a SourceImage and target format. It provides the attachment-level
 * context that the policy needs beyond the source image itself.
 */
final class ConversionPolicyContext {

	/**
	 * Whether to force re-optimization and bypass reuse checks.
	 *
	 * @var bool
	 */
	private $force;

	/**
	 * Whether the attachment is excluded from optimization.
	 *
	 * @var bool
	 */
	private $excluded;

	/**
	 * Existing derivative manifest for reuse checks.
	 *
	 * @var DerivativeManifest|null
	 */
	private $manifest;

	/**
	 * Current attachment fingerprint for staleness comparison.
	 *
	 * @var AttachmentFingerprint|null
	 */
	private $fingerprint;

	/**
	 * Source validation result for this source image.
	 *
	 * @var SourceImageValidationResult|null
	 */
	private $validation;

	/**
	 * Create the context.
	 *
	 * @param bool                             $force Whether to force re-optimization.
	 * @param bool                             $excluded Whether the attachment is excluded.
	 * @param DerivativeManifest|null          $manifest Existing derivative manifest.
	 * @param AttachmentFingerprint|null       $fingerprint Current attachment fingerprint.
	 * @param SourceImageValidationResult|null $validation Source validation result.
	 */
	public function __construct(
		bool $force = false,
		bool $excluded = false,
		?DerivativeManifest $manifest = null,
		?AttachmentFingerprint $fingerprint = null,
		?SourceImageValidationResult $validation = null
	) {
		$this->force       = $force;
		$this->excluded    = $excluded;
		$this->manifest    = $manifest;
		$this->fingerprint = $fingerprint;
		$this->validation  = $validation;
	}

	/**
	 * Build a default context for new optimization.
	 *
	 * @return self
	 */
	public static function for_new_optimization(): self {
		return new self( false, false );
	}

	/**
	 * Build a context for forced re-optimization.
	 *
	 * @param DerivativeManifest|null    $manifest Existing manifest.
	 * @param AttachmentFingerprint|null $fingerprint Current fingerprint.
	 * @return self
	 */
	public static function for_reoptimization( ?DerivativeManifest $manifest = null, ?AttachmentFingerprint $fingerprint = null ): self {
		return new self( true, false, $manifest, $fingerprint );
	}

	/**
	 * Whether to force re-optimization.
	 *
	 * @return bool
	 */
	public function is_forced(): bool {
		return $this->force;
	}

	/**
	 * Whether the attachment is excluded from optimization.
	 *
	 * @return bool
	 */
	public function is_excluded(): bool {
		return $this->excluded;
	}

	/**
	 * Get the existing derivative manifest.
	 *
	 * @return DerivativeManifest|null
	 */
	public function manifest(): ?DerivativeManifest {
		return $this->manifest;
	}

	/**
	 * Get the current attachment fingerprint.
	 *
	 * @return AttachmentFingerprint|null
	 */
	public function fingerprint(): ?AttachmentFingerprint {
		return $this->fingerprint;
	}

	/**
	 * Get the source validation result.
	 *
	 * @return SourceImageValidationResult|null
	 */
	public function validation(): ?SourceImageValidationResult {
		return $this->validation;
	}

	/**
	 * Return a copy with validation result applied.
	 *
	 * @param SourceImageValidationResult $validation Validation result.
	 * @return self
	 */
	public function with_validation( SourceImageValidationResult $validation ): self {
		return new self(
			$this->force,
			$this->excluded,
			$this->manifest,
			$this->fingerprint,
			$validation
		);
	}

	/**
	 * Serialize without exposing internal state unnecessarily.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'force'           => $this->force,
			'excluded'        => $this->excluded,
			'has_manifest'    => null !== $this->manifest,
			'has_fingerprint' => null !== $this->fingerprint,
			'has_validation'  => null !== $this->validation,
		);
	}
}
