<?php
/**
 * Main Plugin Class
 *
 * @package ZohoConnectSerializer
 */

namespace ZohoConnectSerializer\Core;

use ZohoConnectSerializer\Core\DependencyInjection\Container;
use ZohoConnectSerializer\Infrastructure\WordPress\Hooks\HookManager;

/**
 * Main plugin class
 */
class Plugin {

	/**
	 * Plugin instance
	 *
	 * @var Plugin
	 */
	private static $instance = null;

	/**
	 * Dependency injection container
	 *
	 * @var Container
	 */
	private $container;

	/**
	 * Hook manager
	 *
	 * @var HookManager
	 */
	private $hook_manager;

	/**
	 * Get plugin instance
	 *
	 * @return Plugin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->container = new Container();
		$this->hook_manager = new HookManager();
		$this->register_services();
		$this->register_hooks();
	}

	/**
	 * Register services in container
	 */
	private function register_services() {
		// Configuration
		$this->container->bind( 'config', function() {
			return new \ZohoConnectSerializer\Core\Config();
		} );

		// Logger
		$this->container->bind( 'logger', function() {
			return new \ZohoConnectSerializer\Infrastructure\Logging\Logger();
		} );

		// Debug Service
		$this->container->bind( 'debug_service', function() {
			return new \ZohoConnectSerializer\Infrastructure\Debug\DebugService(
				$this->container->make( 'config' ),
				$this->container->make( 'logger' )
			);
		} );

		// API Router
		$this->container->bind( 'api_router', function() {
			return new \ZohoConnectSerializer\Infrastructure\API\Router(
				$this->container->make( 'config' )
			);
		} );

		// Webhook Service
		$this->container->bind( 'webhook_service', function() {
			return new \ZohoConnectSerializer\Domain\Webhook\Services\ZohoFlowWebhookService(
				$this->container->make( 'config' ),
				$this->container->make( 'logger' ),
				$this->container->make( 'http_client' )
			);
		} );

		// HTTP Client
		$this->container->bind( 'http_client', function() {
			return new \ZohoConnectSerializer\Infrastructure\Http\HttpClient(
				$this->container->make( 'logger' )
			);
		} );

		// Serialization Service
		$this->container->bind( 'serialization_service', function() {
			return new \ZohoConnectSerializer\Domain\Serialization\Services\SerializationService(
				$this->container->make( 'logger' )
			);
		} );

		// Booking Payload Repository
		$this->container->bind( 'booking_payload_repository', function() {
			return new \ZohoConnectSerializer\Domain\Booking\Repositories\BookingPayloadRepository(
				$this->container->make( 'logger' )
			);
		} );

		// Booking Service
		$this->container->bind( 'booking_service', function() {
			return new \ZohoConnectSerializer\Domain\Booking\Services\BookingService(
				$this->container->make( 'booking_payload_repository' ),
				$this->container->make( 'serialization_service' ),
				$this->container->make( 'webhook_service' ),
				$this->container->make( 'logger' )
			);
		} );

		// CRBS Integration
		$this->container->bind( 'crbs_integration', function() {
			return new \ZohoConnectSerializer\Domain\CRBS\Integrations\CRBSIntegration(
				$this->container->make( 'booking_service' ),
				$this->container->make( 'logger' )
			);
		} );

		// API Controllers
		$this->container->bind( 'booking_controller', function() {
			return new \ZohoConnectSerializer\Infrastructure\API\Controllers\BookingController(
				$this->container->make( 'booking_service' ),
				$this->container->make( 'logger' )
			);
		} );
	}

	/**
	 * Register WordPress hooks
	 */
	private function register_hooks() {
		// Initialize CRBS integration (must be on plugins_loaded)
		$this->hook_manager->add_action( 'plugins_loaded', array( $this, 'init_crbs_integration' ), 20 );

		// Register REST API routes
		$this->hook_manager->add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		// Register admin hooks
		if ( is_admin() ) {
			$this->hook_manager->add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
			$this->hook_manager->add_action( 'admin_init', array( $this, 'register_admin_settings' ) );
		}
	}

	/**
	 * Initialize CRBS integration
	 */
	public function init_crbs_integration() {
		$integration = $this->container->make( 'crbs_integration' );
		$integration->init();
	}

	/**
	 * Register REST API routes
	 */
	public function register_rest_routes() {
		$router = $this->container->make( 'api_router' );
		$router->register_routes();
	}

	/**
	 * Register admin menu
	 */
	public function register_admin_menu() {
		$admin_page = new \ZohoConnectSerializer\Infrastructure\Admin\AdminPage(
			$this->container->make( 'config' )
		);
		$admin_page->register();
	}

	/**
	 * Register admin settings
	 */
	public function register_admin_settings() {
		$settings = new \ZohoConnectSerializer\Infrastructure\Admin\Settings(
			$this->container->make( 'config' )
		);
		$settings->register();
	}

	/**
	 * Run the plugin
	 */
	public function run() {
		$this->hook_manager->run();
	}

	/**
	 * Get container
	 *
	 * @return Container
	 */
	public function get_container() {
		return $this->container;
	}
}
