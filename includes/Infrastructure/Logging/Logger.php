<?php
/**
 * Logger
 *
 * @package ZohoConnectSerializer
 */

namespace ZohoConnectSerializer\Infrastructure\Logging;

/**
 * Logger for plugin logging
 */
class Logger {

	/**
	 * Log levels
	 */
	const LEVEL_DEBUG = 'debug';
	const LEVEL_INFO = 'info';
	const LEVEL_WARNING = 'warning';
	const LEVEL_ERROR = 'error';

	/**
	 * Log a message
	 *
	 * @param string $level Log level
	 * @param string $message Log message
	 * @param array  $context Context data
	 */
	public function log( $level, $message, array $context = array() ) {
		if ( ! $this->should_log( $level ) ) {
			return;
		}

		$log_entry = array(
			'timestamp' => current_time( 'mysql' ),
			'level'     => $level,
			'message'   => $message,
			'context'   => $context,
		);

		// Log to WordPress debug log if enabled
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( sprintf(
				'[%s] %s: %s %s',
				$log_entry['timestamp'],
				strtoupper( $level ),
				$message,
				! empty( $context ) ? wp_json_encode( $context ) : ''
			) );
		}

		// Store in database or file if needed
		$this->store_log( $log_entry );
	}

	/**
	 * Log debug message
	 *
	 * @param string $message Log message
	 * @param array  $context Context data
	 */
	public function debug( $message, array $context = array() ) {
		$this->log( self::LEVEL_DEBUG, $message, $context );
	}

	/**
	 * Log info message
	 *
	 * @param string $message Log message
	 * @param array  $context Context data
	 */
	public function info( $message, array $context = array() ) {
		$this->log( self::LEVEL_INFO, $message, $context );
	}

	/**
	 * Log warning message
	 *
	 * @param string $message Log message
	 * @param array  $context Context data
	 */
	public function warning( $message, array $context = array() ) {
		$this->log( self::LEVEL_WARNING, $message, $context );
	}

	/**
	 * Log error message
	 *
	 * @param string $message Log message
	 * @param array  $context Context data
	 */
	public function error( $message, array $context = array() ) {
		$this->log( self::LEVEL_ERROR, $message, $context );
	}

	/**
	 * Check if should log
	 *
	 * @param string $level Log level
	 * @return bool
	 */
	private function should_log( $level ) {
		$config = \ZohoConnectSerializer\Core\Plugin::get_instance()
			->get_container()
			->make( 'config' );

		if ( ! $config->get( 'enable_logging', true ) ) {
			return false;
		}

		$log_level = $config->get( 'log_level', 'info' );
		$levels = array( self::LEVEL_DEBUG, self::LEVEL_INFO, self::LEVEL_WARNING, self::LEVEL_ERROR );
		$current_level_index = array_search( $log_level, $levels, true );

		if ( false === $current_level_index ) {
			return true;
		}

		$message_level_index = array_search( $level, $levels, true );
		return $message_level_index >= $current_level_index;
	}

	/**
	 * Store log entry
	 *
	 * @param array $log_entry Log entry
	 */
	private function store_log( array $log_entry ) {
		// Store in database or file if needed
		// Implementation will be added later
	}
}
