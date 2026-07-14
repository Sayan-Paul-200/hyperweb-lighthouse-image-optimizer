<?php
/**
 * Derivative push result.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Integration\Offload;

use HyperWeb\LighthouseImageOptimizer\Image\ConversionResultCollection;

/**
 * Carries the transformed conversion-result set after remote publication.
 */
final class DerivativePushResult {

	/**
	 * Result collection.
	 *
	 * @var ConversionResultCollection
	 */
	private $results;

	/**
	 * Codes.
	 *
	 * @var string[]
	 */
	private $codes;

	/**
	 * Messages.
	 *
	 * @var string[]
	 */
	private $messages;

	/**
	 * Create result.
	 *
	 * @param ConversionResultCollection $results Results.
	 * @param string[]                   $codes Codes.
	 * @param string[]                   $messages Messages.
	 */
	public function __construct( ConversionResultCollection $results, array $codes = array(), array $messages = array() ) {
		$this->results  = $results;
		$this->codes    = array_values( array_unique( array_filter( array_map( 'strval', $codes ) ) ) );
		$this->messages = array_values( array_unique( array_filter( array_map( 'strval', $messages ) ) ) );
	}

	/**
	 * Get results.
	 *
	 * @return ConversionResultCollection
	 */
	public function results(): ConversionResultCollection {
		return $this->results;
	}

	/**
	 * Get codes.
	 *
	 * @return string[]
	 */
	public function codes(): array {
		return $this->codes;
	}

	/**
	 * Get messages.
	 *
	 * @return string[]
	 */
	public function messages(): array {
		return $this->messages;
	}
}
