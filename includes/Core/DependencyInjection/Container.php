<?php
/**
 * Dependency Injection Container
 *
 * @package ZohoConnectSerializer
 */

namespace ZohoConnectSerializer\Core\DependencyInjection;

/**
 * Simple dependency injection container
 */
class Container {

	/**
	 * Bindings
	 *
	 * @var array
	 */
	private $bindings = array();

	/**
	 * Instances
	 *
	 * @var array
	 */
	private $instances = array();

	/**
	 * Bind a class or closure
	 *
	 * @param string $abstract Abstract identifier
	 * @param mixed  $concrete Concrete implementation
	 */
	public function bind( $abstract, $concrete ) {
		$this->bindings[ $abstract ] = $concrete;
	}

	/**
	 * Make an instance
	 *
	 * @param string $abstract Abstract identifier
	 * @return mixed
	 */
	public function make( $abstract ) {
		// Return singleton if already instantiated
		if ( isset( $this->instances[ $abstract ] ) ) {
			return $this->instances[ $abstract ];
		}

		// Get binding
		if ( ! isset( $this->bindings[ $abstract ] ) ) {
			throw new \Exception( "No binding found for: {$abstract}" );
		}

		$concrete = $this->bindings[ $abstract ];

		// If it's a closure, call it
		if ( is_callable( $concrete ) ) {
			$instance = $concrete();
		} elseif ( is_string( $concrete ) && class_exists( $concrete ) ) {
			$instance = new $concrete();
		} else {
			$instance = $concrete;
		}

		// Store as singleton
		$this->instances[ $abstract ] = $instance;

		return $instance;
	}

	/**
	 * Check if binding exists
	 *
	 * @param string $abstract Abstract identifier
	 * @return bool
	 */
	public function has( $abstract ) {
		return isset( $this->bindings[ $abstract ] );
	}
}
