<?php
/**
 * IP Check Service
 *
 * @package WPBrewer\IPCheck
 */

namespace WPBrewer\IPCheck;

use WPBrewer\IPCheck\Utils\SingletonTrait;
use WPBrewer\IPCheck\Settings\IPCheckSettings;

/**
 * Class IPCheckService
 *
 * Handles IP checking functionality and AJAX requests
 */
class IPCheckService {
	use SingletonTrait;

	/**
	 * API endpoint for IP checking
	 *
	 * @var string
	 */
	private $api_endpoint = 'https://api64.ipify.org?format=json';

	/**
	 * Initialize the service
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'wp_ajax_wpbr_check_ip', array( $this, 'check_ip_handler' ) );
		IPCheckSettings::get_instance();
	}

	/**
	 * Get the current IP address from external API
	 *
	 * @return array
	 */
	public function get_current_ip() {
		$http_client = get_option( 'wpbr_http_client', 'wp_remote_get' );
		$force_ipv4 = 'yes' === get_option( 'wpbr_force_ipv4', 'no' );
		
		if ( 'curl' === $http_client ) {
			$ch = curl_init();
			curl_setopt( $ch, CURLOPT_URL, $this->api_endpoint );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			
			if ( $force_ipv4 ) {
				curl_setopt( $ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );
			}
			
			$response = curl_exec( $ch );
			$error = curl_error( $ch );
			curl_close( $ch );
			
			if ( false === $response ) {
				return array(
					'success' => false,
					'ip'      => '0.0.0.0',
					'error'   => $error,
				);
			}
			
			$data = json_decode( $response, true );
		} else {
			$args = array();
			
			if ( $force_ipv4 ) {
				add_action( 'http_api_curl', function( $handle ) {
					curl_setopt( $handle, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );
				}, 10, 1 );
			}
			
			$response = wp_remote_get( $this->api_endpoint, $args );
			
			if ( is_wp_error( $response ) ) {
				return array(
					'success' => false,
					'ip'      => '0.0.0.0',
					'error'   => $response->get_error_message(),
				);
			}
			
			$data = json_decode( wp_remote_retrieve_body( $response ), true );
		}

		if ( empty( $data['ip'] ) ) {
			return array(
				'success' => false,
				'ip'      => '0.0.0.0',
				'error'   => __( 'Invalid response from IP check service', 'wpbr-ip-check' ),
			);
		}

		// Determine IP version based on the format of the IP address
		$version = filter_var( $data['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ? 'ipv6' : 'ipv4';

		return array(
			'success' => true,
			'ip'      => sanitize_text_field( $data['ip'] ),
			'version' => $version,
		);
	}

	/**
	 * Handle the AJAX request for IP checking
	 *
	 * @return void
	 */
	public function check_ip_handler() {
		check_ajax_referer( 'wpbr_ip_check_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied', 'wpbr-ip-check' ) ) );
		}

		$ip_check = $this->get_current_ip();

		if ( ! $ip_check['success'] ) {
			wp_send_json_error( array( 'message' => $ip_check['error'] ) );
		}

		$ip_data = array(
			'ip'           => $ip_check['ip'],
			'version'      => $ip_check['version'],
			'checked_time' => current_time( 'mysql' ),
		);

		update_option( 'wpbr_ip_check', $ip_data );

		wp_send_json_success( $ip_data );
	}
} 
