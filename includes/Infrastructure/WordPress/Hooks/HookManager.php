<?php
/**
 * Hook Manager
 *
 * @package ZohoConnectSerializer
 */

namespace ZohoConnectSerializer\Infrastructure\WordPress\Hooks;

/**
 * WordPress hooks manager
 */
class HookManager {

	/**
	 * Registered hooks
	 *
	 * @var array
	 */
	private $hooks = array();

	/**
	 * Add action
	 *
	 * @param string   $hook Hook name
	 * @param callable $callback Callback function
	 * @param int      $priority Priority
	 * @param int      $accepted_args Number of accepted arguments
	 */
	public function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		add_action( $hook, $callback, $priority, $accepted_args );
		$this->hooks[] = array(
			'type'     => 'action',
			'hook'     => $hook,
			'callback' => $callback,
			'priority' => $priority,
		);
	}

	/**
	 * Add filter
	 *
	 * @param string   $hook Hook name
	 * @param callable $callback Callback function
	 * @param int      $priority Priority
	 * @param int      $accepted_args Number of accepted arguments
	 */
	public function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		add_filter( $hook, $callback, $priority, $accepted_args );
		$this->hooks[] = array(
			'type'     => 'filter',
			'hook'     => $hook,
			'callback' => $callback,
			'priority' => $priority,
		);
	}

	/**
	 * Run hooks (placeholder for future functionality)
	 */
	public function run() {
		// Hooks are automatically registered with WordPress
		// This method can be used for additional initialization
	}
}
