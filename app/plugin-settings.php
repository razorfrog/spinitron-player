<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Initializes the Spinitron Player plugin settings by registering settings, sections, and fields.
 * @return void
 */
function Spinitron_Player_Settings_init() {
		register_setting('spinitron_player', 'spinitron_player_options');

		add_settings_section(
				'spinitron_player_section_developers',
				__('Source Settings', 'spinitron-player'),
				'Spinitron_Player_Section_Developers_cb',
				'spinitron_player'
		);

		add_settings_field(
				'spinitron_player_field_api_key',
				__('API Key (required)', 'spinitron-player'),
				'Spinitron_Player_Field_Api_Key_cb',
				'spinitron_player',
				'spinitron_player_section_developers',
				array(
						'label_for' => 'spinitron_player_field_api_key',
						'class' => 'spinitron_player_row',
						'spinitron_player_custom_data' => 'custom',
				)
		);

		add_settings_field(
				'spinitron_player_field_image_fallback',
				__('Image Fallback (required)', 'spinitron-player'),
				'Spinitron_Player_Field_Image_Fallback_cb',
				'spinitron_player',
				'spinitron_player_section_developers',
				array(
						'label_for' => 'spinitron_player_field_image_fallback',
						'class' => 'spinitron_player_row',
						'spinitron_player_custom_data' => 'custom',
				)
		);

		add_settings_field(
				'spinitron_player_field_stream_url',
				__('Livestream URL', 'spinitron-player'),
				'Spinitron_Player_Field_Stream_Url_cb',
				'spinitron_player',
				'spinitron_player_section_developers',
				array(
						'label_for' => 'spinitron_player_field_stream_url',
						'class' => 'spinitron_player_row',
						'spinitron_player_custom_data' => 'custom',
				)
		);

		add_settings_field(
				'spinitron_player_field_separate_time_dj',
				__('Separate Time and DJ elements', 'spinitron-player'),
				'Spinitron_Player_Field_Separate_Time_DJ_cb',
				'spinitron_player',
				'spinitron_player_section_developers',
				array(
						'label_for' => 'spinitron_player_field_separate_time_dj',
						'class' => 'spinitron_player_row',
						'spinitron_player_custom_data' => 'custom',
				)
		);

		add_settings_field(
				'spinitron_player_field_duplicate_show_image',
				__('Duplicate Show Image tag', 'spinitron-player'),
				'Spinitron_Player_Field_Duplicate_Show_Image_cb',
				'spinitron_player',
				'spinitron_player_section_developers',
				array(
						'label_for' => 'spinitron_player_field_duplicate_show_image',
						'class' => 'spinitron_player_row',
						'spinitron_player_custom_data' => 'custom',
				)
		);
}
add_action('admin_init', 'Spinitron_Player_Settings_init');

/**
 * Registers the Spinitron Player settings page in the WordPress admin area.
 * @return void
 */
function Spinitron_Player_Options_page() {
		add_options_page(
				__('Spinitron Player', 'spinitron-player'),
				__('Spinitron Player', 'spinitron-player'),
				'manage_options',
				'spinitron_player',
				'Spinitron_Player_Options_Page_html'
		);
}
add_action('admin_menu', 'Spinitron_Player_Options_page');

/**
 * Renders the HTML for the Spinitron Player options page in the admin area.
 * @return void
 */
function Spinitron_Player_Options_Page_html() {
		if (!current_user_can('manage_options')) {
				return;
		}

		settings_errors('spinitron_player_messages');

		?>
		<div class="wrap">
				<h1><?php echo esc_html(get_admin_page_title()); ?></h1>
				<form action="options.php" method="post">
						<?php
						settings_fields('spinitron_player');
						do_settings_sections('spinitron_player');
						submit_button(__('Save Settings', 'spinitron-player'));
						?>
				</form>
		</div>
		<?php
}

/**
 * Enqueue custom styles for the Spinitron Player settings page.
 * @param string $hook_suffix The current admin page.
 * @return void
 */
