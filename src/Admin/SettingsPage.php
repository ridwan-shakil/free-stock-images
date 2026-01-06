<?php

namespace FreeStockImages\Admin;

if (! defined('ABSPATH')) {
	exit;
}

/**
 * This class adds settings fields into (Free Stock Images) settings page.
 * page location: admin > settings > Free Stock Images
 */
class SettingsPage {

	/**
	 * Option names for API keys
	 */
	const OPTION_UNSPLASH = 'fsi_unsplash_key';
	const OPTION_PIXABAY  = 'fsi_pixabay_key';
	const OPTION_PEXELS   = 'fsi_pexels_key';

	/**
	 * Initialize hooks
	 */
	public function __construct() {
		add_action('admin_init', [$this, 'register_settings']);
	}

	/**
	 * Register settings fields
	 */
	public function register_settings() {
		// Unsplash.
		register_setting('fsi_settings_group', self::OPTION_UNSPLASH, [
			'type' => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default' => '',
		]);

		add_settings_section(
			'fsi_api_section',
			esc_html__('API Keys', 'free-stock-images'),
			null,
			'fsi-settings'
		);

		add_settings_field(
			self::OPTION_UNSPLASH,
			esc_html__('Unsplash API Key', 'free-stock-images'),
			[$this, 'render_input_field'],
			'fsi-settings',
			'fsi_api_section',
			['option_name' => self::OPTION_UNSPLASH, 'get_key_url' => 'https://unsplash.com/developers']
		);

		// Pixabay
		register_setting('fsi_settings_group', self::OPTION_PIXABAY, [
			'type' => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default' => '',
		]);

		add_settings_field(
			self::OPTION_PIXABAY,
			esc_html__('Pixabay API Key', 'free-stock-images'),
			[$this, 'render_input_field'],
			'fsi-settings',
			'fsi_api_section',
			['option_name' => self::OPTION_PIXABAY, 'get_key_url' => 'https://pixabay.com/api/docs/']
		);

		// Pexels
		register_setting('fsi_settings_group', self::OPTION_PEXELS, [
			'type' => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default' => '',
		]);

		add_settings_field(
			self::OPTION_PEXELS,
			esc_html__('Pexels API Key', 'free-stock-images'),
			[$this, 'render_input_field'],
			'fsi-settings',
			'fsi_api_section',
			['option_name' => self::OPTION_PEXELS, 'get_key_url' => 'https://www.pexels.com/api/']
		);
	}

	/**
	 * Render input field with "Get API key" link
	 */
	public function render_input_field($args) {
		$option_name = $args['option_name'];
		$get_key_url = $args['get_key_url'];

		$value = get_option($option_name, '');
?>
		<input type="text" name="<?php echo esc_attr($option_name); ?>" value="<?php echo esc_attr($value); ?>"
			class="regular-text" />
		<a href="<?php echo esc_url($get_key_url); ?>" target="_blank"
			style="margin-left:10px;"><?php esc_html_e('→ Get API key', 'free-stock-images'); ?></a>
		<p class="description"><?php esc_html_e('Leave empty to use the plugin\'s default demo key.', 'free-stock-images'); ?>
		</p>
	<?php
	}

	/**
	 * Render settings page
	 */
	public function render_page() {
		if (! current_user_can('manage_options')) {
			return;
		}
	?>
		<div class="wrap">
			<h1><?php esc_html_e('Free Stock Images Settings', 'free-stock-images'); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields('fsi_settings_group');
				do_settings_sections('fsi-settings');
				submit_button();
				?>
			</form>
		</div>
<?php
	}
}
