<?php
/**
 * Queue control state value object.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Queue;

/**
 * Carries the persisted global queue pause/resume state.
 */
final class QueueControlState {

	/**
	 * Whether attachment processing is paused.
	 *
	 * @var bool
	 */
	private $paused;

	/**
	 * Last updated GMT timestamp.
	 *
	 * @var string
	 */
	private $updated_at_gmt;

	/**
	 * Last updating user ID.
	 *
	 * @var int
	 */
	private $updated_by_user_id;

	/**
	 * Create the state object.
	 *
	 * @param bool   $paused Whether paused.
	 * @param string $updated_at_gmt Updated timestamp.
	 * @param int    $updated_by_user_id Updating user ID.
	 */
	public function __construct( bool $paused = false, string $updated_at_gmt = '', int $updated_by_user_id = 0 ) {
		$this->paused             = $paused;
		$this->updated_at_gmt     = '' === trim( $updated_at_gmt ) ? gmdate( 'Y-m-d H:i:s' ) : trim( $updated_at_gmt );
		$this->updated_by_user_id = max( 0, $updated_by_user_id );
	}

	/**
	 * Build from stored data.
	 *
	 * @param mixed $value Raw stored value.
	 * @return self
	 */
	public static function from_array( $value ): self {
		if ( ! is_array( $value ) ) {
			return new self( false, gmdate( 'Y-m-d H:i:s' ), 0 );
		}

		return new self(
			isset( $value['paused'] ) && (bool) $value['paused'],
			isset( $value['updated_at_gmt'] ) && is_scalar( $value['updated_at_gmt'] ) ? (string) $value['updated_at_gmt'] : '',
			isset( $value['updated_by_user_id'] ) && is_numeric( $value['updated_by_user_id'] ) ? (int) $value['updated_by_user_id'] : 0
		);
	}

	/**
	 * Get paused state.
	 *
	 * @return bool
	 */
	public function paused(): bool {
		return $this->paused;
	}

	/**
	 * Get updated timestamp.
	 *
	 * @return string
	 */
	public function updated_at_gmt(): string {
		return $this->updated_at_gmt;
	}

	/**
	 * Get updating user ID.
	 *
	 * @return int
	 */
	public function updated_by_user_id(): int {
		return $this->updated_by_user_id;
	}

	/**
	 * Serialize to a safe stored array.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return array(
			'paused'             => $this->paused(),
			'updated_at_gmt'     => $this->updated_at_gmt(),
			'updated_by_user_id' => $this->updated_by_user_id(),
		);
	}
}