function spinitron_player_admin_styles($hook_suffix) {
		// Check if we're on the Spinitron Player settings page
		if ($hook_suffix == 'settings_page_spinitron_player') {
				wp_add_inline_style('wp-admin', '
						.form-table {
								max-width: 1000px;
						}
						.form-table th {
								width: 250px;
						}
						input[type=date],
						input[type=datetime-local],
						input[type=datetime],
						input[type=email],
						input[type=month],
						input[type=number],
						input[type=password],
						input[type=search],
						input[type=tel],
						input[type=text],
						input[type=time],
						input[type=url],
						input[type=week] {
								width: 100% !important;
						}
				');
		}
}
add_action('admin_enqueue_scripts', 'spinitron_player_admin_styles');

/**
 * Outputs the developers section description for Spinitron Player settings.
 * @param array $args Configuration for the section.
 * @return void
 */
function Spinitron_Player_Section_Developers_cb($args) {
		?>
		<p id="<?php echo esc_attr($args['id']); ?>"><?php esc_html_e('Enter your API key and stream URL below:', 'spinitron-player'); ?></p>
		<?php
}

/**
 * Renders the API Key input field in plugin settings.
 * @param array $args Configuration for the field.
 * @return void
 */
function Spinitron_Player_Field_Api_Key_cb($args) {
		$options = get_option('spinitron_player_options');
		?>
		<input type="text"
					 id="<?php echo esc_attr($args['label_for']); ?>"
					 data-custom="<?php echo esc_attr($args['spinitron_player_custom_data']); ?>"
					 name="spinitron_player_options[<?php echo esc_attr($args['label_for']); ?>]"
					 value="<?php echo esc_attr($options[$args['label_for']] ?? ''); ?>"
					 style="max-width: 100%; width: 500px;"
					 required>
		<p class="description"><?php esc_html_e('Find the API key at spinitron.com, under Admin > Automation & API > Control Panel.', 'spinitron-player'); ?></p>
		<?php

		// Clear the transient cache when the API key is updated
		delete_transient('spinitron_show_today');
}

/**
 * Outputs the Image Fallback input field in plugin settings.
 * @param array $args Configuration for the field.
 * @return void
 */
function Spinitron_Player_Field_Image_Fallback_cb($args) {
		$options = get_option('spinitron_player_options');
		$fallback_image = isset($options[$args['label_for']]) ? $options[$args['label_for']] : '';
		?>
		<input type="text"
					 id="<?php echo esc_attr($args['label_for']); ?>"
					 data-custom="<?php echo esc_attr($args['spinitron_player_custom_data']); ?>"
					 name="spinitron_player_options[<?php echo esc_attr($args['label_for']); ?>]"
					 value="<?php echo esc_attr($fallback_image); ?>"
					 style="max-width: 100%; width: 500px;"
					 required>
		<p class="description"><?php esc_html_e('Enter the URL of the fallback image to use when the player image is not available or broken.', 'spinitron-player'); ?></p>
		<?php
}

/**
 * Renders the Stream URL input field in plugin settings.
 * @param array $args Configuration for the field.
 * @return void
 */
function Spinitron_Player_Field_Stream_Url_cb($args) {
		$options = get_option('spinitron_player_options');
		?>
		<input type="url"
					 id="<?php echo esc_attr($args['label_for']); ?>"
					 data-custom="<?php echo esc_attr($args['spinitron_player_custom_data']); ?>"
					 name="spinitron_player_options[<?php echo esc_attr($args['label_for']); ?>]"
					 value="<?php echo esc_attr($options[$args['label_for']] ?? ''); ?>"
					 style="max-width: 100%; width: 500px;">
		<p class="description"><?php esc_html_e('Enter the URL of the public livestream that will be used by the Play button.', 'spinitron-player'); ?></p>
		<?php
}

/**
 * Outputs the Separate Time and DJ checkbox in plugin settings.
 * @param array $args Configuration for the field.
 * @return void
 */
function Spinitron_Player_Field_Separate_Time_DJ_cb($args) {
		$options = get_option('spinitron_player_options');
		$checked = isset($options[$args['label_for']]) ? $options[$args['label_for']] : 0;
		?>
		<input type="checkbox" id="<?php echo esc_attr($args['label_for']); ?>" name="spinitron_player_options[<?php echo esc_attr($args['label_for']); ?>]" value="1" <?php checked($checked, 1); ?> />
		<label for="<?php echo esc_attr($args['label_for']); ?>"><?php esc_html_e('Separate the show time and DJ name into <p class="show-time"> and <p class="show-dj"> elements. This is useful for better readability and to style the show schedule and the DJ’s name individually. If unselected, the show time and DJ name will be displayed together in a single <p class="show-time-dj"> element.', 'spinitron-player'); ?></label>
		<?php
}

/**
 * Outputs the Duplicate Show Image checkbox in plugin settings.
 * @param array $args Configuration for the field.
 * @return void
 */
function Spinitron_Player_Field_Duplicate_Show_Image_cb($args) {
		$options = get_option('spinitron_player_options');
		$checked = isset($options[$args['label_for']]) ? $options[$args['label_for']] : 0;
		?>
		<input type="checkbox" id="<?php echo esc_attr($args['label_for']); ?>" name="spinitron_player_options[<?php echo esc_attr($args['label_for']); ?>]" value="1" <?php checked($checked, 1); ?> />
		<label for="<?php echo esc_attr($args['label_for']); ?>"><?php esc_html_e('Display the show’s image twice: once above the show’s details and once within the details section. This is useful for layouts that prominently feature the show’s image within the content while also having the flexibility to use the additional image for styling purposes, such as placing it as a background.', 'spinitron-player'); ?></label>
		<?php
}
