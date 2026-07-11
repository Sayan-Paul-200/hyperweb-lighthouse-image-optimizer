<?php
/**
 * Conversion policy decision service.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Image;

use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentFingerprint;
use HyperWeb\LighthouseImageOptimizer\Attachment\DerivativeManifest;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\FormatSupportProviderInterface;
use HyperWeb\LighthouseImageOptimizer\Settings\SettingsRepositoryInterface;

/**
 * Decides whether a specific source image should be converted to a target format.
 *
 * Aggregates all eligibility gates: settings enablement, server support, MIME policy,
 * validation state, resource limits, existing derivative reuse, exclusion, and
 * developer filters. Each gate returns early with a stable skip code so callers
 * understand precisely why conversion was denied.
 *
 * This is a pure-domain decision service. It does not write metadata, schedule
 * queue jobs, call WordPress hooks, or mutate any external state.
 */
final class ConversionPolicy {

	/**
	 * Supported target format identifiers.
	 *
	 * @var string[]
	 */
	private const KNOWN_FORMATS = array( 'webp', 'avif' );

	/**
	 * Settings repository.
	 *
	 * @var SettingsRepositoryInterface
	 */
	private $settings;

	/**
	 * Format support provider.
	 *
	 * @var FormatSupportProviderInterface
	 */
	private $format_support;

	/**
	 * Resource guard.
	 *
	 * @var ResourceGuard
	 */
	private $resource_guard;

	/**
	 * Source MIME policy.
	 *
	 * @var SourceMimePolicy
	 */
	private $mime_policy;

	/**
	 * Create the policy.
	 *
	 * @param SettingsRepositoryInterface    $settings Settings repository.
	 * @param FormatSupportProviderInterface $format_support Format support provider.
	 * @param ResourceGuard                  $resource_guard Resource guard.
	 * @param SourceMimePolicy|null          $mime_policy Source MIME policy.
	 */
	public function __construct(
		SettingsRepositoryInterface $settings,
		FormatSupportProviderInterface $format_support,
		ResourceGuard $resource_guard,
		?SourceMimePolicy $mime_policy = null
	) {
		$this->settings       = $settings;
		$this->format_support = $format_support;
		$this->resource_guard = $resource_guard;
		$this->mime_policy    = $mime_policy ?? new SourceMimePolicy();
	}

	/**
	 * Decide whether this source should be converted to the target format.
	 *
	 * Gates are evaluated in a deliberate order so that the cheapest and most
	 * authoritative checks run first. Each gate returns early with a stable
	 * skip code from the established ConversionResultCode taxonomy.
	 *
	 * @param SourceImage             $source Source image.
	 * @param string                  $target_format Target format identifier (e.g. 'webp', 'avif').
	 * @param ConversionPolicyContext $context Attachment-level context.
	 * @return ConversionPolicyResult
	 */
	public function should_convert( SourceImage $source, string $target_format, ConversionPolicyContext $context ): ConversionPolicyResult {
		$target_format = strtolower( trim( $target_format ) );

		// Gate 1: Valid target format.
		if ( ! in_array( $target_format, self::KNOWN_FORMATS, true ) ) {
			return ConversionPolicyResult::skip(
				ConversionResultCode::INVALID_TARGET_FORMAT,
				sprintf( 'The target format "%s" is not a recognized conversion target.', $target_format )
			);
		}

		// Gate 2: Attachment-level exclusion.
		if ( $context->is_excluded() ) {
			return ConversionPolicyResult::skip(
				ConversionResultCode::SKIPPED_EXCLUDED,
				'The attachment is excluded from optimization.'
			);
		}

		// Gate 3: Format enabled in settings.
		if ( ! in_array( $target_format, $this->settings->enabled_formats(), true ) ) {
			return ConversionPolicyResult::skip(
				ConversionResultCode::SKIPPED_TARGET_NOT_ENABLED,
				sprintf( 'The target format "%s" is not enabled in plugin settings.', $target_format )
			);
		}

		// Gate 4: Server encoding support for target format.
		$support = $this->format_support->support_for( $target_format );
		if ( ! $support->is_supported() ) {
			return ConversionPolicyResult::skip(
				ConversionResultCode::SKIPPED_TARGET_NOT_SUPPORTED,
				sprintf( 'The server does not support encoding %s (%s).', strtoupper( $target_format ), $support->reason() )
			);
		}

		// Gate 5: Source MIME eligibility — is this a supported raster source?
		$source_mime = $this->mime_policy->normalize_mime( $source->mime_type() );
		if ( null === $source_mime || ! $this->mime_policy->is_supported_source_mime( $source_mime ) ) {
			return ConversionPolicyResult::skip(
				ConversionResultCode::SKIPPED_UNSUPPORTED_SOURCE_MIME,
				'The source MIME type is not supported for raster conversion.'
			);
		}

		// Gate 6: Source MIME → target format compatibility.
		$allowed_targets = $this->mime_policy->target_formats_for_source_mime( $source_mime );
		if ( ! in_array( $target_format, $allowed_targets, true ) ) {
			return ConversionPolicyResult::skip(
				ConversionResultCode::SKIPPED_UNSUPPORTED_SOURCE_MIME,
				sprintf(
					'The source MIME type "%s" cannot be converted to "%s".',
					$source_mime,
					$target_format
				)
			);
		}

		// Gate 7: Pre-existing validation result (if context includes one).
		$validation = $context->validation();
		if ( null !== $validation && ! $validation->is_eligible() ) {
			return $this->skip_from_validation( $validation );
		}

		// Gate 8: Resource guard.
		$resource_check = $this->resource_guard->check( $source );
		if ( $resource_check->is_denied() ) {
			return ConversionPolicyResult::skip(
				ConversionResultCode::SKIPPED_RESOURCE_LIMIT,
				$resource_check->message()
			);
		}

		// Gate 9: Existing valid derivative reuse (skip if not forced).
		if ( ! $context->is_forced() ) {
			$reuse = $this->check_derivative_reuse( $source, $target_format, $context );
			if ( null !== $reuse ) {
				return $reuse;
			}
		}

		return ConversionPolicyResult::eligible(
			sprintf(
				'The source "%s" is eligible for %s conversion.',
				$source->size_name(),
				strtoupper( $target_format )
			)
		);
	}

