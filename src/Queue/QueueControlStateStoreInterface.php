<?php
/**
 * Queue control state store contract.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Queue;

/**
 * Persists the global queue pause/resume state.
 */
interface QueueControlStateStoreInterface {

	/**
	 * Read the current state.
	 *
	 * @return QueueControlState
	 */
	public function read(): QueueControlState;

	/**
	 * Persist a paused state.
	 *
	 * @param int $user_id Updating user ID.
	 * @return QueueControlState
	 */
	public function pause( int $user_id ): QueueControlState;

	/**
	 * Persist a resumed state.
	 *
	 * @param int $user_id Updating user ID.
	 * @return QueueControlState
	 */
	public function resume( int $user_id ): QueueControlState;
}
