<?php
/**
 * Zoho Flow Webhook Service
 *
 * @package ZohoConnectSerializer
 */

namespace ZohoConnectSerializer\Domain\Webhook\Services;

use ZohoConnectSerializer\Core\Config;
use ZohoConnectSerializer\Infrastructure\Http\HttpClient;

/**
 * Service for sending webhooks to Zoho Flow
 */
class ZohoFlowWebhookService {

	/**
	 * Configuration
	 *
	 * @var Config
	 */
	private $config;

	/**
	 * Logger
	 *
	 * @var \ZohoConnectSerializer\Infrastructure\Logging\Logger
	 */
	private $logger;

	/**
	 * HTTP client
	 *
	 * @var HttpClient
	 */
	private $http_client;

	/**
	 * Constructor
	 *
	 * @param Config      $config Configuration instance
	 * @param \ZohoConnectSerializer\Infrastructure\Logging\Logger $logger Logger instance
	 * @param HttpClient  $http_client HTTP client instance
	 */
	public function __construct( Config $config, $logger, HttpClient $http_client ) {
		$this->config = $config;
		$this->logger = $logger;
		$this->http_client = $http_client;
	}

	/**
	 * Send payload to Zoho Flow webhook
	 *
	 * @param array $payload Serialized payload
	 * @return array Response data
	 * @throws \Exception
	 */
	public function send( array $payload ) {
		$webhook_url = $this->config->get( 'zoho_flow_webhook_url' );

		if ( empty( $webhook_url ) ) {
			throw new \Exception( 'Zoho Flow webhook URL is not configured' );
		}

		$retry_attempts = $this->config->get( 'retry_attempts', 3 );
		$retry_delay = $this->config->get( 'retry_delay', 5 );

		$attempt = 0;
		$last_error = null;

		while ( $attempt < $retry_attempts ) {
			try {
				$response = $this->http_client->post( $webhook_url, $payload );

				if ( $response['success'] ) {
					$this->logger->info( 'Webhook sent successfully', array(
						'attempt' => $attempt + 1,
						'response_code' => $response['code'] ?? null,
					) );

					return $response['data'];
				}

				$last_error = $response['error'] ?? 'Unknown error';

			} catch ( \Exception $e ) {
				$last_error = $e->getMessage();
			}

			$attempt++;

			if ( $attempt < $retry_attempts ) {
				$this->logger->warning( 'Webhook send failed, retrying', array(
					'attempt' => $attempt,
					'error' => $last_error,
					'next_retry_in' => $retry_delay . ' seconds',
				) );

				sleep( $retry_delay );
			}
		}

		throw new \Exception( 'Failed to send webhook after ' . $retry_attempts . ' attempts: ' . $last_error );
	}
}
