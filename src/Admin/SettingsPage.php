<?php
/**
 * Registers and renders plugin settings.
 *
 * @package FreeStockImages
 * @since 1.0.0
 */

namespace FreeStockImages\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and renders plugin settings.
 */
class SettingsPage {
	const OPTION_UNSPLASH = 'fsimgs_unsplash_key';
	const OPTION_PIXABAY  = 'fsimgs_pixabay_key';
	const OPTION_PEXELS   = 'fsimgs_pexels_key';

	/**
	 * Constructor. Hooks the settings registration into admin_init.
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Registers the plugin settings.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'fsimgs_settings_group',
			self::OPTION_UNSPLASH,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);

		register_setting(
			'fsimgs_settings_group',
			self::OPTION_PIXABAY,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);

		register_setting(
			'fsimgs_settings_group',
			self::OPTION_PEXELS,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);

		add_settings_section(
			'fsimgs_api_section',
			esc_html__( 'API Keys', 'plugmint-stock-images' ),
			array( $this, 'render_section_description' ),
			'fsimgs-settings'
		);

		add_settings_field(
			self::OPTION_UNSPLASH,
			esc_html__( 'Unsplash API Key', 'plugmint-stock-images' ),
			array( $this, 'render_input_field' ),
			'fsimgs-settings',
			'fsimgs_api_section',
			array(
				'option_name' => self::OPTION_UNSPLASH,
				'get_key_url' => 'https://unsplash.com/developers',
				'required'    => true,
			)
		);

		add_settings_field(
			self::OPTION_PIXABAY,
			esc_html__( 'Pixabay API Key', 'plugmint-stock-images' ),
			array( $this, 'render_input_field' ),
			'fsimgs-settings',
			'fsimgs_api_section',
			array(
				'option_name' => self::OPTION_PIXABAY,
				'get_key_url' => 'https://pixabay.com/api/docs/',
				'required'    => false,
			)
		);

		add_settings_field(
			self::OPTION_PEXELS,
			esc_html__( 'Pexels API Key', 'plugmint-stock-images' ),
			array( $this, 'render_input_field' ),
			'fsimgs-settings',
			'fsimgs_api_section',
			array(
				'option_name' => self::OPTION_PEXELS,
				'get_key_url' => 'https://www.pexels.com/api/',
				'required'    => false,
			)
		);
	}

	/**
	 * Renders the description for the API keys section.
	 *
	 * @return void
	 */
	public function render_section_description() {
		echo '<p>' . esc_html__( 'Configure provider keys for higher reliability and quota. Unsplash requires your own key.', 'plugmint-stock-images' ) . '</p>';
	}

	/**
	 * Renders an input field for a given setting.
	 *
	 * @param array $args Field args.
	 * @return void
	 */
	public function render_input_field( $args ) {
		$option_name = isset( $args['option_name'] ) ? (string) $args['option_name'] : '';
		$get_key_url = isset( $args['get_key_url'] ) ? (string) $args['get_key_url'] : '';
		$required    = ! empty( $args['required'] );
		$value       = get_option( $option_name, '' );
		$is_enabled  = '' !== trim( (string) $value ) || ! $required;
		$status_text = $is_enabled ? __( 'Enabled', 'plugmint-stock-images' ) : __( 'Needs key', 'plugmint-stock-images' );
		$status_css  = $is_enabled ? 'color:#008a20;font-weight:600;' : 'color:#b32d2e;font-weight:600;';
		?>
		<input type="text" name="<?php echo esc_attr( $option_name ); ?>" value="<?php echo esc_attr( (string) $value ); ?>" class="regular-text" />
		<a href="<?php echo esc_url( $get_key_url ); ?>" target="_blank" rel="noopener noreferrer" style="margin-left:10px;"><?php esc_html_e( 'Get API key', 'plugmint-stock-images' ); ?></a>
		<p class="description" style="<?php echo esc_attr( $status_css ); ?>"><?php echo esc_html( $status_text ); ?></p>
		<?php if ( $required ) : ?>
			<p class="description"><?php esc_html_e( 'Required: this source will stay disabled until a key is saved.', 'plugmint-stock-images' ); ?></p>
		<?php else : ?>
			<p class="description"><?php esc_html_e( 'Optional: plugin fallback key is used when empty.', 'plugmint-stock-images' ); ?></p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Renders the settings page. This method is called as a callback when the settings submenu is accessed.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Free Stock Images Settings', 'plugmint-stock-images' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'fsimgs_settings_group' );
				do_settings_sections( 'fsimgs-settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
