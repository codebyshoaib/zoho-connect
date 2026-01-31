<?php
/**
 * Autoloader for Zoho Connect Serializer
 *
 * @package ZohoConnectSerializer
 */

namespace ZohoConnectSerializer\Includes;

/**
 * PSR-4 Autoloader
 */
class Autoloader {

	/**
	 * Namespace prefix
	 *
	 * @var string
	 */
	private $prefix = 'ZohoConnectSerializer\\';

	/**
	 * Base directory for namespace prefix
	 *
	 * @var string
	 */
	private $base_dir;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->base_dir = ZOHO_CONNECT_SERIALIZER_PLUGIN_DIR . 'includes' . DIRECTORY_SEPARATOR;
	}

	/**
	 * Register autoloader
	 */
	public function register() {
		spl_autoload_register( array( $this, 'load_class' ) );
	}

	/**
	 * Load class file
	 *
	 * @param string $class Fully qualified class name
	 */
	public function load_class( $class ) {
		// Does the class use the namespace prefix?
		$len = strlen( $this->prefix );
		if ( strncmp( $this->prefix, $class, $len ) !== 0 ) {
			return;
		}

		// Get the relative class name
		$relative_class = substr( $class, $len );

		// Replace namespace separators with directory separators
		$file = $this->base_dir . str_replace( '\\', DIRECTORY_SEPARATOR, $relative_class ) . '.php';

		// If the file exists, require it
		if ( file_exists( $file ) ) {
			require $file;
		}
	}
}
