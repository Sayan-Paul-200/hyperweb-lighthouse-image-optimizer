<?php
/**
 * Random attachment lock token generator.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Attachment;

/**
 * Generates random lock tokens.
 */
final class RandomAttachmentLockTokenGenerator implements AttachmentLockTokenGeneratorInterface {

	/**
	 * Generate a token.
	 *
	 * @return string
	 */
	public function generate(): string {
		try {
			return bin2hex( random_bytes( 16 ) );
		} catch ( \Throwable $throwable ) {
			unset( $throwable );
		}

		$extra = function_exists( 'wp_rand' ) ? (string) \wp_rand() : (string) microtime( true );

		return hash( 'sha256', uniqid( 'hwlio-lock-', true ) . $extra );
	}
}
