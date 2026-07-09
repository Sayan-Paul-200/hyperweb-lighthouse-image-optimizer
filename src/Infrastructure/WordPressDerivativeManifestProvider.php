<?php
/**
 * WordPress derivative manifest provider.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Infrastructure;

/**
 * Reads attachment-owned derivative manifests in bounded batches.
 */
final class WordPressDerivativeManifestProvider implements DerivativeManifestProviderInterface {

	private const BATCH_SIZE = 100;

	/**
	 * Get derivative manifests keyed by attachment ID.
	 *
	 * @return iterable<int,array<string,mixed>>
	 */
	public function manifests(): iterable {
		if ( ! function_exists( 'get_posts' ) || ! function_exists( 'get_post_meta' ) ) {
			return;
		}

		$offset = 0;

		do {
			$attachment_ids = \get_posts(
				array(
					'post_type'     => 'attachment',
					'post_status'   => 'any',
					'fields'        => 'ids',
					'numberposts'   => self::BATCH_SIZE,
					'offset'        => $offset,
					'meta_key'      => LifecyclePolicy::META_DERIVATIVES,
					'no_found_rows' => true,
				)
			);

			if ( ! is_array( $attachment_ids ) ) {
				return;
			}

			foreach ( $attachment_ids as $attachment_id ) {
				$manifest = \get_post_meta( (int) $attachment_id, LifecyclePolicy::META_DERIVATIVES, true );

				if ( is_array( $manifest ) ) {
					yield (int) $attachment_id => $manifest;
				}
			}

			$attachment_count = count( $attachment_ids );
			$offset          += self::BATCH_SIZE;
		} while ( self::BATCH_SIZE === $attachment_count );
	}
}
