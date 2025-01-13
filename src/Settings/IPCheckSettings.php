<?php
/**
 * IP Check Settings Page
 *
 * @package WPBrewer\IPCheck\Settings
 */

namespace WPBrewer\IPCheck\Settings;

use WPBrewer\IPCheck\Utils\SingletonTrait;

/**
 * Class IPCheckSettings
 *
 * Handles the WooCommerce settings page for IP checking
 */
class IPCheckSettings extends \WC_Settings_Page {
	use SingletonTrait;

	/**
	 * Constructor.
	 */
	protected function __construct() {
		$this->id    = 'wpbr_ip_check';
		$this->label = __( 'IP Check', 'wpbr-ip-check' );

		parent::__construct();

		add_action( 'woocommerce_admin_field_check_ip_button', array( $this, 'output_check_ip_button' ) );
		add_action( 'woocommerce_admin_field_ip_check_result', array( $this, 'output_ip_check_result' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Get settings array
	 *
	 * @return array
	 */
	public function get_settings() {
		$settings = array(
			array(
				'title' => __( 'IP Check Settings', 'wpbr-ip-check' ),
				'type'  => 'title',
				'desc'  => __( 'Check your server\'s IP address when calling external services. The service will automatically detect and return IPv6 if available, otherwise it will return IPv4.', 'wpbr-ip-check' ),
				'id'    => 'wpbr_ip_check_section',
			),
			array(
				'title'       => __( 'Force IPv4', 'wpbr-ip-check' ),
				'type'        => 'checkbox',
				'desc'        => __( 'Force using IPv4 when checking IP address', 'wpbr-ip-check' ),
				'id'          => 'wpbr_force_ipv4',
				'default'     => 'no',
			),
			array(
				'title'       => __( 'Check IP', 'wpbr-ip-check' ),
				'type'        => 'check_ip_button',
				'desc'        => __( 'Click to check your server\'s IP address', 'wpbr-ip-check' ),
				'id'          => 'wpbr_check_ip_button',
				'desc_tip'    => true,
			),
			array(
				'title' => __( 'Last Check Result', 'wpbr-ip-check' ),
				'type'  => 'ip_check_result',
				'id'    => 'wpbr_ip_check_result_field',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'wpbr_ip_check_section',
			),
		);

		return apply_filters( 'wpbr_ip_check_settings', $settings );
	}

	/**
	 * Output the check IP button
	 *
	 * @param array $value Field value.
	 * @return void
	 */
	public function output_check_ip_button( $value ) {
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
				<?php echo wc_help_tip( $value['desc'] ); ?>
			</th>
			<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
				<button type="button" 
					class="button button-primary" 
					id="<?php echo esc_attr( $value['id'] ); ?>"
					data-nonce="<?php echo esc_attr( wp_create_nonce( 'wpbr_ip_check_nonce' ) ); ?>">
					<?php echo esc_html( $value['title'] ); ?>
				</button>
				<div id="wpbr-ip-check-result" style="margin-top: 10px;"></div>
			</td>
		</tr>
		<?php
	}

	/**
	 * Output the IP check result
	 *
	 * @param array $value Field value.
	 * @return void
	 */
	public function output_ip_check_result( $value ) {
		$ip_data = get_option( 'wpbr_ip_check', array() );
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label><?php echo esc_html( $value['title'] ); ?></label>
			</th>
			<td class="forminp">
				<?php if ( ! empty( $ip_data ) ) : ?>
					<div class="ip-check-stored-result">
						<p>
							<strong><?php esc_html_e( 'IP Address:', 'wpbr-ip-check' ); ?></strong>
							<?php echo esc_html( $ip_data['ip'] ); ?>
							<span class="ip-version">(<?php echo esc_html( strtoupper( $ip_data['version'] ) ); ?>)</span>
						</p>
						<p>
							<strong><?php esc_html_e( 'Last Checked:', 'wpbr-ip-check' ); ?></strong>
							<?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $ip_data['checked_time'] ) ) ); ?>
						</p>
					</div>
				<?php else : ?>
					<p class="description"><?php esc_html_e( 'No IP check results available yet.', 'wpbr-ip-check' ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Enqueue scripts
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		$screen = get_current_screen();
		if ( 'woocommerce_page_wc-settings' !== $screen->id ) {
			return;
		}

		wp_enqueue_script(
			'wpbr-ip-check',
			WPBR_IP_CHECK_PLUGIN_URL . 'src/assets/js/ip-check.js',
			array( 'jquery' ),
			WPBR_IP_CHECK_VERSION,
			true
		);
	}
} 
