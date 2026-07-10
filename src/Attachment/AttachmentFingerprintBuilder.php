<?php
/**
 * Attachment fingerprint builder.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Attachment;

use HyperWeb\LighthouseImageOptimizer\Image\SourceImage;
use HyperWeb\LighthouseImageOptimizer\Image\SourceImageCollection;
use HyperWeb\LighthouseImageOptimizer\Image\SourceImageIssue;

/**
 * Builds and compares cheap attachment source fingerprints.
 */
final class AttachmentFingerprintBuilder {

	/**
	 * Build current attachment fingerprint.
	 *
	 * @param SourceImageCollection $collection Source collection.
	 * @return AttachmentFingerprint|null
	 */
	public function build( SourceImageCollection $collection ): ?AttachmentFingerprint {
		$full = $this->full_source( $collection );

		if ( null === $full ) {
			return null;
		}

		return new AttachmentFingerprint(
			$full->relative_path(),
			$full->bytes(),
			$full->modified_time(),
			$this->metadata_hash( $collection )
		);
	}

	/**
	 * Compare a queued short signature with current state.
	 *
	 * @param string                $queued_signature Queued signature.
	 * @param SourceImageCollection $collection Source collection.
	 * @return AttachmentFingerprintComparison
	 */
	public function compare_signature( string $queued_signature, SourceImageCollection $collection ): AttachmentFingerprintComparison {
		$current = $this->build( $collection );

		if ( null === $current ) {
			return AttachmentFingerprintComparison::invalid(
				AttachmentFingerprintCode::FINGERPRINT_MISSING,
				'The current attachment source state cannot be fingerprinted.'
			);
		}

		$queued_signature = strtolower( trim( $queued_signature ) );

		if ( 1 !== preg_match( '/^[a-f0-9]{20}$/', $queued_signature ) ) {
			return AttachmentFingerprintComparison::invalid(
				AttachmentFingerprintCode::FINGERPRINT_INVALID,
				'The queued attachment fingerprint is not valid.',
				$current
			);
		}

		if ( $queued_signature === $current->signature() ) {
			return AttachmentFingerprintComparison::matched( $current );
		}

		return AttachmentFingerprintComparison::stale(
			AttachmentFingerprintCode::FINGERPRINT_MISMATCH,
			'The queued attachment fingerprint no longer matches current source state.',
			$current,
			null,
			array(
				'queued_signature'  => $queued_signature,
				'current_signature' => $current->signature(),
			)
		);
	}

	/**
	 * Compare a stored fingerprint array with current state.
	 *
	 * @param array<string,mixed>   $stored_fingerprint Stored fingerprint.
	 * @param SourceImageCollection $collection Source collection.
	 * @return AttachmentFingerprintComparison
	 */
	public function compare_stored( array $stored_fingerprint, SourceImageCollection $collection ): AttachmentFingerprintComparison {
		$current = $this->build( $collection );

		if ( null === $current ) {
			return AttachmentFingerprintComparison::invalid(
				AttachmentFingerprintCode::FINGERPRINT_MISSING,
				'The current attachment source state cannot be fingerprinted.'
			);
		}

		$reference = AttachmentFingerprint::from_array( $stored_fingerprint );

		if ( null === $reference ) {
			return AttachmentFingerprintComparison::invalid(
				AttachmentFingerprintCode::FINGERPRINT_INVALID,
				'The stored attachment fingerprint is not valid.',
				$current
			);
		}

		if ( $reference->relative_file() !== $current->relative_file() ) {
			return AttachmentFingerprintComparison::stale(
				AttachmentFingerprintCode::SOURCE_PATH_CHANGED,
				'The attachment source path changed.',
				$current,
				$reference
			);
		}

		if ( $reference->file_size() !== $current->file_size() ) {
			return AttachmentFingerprintComparison::stale(
				AttachmentFingerprintCode::SOURCE_BYTES_CHANGED,
				'The attachment source byte size changed.',
				$current,
				$reference
			);
		}

		if ( $reference->modified_time() !== $current->modified_time() ) {
			return AttachmentFingerprintComparison::stale(
				AttachmentFingerprintCode::SOURCE_MODIFIED_CHANGED,
				'The attachment source modified time changed.',
				$current,
				$reference
			);
		}

		if ( $reference->metadata_hash() !== $current->metadata_hash() ) {
			return AttachmentFingerprintComparison::stale(
				AttachmentFingerprintCode::METADATA_HASH_CHANGED,
				'The attachment source metadata signature changed.',
				$current,
				$reference
			);
		}

		return AttachmentFingerprintComparison::matched( $current, $reference );
	}

