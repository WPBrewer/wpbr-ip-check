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
 * Handles the settings page for IP checking
 */
class IPCheckSettings {
	use SingletonTrait;

	/**
	 * Constructor.
	 */
	protected function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Add settings page under Tools menu
	 *
	 * @return void
	 */
	public function add_settings_page() {
		add_management_page(
			__( 'IP Check', 'wpbr-ip-check' ),
			__( 'IP Check', 'wpbr-ip-check' ),
			'manage_options',
			'wpbr-ip-check',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting( 'wpbr_ip_check_settings', 'wpbr_force_ipv4' );
		register_setting( 'wpbr_ip_check_settings', 'wpbr_http_client' );

		add_settings_section(
			'wpbr_ip_check_section',
			__( 'IP Check Settings', 'wpbr-ip-check' ),
			array( $this, 'render_section_description' ),
			'wpbr_ip_check_settings'
		);

		add_settings_field(
			'wpbr_force_ipv4',
			__( 'Force IPv4', 'wpbr-ip-check' ),
			array( $this, 'render_force_ipv4_field' ),
			'wpbr_ip_check_settings',
			'wpbr_ip_check_section'
		);

		add_settings_field(
			'wpbr_http_client',
			__( 'HTTP Client', 'wpbr-ip-check' ),
			array( $this, 'render_http_client_field' ),
			'wpbr_ip_check_settings',
			'wpbr_ip_check_section'
		);
	}

	/**
	 * Render section description
	 *
	 * @return void
	 */
	public function render_section_description() {
		echo '<p>' . esc_html__( 'Check your server\'s IP address when calling external services. The service will automatically detect and return IPv6 if available, otherwise it will return IPv4.', 'wpbr-ip-check' ) . '</p>';
	}

	/**
	 * Render force IPv4 field
	 *
	 * @return void
	 */
	public function render_force_ipv4_field() {
		$value = get_option( 'wpbr_force_ipv4', 'no' );
		?>
		<label>
			<input type="checkbox" name="wpbr_force_ipv4" value="yes" <?php checked( $value, 'yes' ); ?> />
			<?php esc_html_e( 'Force using IPv4 when checking IP address', 'wpbr-ip-check' ); ?>
		</label>
		<?php
	}

	/**
	 * Render HTTP client field
	 *
	 * @return void
	 */
	public function render_http_client_field() {
		$value = get_option( 'wpbr_http_client', 'wp_remote_get' );
		?>
		<label>
			<input type="radio" name="wpbr_http_client" value="wp_remote_get" <?php checked( $value, 'wp_remote_get' ); ?> />
			<?php esc_html_e( 'WordPress HTTP API (wp_remote_get)', 'wpbr-ip-check' ); ?>
		</label>
		<br>
		<label>
			<input type="radio" name="wpbr_http_client" value="curl" <?php checked( $value, 'curl' ); ?> />
			<?php esc_html_e( 'cURL', 'wpbr-ip-check' ); ?>
		</label>
		<?php
	}

	/**
	 * Render settings page
	 *
	 * @return void
	 */
	public function render_settings_page() {
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'wpbr_ip_check_settings' );
				do_settings_sections( 'wpbr_ip_check_settings' );
				submit_button();
				?>
			</form>

			<h2><?php esc_html_e( 'Check IP', 'wpbr-ip-check' ); ?></h2>
			<p><?php esc_html_e( 'Click the button below to check your server\'s IP address', 'wpbr-ip-check' ); ?></p>
			<button type="button" 
				class="button button-primary" 
				id="wpbr_check_ip_button"
				data-nonce="<?php echo esc_attr( wp_create_nonce( 'wpbr_ip_check_nonce' ) ); ?>">
				<?php esc_html_e( 'Check IP', 'wpbr-ip-check' ); ?>
			</button>
			<div id="wpbr-ip-check-result" style="margin-top: 10px;"></div>

			<?php
			$ip_data = get_option( 'wpbr_ip_check', array() );
			if ( ! empty( $ip_data ) ) :
			?>
				<div class="ip-check-stored-result" style="margin-top: 20px;">
					<h3><?php esc_html_e( 'Last Check Result', 'wpbr-ip-check' ); ?></h3>
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
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Enqueue scripts
	 *
	 * @param string $hook The current admin page.
	 * @return void
	 */
	public function enqueue_scripts( $hook ) {
		if ( 'tools_page_wpbr-ip-check' !== $hook ) {
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
