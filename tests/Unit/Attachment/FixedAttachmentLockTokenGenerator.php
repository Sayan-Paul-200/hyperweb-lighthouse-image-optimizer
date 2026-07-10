<?php
/**
 * Fixed attachment lock token generator.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Tests\Unit\Attachment;

use HyperWeb\LighthouseImageOptimizer\Attachment\AttachmentLockTokenGeneratorInterface;

/**
 * Generates deterministic lock tokens for tests.
 */
final class FixedAttachmentLockTokenGenerator implements AttachmentLockTokenGeneratorInterface {

	/**
	 * Tokens.
	 *
	 * @var string[]
	 */
	private $tokens;

	/**
	 * Current index.
	 *
	 * @var int
	 */
	private $index = 0;

	/**
	 * Create generator.
	 *
	 * @param string[] $tokens Tokens.
	 */
	public function __construct( array $tokens ) {
		$this->tokens = array_values( $tokens );
	}

	/**
	 * Generate a token.
	 *
	 * @return string
	 */
	public function generate(): string {
		$token = $this->tokens[ $this->index ] ?? 'fallback-token';
		++$this->index;

		return $token;
	}
}