	/**
	 * Get full source from collection.
	 *
	 * @param SourceImageCollection $collection Collection.
	 * @return SourceImage|null
	 */
	private function full_source( SourceImageCollection $collection ): ?SourceImage {
		foreach ( $collection->sources() as $source ) {
			if ( SourceImage::ROLE_FULL === $source->role() ) {
				return $source;
			}
		}

		return null;
	}

	/**
	 * Build metadata hash from normalized source and issue facts.
	 *
	 * @param SourceImageCollection $collection Collection.
	 * @return string
	 */
	private function metadata_hash( SourceImageCollection $collection ): string {
		return hash(
			'sha256',
			$this->canonical_json(
				array(
					'sources' => $this->source_records( $collection->sources() ),
					'issues'  => $this->issue_records( $collection->issues() ),
				)
			)
		);
	}

	/**
	 * Normalize source records for hashing.
	 *
	 * @param SourceImage[] $sources Sources.
	 * @return array<int,array<string,mixed>>
	 */
	private function source_records( array $sources ): array {
		$records = array();

		foreach ( $sources as $source ) {
			$records[] = array(
				'role_order'    => $this->role_order( $source->role() ),
				'role'          => $source->role(),
				'size_name'     => $source->size_name(),
				'relative_path' => $source->relative_path(),
				'mime_type'     => $source->mime_type(),
				'width'         => $source->width(),
				'height'        => $source->height(),
				'bytes'         => $source->bytes(),
				'modified_time' => $source->modified_time(),
			);
		}

		usort(
			$records,
			static function ( array $left, array $right ): int {
				foreach ( array( 'role_order', 'size_name', 'relative_path' ) as $key ) {
					if ( $left[ $key ] === $right[ $key ] ) {
						continue;
					}

					return $left[ $key ] < $right[ $key ] ? -1 : 1;
				}

				return 0;
			}
		);

		foreach ( $records as &$record ) {
			unset( $record['role_order'] );
		}
		unset( $record );

		return $records;
	}

	/**
	 * Normalize issue records for hashing.
	 *
	 * @param SourceImageIssue[] $issues Issues.
	 * @return array<int,array<string,mixed>>
	 */
	private function issue_records( array $issues ): array {
		$records = array();

		foreach ( $issues as $issue ) {
			$details = $issue->details();
			ksort( $details );

			$records[] = array(
				'role_order' => $this->role_order( $issue->role() ),
				'role'       => $issue->role(),
				'size_name'  => $issue->size_name(),
				'code'       => $issue->code(),
				'details'    => $details,
			);
		}

		usort(
			$records,
			function ( array $left, array $right ): int {
				foreach ( array( 'role_order', 'size_name', 'code' ) as $key ) {
					if ( $left[ $key ] === $right[ $key ] ) {
						continue;
					}

					return $left[ $key ] < $right[ $key ] ? -1 : 1;
				}

				return strcmp( $this->canonical_json( $left['details'] ), $this->canonical_json( $right['details'] ) );
			}
		);

		foreach ( $records as &$record ) {
			unset( $record['role_order'] );
		}
		unset( $record );

		return $records;
	}

	/**
	 * Get deterministic role ordering.
	 *
	 * @param string $role Source role.
	 * @return int
	 */
	private function role_order( string $role ): int {
		if ( SourceImage::ROLE_FULL === $role ) {
			return 0;
		}

		if ( SourceImage::ROLE_SUBSIZE === $role ) {
			return 1;
		}

		if ( SourceImage::ROLE_ORIGINAL === $role ) {
			return 2;
		}

		return 3;
	}

	/**
	 * Encode canonical scalar arrays for hashing.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	private function canonical_json( $value ): string {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Fingerprinting is intentionally independent of WordPress bootstrap.
		$json = json_encode( $value, JSON_UNESCAPED_SLASHES );

		return false === $json ? '' : $json;
	}
}
