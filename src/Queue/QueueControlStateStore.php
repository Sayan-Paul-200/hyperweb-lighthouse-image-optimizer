<?php
/**
 * Queue control state store.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Queue;

use HyperWeb\LighthouseImageOptimizer\Infrastructure\LifecyclePolicy;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\OptionStoreInterface;
use HyperWeb\LighthouseImageOptimizer\Infrastructure\WordPressOptionStore;

/**
 * Persists queue pause/resume state in a plugin-owned option.
 */
final class QueueControlStateStore implements QueueControlStateStoreInterface {

	/**
	 * Option store.
	 *
	 * @var OptionStoreInterface
	 */
	private $options;

	/**
	 * Option name.
	 *
	 * @var string
	 */
	private $option_name;

	/**
	 * GMT clock callback.
	 *
	 * @var callable
	 */
	private $now_gmt;

	/**
	 * Build the WordPress-backed store.
	 *
	 * @return self
	 */
	public static function for_wordpress(): self {
		return new self( new WordPressOptionStore() );
	}

	/**
	 * Create the store.
	 *
	 * @param OptionStoreInterface $options Option store.
	 * @param string               $option_name Option name.
	 * @param callable|null        $now_gmt Optional GMT clock callback.
	 */
	public function __construct(
		OptionStoreInterface $options,
		string $option_name = LifecyclePolicy::OPTION_QUEUE_CONTROL_STATE,
		?callable $now_gmt = null
	) {
		$this->options     = $options;
		$this->option_name = $option_name;
		$this->now_gmt     = $now_gmt ?? static function (): string {
			return gmdate( 'Y-m-d H:i:s' );
		};
	}

	/**
	 * Read the current state.
	 *
	 * @return QueueControlState
	 */
	public function read(): QueueControlState {
		return QueueControlState::from_array( $this->options->get( $this->option_name, array() ) );
	}

	/**
	 * Persist a paused state.
	 *
	 * @param int $user_id Updating user ID.
	 * @return QueueControlState
	 */
	public function pause( int $user_id ): QueueControlState {
		return $this->persist( true, $user_id );
	}

	/**
	 * Persist a resumed state.
	 *
	 * @param int $user_id Updating user ID.
	 * @return QueueControlState
	 */
	public function resume( int $user_id ): QueueControlState {
		return $this->persist( false, $user_id );
	}

	/**
	 * Persist one state snapshot.
	 *
	 * @param bool $paused Whether paused.
	 * @param int  $user_id Updating user ID.
	 * @return QueueControlState
	 */
	private function persist( bool $paused, int $user_id ): QueueControlState {
		$state = new QueueControlState(
			$paused,
			(string) call_user_func( $this->now_gmt ),
			max( 0, $user_id )
		);

		if ( false === $this->options->get( $this->option_name, false ) ) {
			$this->options->add( $this->option_name, $state->to_array(), false );
		} else {
			$this->options->update( $this->option_name, $state->to_array(), false );
		}

		return $state;
	}
}
