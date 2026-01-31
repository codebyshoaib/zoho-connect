<?php
/**
 * Plugin Updater Class
 *
 * Handles automatic updates from GitHub releases using Plugin Update Checker
 *
 * @package ZohoConnectSerializer
 */

namespace ZohoConnectSerializer\Infrastructure\Updater;

/**
 * Plugin Updater
 */
class PluginUpdater {

	/**
	 * GitHub repository owner
	 *
	 * @var string
	 */
	private $github_username;

	/**
	 * GitHub repository name
	 *
	 * @var string
	 */
	private $github_repo;

	/**
	 * Plugin file path
	 *
	 * @var string
	 */
	private $plugin_file;

	/**
	 * Update checker instance
	 *
	 * @var \YahnisElsts\PluginUpdateChecker\v5p6\Plugin\UpdateChecker
	 */
	private $update_checker;

	/**
	 * Constructor
	 *
	 * @param string $plugin_file Path to main plugin file.
	 * @param string $github_username GitHub username/organization.
	 * @param string $github_repo GitHub repository name.
	 */
	public function __construct( $plugin_file, $github_username, $github_repo ) {
		$this->plugin_file     = $plugin_file;
		$this->github_username  = $github_username;
		$this->github_repo      = $github_repo;
	}

	/**
	 * Initialize the updater
	 */
	public function init() {
		// Check if Plugin Update Checker is available
		if ( ! class_exists( '\YahnisElsts\PluginUpdateChecker\v5\PucFactory' ) ) {
			// Try to load from vendor directory (Composer)
			$update_checker_path = ZOHO_CONNECT_SERIALIZER_PLUGIN_DIR . 'vendor/yahnis-elsts/plugin-update-checker/plugin-update-checker.php';
			
			if ( file_exists( $update_checker_path ) ) {
				require_once $update_checker_path;
			} else {
				// Fallback: try to load from includes if bundled
				$bundled_path = ZOHO_CONNECT_SERIALIZER_PLUGIN_DIR . 'includes/Infrastructure/Updater/plugin-update-checker/plugin-update-checker.php';
				if ( file_exists( $bundled_path ) ) {
					require_once $bundled_path;
				} else {
					return; // Library not found, skip update checker
				}
			}
		}

		// Build GitHub repository URL
		$github_url = sprintf(
			'https://github.com/%s/%s',
			$this->github_username,
			$this->github_repo
		);

		// Initialize the update checker using the namespaced factory
		$this->update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
			$github_url,
			$this->plugin_file,
			ZOHO_CONNECT_SERIALIZER_PLUGIN_BASENAME
		);

		// Set to check for releases (tags)
		$this->update_checker->getVcsApi()->enableReleaseAssets();

		// Optional: Set branch (defaults to 'master' or 'main')
		// $this->update_checker->setBranch('main');
	}

	/**
	 * Get update checker instance
	 *
	 * @return \YahnisElsts\PluginUpdateChecker\v5p6\Plugin\UpdateChecker|null
	 */
	public function get_update_checker() {
		return $this->update_checker;
	}
}
