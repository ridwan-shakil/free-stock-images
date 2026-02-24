<?php

namespace FreeStockImages\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and renders plugin settings.
 */
class SettingsPage {
	const OPTION_UNSPLASH = 'fsi_unsplash_key';
	const OPTION_PIXABAY  = 'fsi_pixabay_key';
	const OPTION_PEXELS   = 'fsi_pexels_key';

	public function __construct() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'fsi_settings_group',
			self::OPTION_UNSPLASH,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);

		register_setting(
			'fsi_settings_group',
			self::OPTION_PIXABAY,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);

		register_setting(
			'fsi_settings_group',
			self::OPTION_PEXELS,
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);

		add_settings_section(
			'fsi_api_section',
			esc_html__( 'API Keys', 'free-stock-images' ),
			array( $this, 'render_section_description' ),
			'fsi-settings'
		);

		add_settings_field(
			self::OPTION_UNSPLASH,
			esc_html__( 'Unsplash API Key', 'free-stock-images' ),
			array( $this, 'render_input_field' ),
			'fsi-settings',
			'fsi_api_section',
			array(
				'option_name' => self::OPTION_UNSPLASH,
				'get_key_url' => 'https://unsplash.com/developers',
				'required'    => true,
			)
		);

		add_settings_field(
			self::OPTION_PIXABAY,
			esc_html__( 'Pixabay API Key', 'free-stock-images' ),
			array( $this, 'render_input_field' ),
			'fsi-settings',
			'fsi_api_section',
			array(
				'option_name' => self::OPTION_PIXABAY,
				'get_key_url' => 'https://pixabay.com/api/docs/',
				'required'    => false,
			)
		);

		add_settings_field(
			self::OPTION_PEXELS,
			esc_html__( 'Pexels API Key', 'free-stock-images' ),
			array( $this, 'render_input_field' ),
			'fsi-settings',
			'fsi_api_section',
			array(
				'option_name' => self::OPTION_PEXELS,
				'get_key_url' => 'https://www.pexels.com/api/',
				'required'    => false,
			)
		);
		
	}

	/**
	 * @return void
	 */
	public function render_section_description() {
		echo '<p>' . esc_html__( 'Configure provider keys for higher reliability and quota. Unsplash requires your own key.', 'free-stock-images' ) . '</p>';
	}

	/**
	 * @param array $args Field args.
	 * @return void
	 */
	public function render_input_field( $args ) {
		$option_name = isset( $args['option_name'] ) ? (string) $args['option_name'] : '';
		$get_key_url = isset( $args['get_key_url'] ) ? (string) $args['get_key_url'] : '';
		$required    = ! empty( $args['required'] );
		$value       = get_option( $option_name, '' );
		$is_enabled  = '' !== trim( (string) $value ) || ! $required;
		$status_text = $is_enabled ? __( 'Enabled', 'free-stock-images' ) : __( 'Needs key', 'free-stock-images' );
		$status_css  = $is_enabled ? 'color:#008a20;font-weight:600;' : 'color:#b32d2e;font-weight:600;';
		?>
		<input type="text" name="<?php echo esc_attr( $option_name ); ?>" value="<?php echo esc_attr( (string) $value ); ?>" class="regular-text" />
		<a href="<?php echo esc_url( $get_key_url ); ?>" target="_blank" rel="noopener noreferrer" style="margin-left:10px;"><?php esc_html_e( 'Get API key', 'free-stock-images' ); ?></a>
		<p class="description" style="<?php echo esc_attr( $status_css ); ?>"><?php echo esc_html( $status_text ); ?></p>
		<?php if ( $required ) : ?>
			<p class="description"><?php esc_html_e( 'Required: this source will stay disabled until a key is saved.', 'free-stock-images' ); ?></p>
		<?php else : ?>
			<p class="description"><?php esc_html_e( 'Optional: plugin fallback key is used when empty.', 'free-stock-images' ); ?></p>
		<?php endif; ?>
		<?php
	}

	/**
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Free Stock Images Settings', 'free-stock-images' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'fsi_settings_group' );
				do_settings_sections( 'fsi-settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
