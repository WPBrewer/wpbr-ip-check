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
		add_filter( 'woocommerce_get_settings_pages', array( $this, 'add_settings_page' ) );
	}

	/**
	 * Add settings page to WooCommerce settings
	 *
	 * @param array $settings Array of WC_Settings_Page classes.
	 * @return array
	 */
	public function add_settings_page( $settings ) {
		$settings[] = IPCheckSettings::get_instance();
		return $settings;
	}

	/**
	 * Get the current IP address from external API
	 *
	 * @return array
	 */
	public function get_current_ip() {
		$args = array();
		
		// Force IPv4 if enabled in settings
		if ( 'yes' === get_option( 'wpbr_force_ipv4', 'no' ) ) {
			//force curl to use ipv4
			add_action( 'http_api_curl', function( $handle, $r, $url ) {
				curl_setopt( $handle, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );
			}, 10, 3);
		}

		$response = wp_remote_get( $this->api_endpoint, $args );

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'ip'      => '0.0.0.0',
				'error'   => $response->get_error_message(),
			);
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

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

	/**
	 * Get the stored IP check data
	 *
	 * @return array
	 */
	private function get_stored_ip_data() {
		$default = array(
			'ip'           => '',
			'version'      => '',
			'checked_time' => '',
		);

		return get_option( 'wpbr_ip_check', $default );
	}
} 
