<?php
/**
 * WordPress hook registrar.
 *
 * @package Hyperweb_Lighthouse_Image_Optimizer
 */

namespace HyperWeb\LighthouseImageOptimizer\Infrastructure;

/**
 * Collects WordPress hooks and registers them in one pass.
 */
final class HookRegistrar {

	/**
	 * Action hook definitions.
	 *
	 * @var array<int,array{hook:string,callback:callable,priority:int,accepted_args:int}>
	 */
	private $actions = array();

	/**
	 * Filter hook definitions.
	 *
	 * @var array<int,array{hook:string,callback:callable,priority:int,accepted_args:int}>
	 */
	private $filters = array();

	/**
	 * Whether collected hooks have been registered with WordPress.
	 *
	 * @var bool
	 */
	private $registered = false;

	/**
	 * Add an action hook definition.
	 *
	 * @param string   $hook Hook name.
	 * @param callable $callback Callback.
	 * @param int      $priority Hook priority.
	 * @param int      $accepted_args Accepted argument count.
	 * @return void
	 */
	public function add_action( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {
		$this->actions[] = $this->definition( $hook, $callback, $priority, $accepted_args );
	}

	/**
	 * Add a filter hook definition.
	 *
	 * @param string   $hook Hook name.
	 * @param callable $callback Callback.
	 * @param int      $priority Hook priority.
	 * @param int      $accepted_args Accepted argument count.
	 * @return void
	 */
	public function add_filter( string $hook, callable $callback, int $priority = 10, int $accepted_args = 1 ): void {
		$this->filters[] = $this->definition( $hook, $callback, $priority, $accepted_args );
	}

	/**
	 * Register collected hooks with WordPress.
	 *
	 * @return void
	 */
	public function register(): void {
		if ( $this->registered ) {
			return;
		}

		foreach ( $this->filters as $filter ) {
			\add_filter( $filter['hook'], $filter['callback'], $filter['priority'], $filter['accepted_args'] );
		}

		foreach ( $this->actions as $action ) {
			\add_action( $action['hook'], $action['callback'], $action['priority'], $action['accepted_args'] );
		}

		$this->registered = true;
	}

	/**
	 * Get collected action hook definitions.
	 *
	 * @return array<int,array{hook:string,callback:callable,priority:int,accepted_args:int}>
	 */
	public function actions(): array {
		return $this->actions;
	}

	/**
	 * Get collected filter hook definitions.
	 *
	 * @return array<int,array{hook:string,callback:callable,priority:int,accepted_args:int}>
	 */
	public function filters(): array {
		return $this->filters;
	}

	/**
	 * Determine whether hooks have been registered with WordPress.
	 *
	 * @return bool
	 */
	public function is_registered(): bool {
		return $this->registered;
	}

	/**
	 * Build a hook definition.
	 *
	 * @param string   $hook Hook name.
	 * @param callable $callback Callback.
	 * @param int      $priority Hook priority.
	 * @param int      $accepted_args Accepted argument count.
	 * @return array{hook:string,callback:callable,priority:int,accepted_args:int}
	 */
	private function definition( string $hook, callable $callback, int $priority, int $accepted_args ): array {
		return array(
			'hook'          => $hook,
			'callback'      => $callback,
			'priority'      => $priority,
			'accepted_args' => $accepted_args,
		);
	}
}
