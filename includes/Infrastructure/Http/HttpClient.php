<?php
/**
 * HTTP Client
 *
 * @package ZohoConnectSerializer
 */

namespace ZohoConnectSerializer\Infrastructure\Http;

/**
 * HTTP client for making requests
 */
class HttpClient {

	/**
	 * Logger
	 *
	 * @var \ZohoConnectSerializer\Infrastructure\Logging\Logger
	 */
	private $logger;

	/**
	 * Constructor
	 *
	 * @param \ZohoConnectSerializer\Infrastructure\Logging\Logger $logger Logger instance
	 */
	public function __construct( $logger ) {
		$this->logger = $logger;
	}

	/**
	 * Send POST request
	 *
	 * @param string $url Request URL
	 * @param array  $data Request data
	 * @param array  $headers Request headers
	 * @return array
	 */
	public function post( $url, array $data, array $headers = array() ) {
		$default_headers = array(
			'Content-Type' => 'application/json',
		);

		$headers = array_merge( $default_headers, $headers );

		$args = array(
			'method'      => 'POST',
			'timeout'     => 30,
			'headers'     => $headers,
			'body'        => wp_json_encode( $data ),
			'data_format' => 'body',
		);

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			$this->logger->error( 'HTTP request failed', array(
				'url' => $url,
				'error' => $response->get_error_message(),
			) );

			return array(
				'success' => false,
				'error'   => $response->get_error_message(),
			);
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		$decoded_body = json_decode( $response_body, true );

		return array(
			'success' => $response_code >= 200 && $response_code < 300,
			'code'    => $response_code,
			'data'    => $decoded_body ? $decoded_body : $response_body,
		);
	}

	/**
	 * Send GET request
	 *
	 * @param string $url Request URL
	 * @param array  $headers Request headers
	 * @return array
	 */
	public function get( $url, array $headers = array() ) {
		$args = array(
			'method'  => 'GET',
			'timeout' => 30,
			'headers' => $headers,
		);

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'error'   => $response->get_error_message(),
			);
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		return array(
			'success' => $response_code >= 200 && $response_code < 300,
			'code'    => $response_code,
			'data'    => json_decode( $response_body, true ),
		);
	}
}