	/**
	 * Check whether an existing derivative can be reused.
	 *
	 * A derivative is reusable when:
	 * - The manifest exists and contains a ready entry for this size + format.
	 * - The manifest fingerprint matches the current fingerprint (source not stale).
	 * - The stored entry status is 'ready'.
	 *
	 * @param SourceImage             $source Source image.
	 * @param string                  $target_format Target format.
	 * @param ConversionPolicyContext $context Context.
	 * @return ConversionPolicyResult|null Null when no reusable derivative exists.
	 */
	private function check_derivative_reuse(
		SourceImage $source,
		string $target_format,
		ConversionPolicyContext $context
	): ?ConversionPolicyResult {
		$manifest = $context->manifest();
		if ( null === $manifest || ! $manifest->has_derivatives() ) {
			return null;
		}

		// Check fingerprint freshness.
		if ( ! $this->fingerprints_match( $manifest->fingerprint(), $context->fingerprint() ) ) {
			return null;
		}

		// Look up the specific size + format entry.
		$sizes     = $manifest->sizes();
		$size_name = $source->size_name();

		if ( ! isset( $sizes[ $size_name ] ) ) {
			return null;
		}

		$size_entry = $sizes[ $size_name ];
		$formats    = isset( $size_entry['formats'] ) && is_array( $size_entry['formats'] )
			? $size_entry['formats']
			: array();

		if ( ! isset( $formats[ $target_format ] ) || ! is_array( $formats[ $target_format ] ) ) {
			return null;
		}

		$format_entry = $formats[ $target_format ];
		$status       = isset( $format_entry['status'] ) && is_string( $format_entry['status'] )
			? $format_entry['status']
			: '';

		if ( DerivativeManifest::FORMAT_STATUS_READY !== $status ) {
			return null;
		}

		return ConversionPolicyResult::skip(
			ConversionResultCode::ALREADY_CURRENT,
			sprintf(
				'A valid %s derivative already exists for size "%s" and the source has not changed.',
				strtoupper( $target_format ),
				$size_name
			)
		);
	}

	/**
	 * Compare manifest fingerprint with current fingerprint.
	 *
	 * @param AttachmentFingerprint|null $manifest_fingerprint Manifest fingerprint.
	 * @param AttachmentFingerprint|null $current_fingerprint Current fingerprint.
	 * @return bool
	 */
	private function fingerprints_match(
		?AttachmentFingerprint $manifest_fingerprint,
		?AttachmentFingerprint $current_fingerprint
	): bool {
		if ( null === $manifest_fingerprint || null === $current_fingerprint ) {
			return false;
		}

		if ( $manifest_fingerprint->relative_file() !== $current_fingerprint->relative_file() ) {
			return false;
		}

		if ( $manifest_fingerprint->file_size() !== $current_fingerprint->file_size() ) {
			return false;
		}

		if ( $manifest_fingerprint->modified_time() !== $current_fingerprint->modified_time() ) {
			return false;
		}

		if ( $manifest_fingerprint->metadata_hash() !== $current_fingerprint->metadata_hash() ) {
			return false;
		}

		return true;
	}

	/**
	 * Map a non-eligible validation result to a policy skip.
	 *
	 * @param SourceImageValidationResult $validation Validation result.
	 * @return ConversionPolicyResult
	 */
	private function skip_from_validation( SourceImageValidationResult $validation ): ConversionPolicyResult {
		$code_map = array(
			SourceImageValidationResult::CODE_SKIPPED_UNSUPPORTED_MIME => ConversionResultCode::SKIPPED_UNSUPPORTED_SOURCE_MIME,
			SourceImageValidationResult::CODE_SKIPPED_ANIMATED_IMAGE  => ConversionResultCode::SKIPPED_ANIMATED_IMAGE,
			SourceImageValidationResult::CODE_SOURCE_MISSING          => ConversionResultCode::SOURCE_MISSING,
			SourceImageValidationResult::CODE_SOURCE_UNREADABLE       => ConversionResultCode::SOURCE_UNREADABLE,
			SourceImageValidationResult::CODE_SOURCE_INVALID_MIME     => ConversionResultCode::SOURCE_INVALID_MIME,
			SourceImageValidationResult::CODE_SOURCE_CORRUPT          => ConversionResultCode::SOURCE_CORRUPT,
			SourceImageValidationResult::CODE_SOURCE_ANIMATION_UNKNOWN => ConversionResultCode::SOURCE_CORRUPT,
		);

		$result_code = isset( $code_map[ $validation->code() ] )
			? $code_map[ $validation->code() ]
			: ConversionResultCode::SKIPPED_UNKNOWN;

		return ConversionPolicyResult::skip(
			$result_code,
			$validation->message()
		);
	}
}
