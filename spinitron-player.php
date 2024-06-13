<?php
/**
 * Plugin Name: Spinitron Player
 * Plugin URI: https://razorfrog.com/spinitron-player/
 * Description: A streaming player for radio stations using Spinitron, with live data integration.
 * Author: Razorfrog Web Design
 * Version: 1.0.2
 * Author URI: https://razorfrog.com/
 * License: GPLv2 or later
 * Text Domain: spinitron-player
 * Requires at least: 6.0
 * Requires PHP: 7.2
 *
 * PHP version 7.2
 *
 * @category Media
 * @package  SpinitronPlayer
 * @author   Razorfrog Web Design <hello@razorfrog.com>
 * @license  GPLv2 or later http://www.gnu.org/licenses/gpl-2.0.html
 * @link     https://razorfrog.com/
 */


if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Enqueues plugin styles.
 *
 * Adds the plugin's CSS file to the WordPress queue for stylesheets, ensuring it's loaded into the theme.
 * The stylesheet 'style.css' is located in the root of the plugin's directory and is enqueued
 * with a specific version number to manage caching and updates efficiently.
 *
 * @return void
 */
function Spinitron_Player_Enqueue_styles()
{
    $css_url     = plugin_dir_url(__FILE__) . 'style.css'; // Get the URL for the stylesheet.
    $css_version = '0.0.3'; // Set the version of the stylesheet.
    wp_enqueue_style('spinitron-player-styles', $css_url, array(), $css_version); // Enqueue the stylesheet.
}
add_action('wp_enqueue_scripts', 'Spinitron_Player_Enqueue_styles');


/**
 * Enqueue custom styles for the Spinitron Player settings page.
 *
 * This function adds inline CSS to the admin area to style the settings table.
 *
 * @param string $hook_suffix The current admin page.
 *
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
 * Initializes the Spinitron Player plugin settings by registering settings, sections, and fields.
 *
 * This function sets up the configuration for the Spinitron Player plugin in the WordPress admin dashboard.
 * It registers a new setting for the plugin, adds a settings section, and then adds fields within that section
 * for the API key and stream URL, each handled by its respective callback function for rendering the HTML input elements.
 *
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
            'label_for'                    => 'spinitron_player_field_api_key',
            'class'                        => 'spinitron_player_row',
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
            'label_for'                    => 'spinitron_player_field_image_fallback',
            'class'                        => 'spinitron_player_row',
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
            'label_for'                    => 'spinitron_player_field_stream_url',
            'class'                        => 'spinitron_player_row',
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
            'label_for'                    => 'spinitron_player_field_separate_time_dj',
            'class'                        => 'spinitron_player_row',
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
            'label_for'                    => 'spinitron_player_field_duplicate_show_image',
            'class'                        => 'spinitron_player_row',
            'spinitron_player_custom_data' => 'custom',
        )
    );
}
add_action('admin_init', 'Spinitron_Player_Settings_init');


/**
 * Outputs the developers section description for Spinitron Player settings.
 *
 * This function generates the HTML for the description paragraph of the developers section
 * in the Spinitron Player settings page. It uses the `$args` parameter to apply an ID to the paragraph element,
 * which helps in targeting the element for styling or scripting.
 *
 * @param array $args Configuration for the section, including 'id' for targeting the paragraph element.
 *
 * @return void
 */
function Spinitron_Player_Section_Developers_cb( $args )
{
    ?>
    <p id="<?php echo esc_attr($args['id']); ?>"><?php esc_html_e('Enter your API key and stream URL below:', 'spinitron-player'); ?></p>
    <?php
}


/**
 * Renders the API Key input field in plugin settings.
 *
 * Outputs an HTML input field of type text for configuring the API Key setting in the Spinitron Player plugin.
 * It utilizes the `$args` parameter to fetch and apply specific attributes like `id` and `data-custom` to the field,
 * ensuring it integrates properly with the WordPress settings API and follows the established plugin structure.
 *
 * @param array $args Configuration for the field, including 'label_for' and 'spinitron_player_custom_data', ensuring proper identification and data handling.
 *
 * @return void
 */
function Spinitron_Player_Field_Api_Key_cb( $args )
{
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
}


/**
 * Outputs the Image Fallback input field in plugin settings.
 *
 * Renders an HTML input field of type text for configuring the Image Fallback setting in the Spinitron Player plugin.
 * This function uses the `$args` parameter to set HTML attributes like `id` and `data-custom`.
 *
 * @param array $args Configuration for the field, including 'label_for' and 'spinitron_player_custom_data'.
 *
 * @return void
 */
function Spinitron_Player_Field_Image_Fallback_cb($args) {
    $options = get_option('spinitron_player_options');
    $fallback_image = isset($options['spinitron_player_field_image_fallback']) ? $options['spinitron_player_field_image_fallback'] : '';
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
 *
 * Outputs an HTML input field of type URL for configuring the Stream URL setting in the Spinitron Player plugin.
 * The function uses data passed in `$args` to set HTML attributes like `id` and `data-custom`.
 *
 * @param array $args Configuration for the field, including 'label_for' and 'spinitron_player_custom_data'.
 *
 * @return void
 */
function Spinitron_Player_Field_Stream_Url_cb( $args )
{
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
 *
 * Renders an HTML checkbox input for the 'Separate Time and DJ' setting in the Spinitron Player plugin.
 * This function uses the `$args` parameter to set HTML attributes like `id` and applies the `checked` state
 * based on the stored option value.
 *
 * @param array $args Configuration for the field, including 'label_for' and 'spinitron_player_custom_data'.
 *
 * @return void
 */
function Spinitron_Player_Field_Separate_Time_DJ_cb($args) {
    $options = get_option('spinitron_player_options');
    $checked = isset($options['separate_time_dj']) ? $options['separate_time_dj'] : 0;
    ?>
    <input type="checkbox" id="<?php echo esc_attr($args['label_for']); ?>" name="spinitron_player_options[separate_time_dj]" value="1" <?php checked($checked, 1); ?> />
    <label for="<?php echo esc_attr($args['label_for']); ?>"><?php esc_html_e('Separate the show time and DJ name into <p class="show-time"> and <p class="show-dj"> elements. This is useful for better readability and to style the show schedule and the DJ’s name individually. If unselected, the show time and DJ name will be displayed together in a single <p class="show-time-dj"> element.', 'spinitron-player'); ?></label>
    <?php
}


/**
 * Outputs the Duplicate Show Image checkbox in plugin settings.
 *
 * Renders an HTML checkbox input for the 'Duplicate Show Image' setting in the Spinitron Player plugin.
 * This function uses the `$args` parameter to set HTML attributes like `id` and applies the `checked` state
 * based on the stored option value.
 *
 * @param array $args Configuration for the field, including 'label_for' and 'spinitron_player_custom_data'.
 *
 * @return void
 */
function Spinitron_Player_Field_Duplicate_Show_Image_cb($args) {
    $options = get_option('spinitron_player_options');
    $checked = isset($options['duplicate_show_image']) ? $options['duplicate_show_image'] : 0;
    ?>
    <input type="checkbox" id="<?php echo esc_attr($args['label_for']); ?>" name="spinitron_player_options[duplicate_show_image]" value="1" <?php checked($checked, 1); ?> />
    <label for="<?php echo esc_attr($args['label_for']); ?>"><?php esc_html_e('Display the show’s image twice: once above the show’s details and once within the details section. This is useful for layouts that prominently feature the show’s image within the content while also having the flexibility to use the additional image for styling purposes, such as placing it as a background.', 'spinitron-player');
 ?></label>
    <?php
}


/**
 * Registers the Spinitron Player settings page in the WordPress admin area.
 *
 * This function adds the Spinitron Player settings page to the WordPress admin menu.
 * It defines the page title, menu title, required capability to access the page,
 * the menu slug, and the function that will render the settings page HTML.
 *
 * @return void
 */
function Spinitron_Player_Options_page()
{
    add_options_page(
        __('Spinitron Player', 'spinitron-player'), // Page title.
        __('Spinitron Player', 'spinitron-player'), // Menu title.
        'manage_options',   // Capability required.
        'spinitron_player', // Menu slug.
        'Spinitron_Player_Options_Page_html' // Callback function to render the options page.
    );
}
add_action('admin_menu', 'Spinitron_Player_Options_page');


/**
 * Renders the HTML for the Spinitron Player options page in the admin area.
 *
 * This function checks if the current user has the 'manage_options' capability. If so, it displays
 * the settings errors and the form for updating the Spinitron Player settings.
 * It's hooked into the WordPress admin to add a settings page for the plugin.
 *
 * @return void
 */
function Spinitron_Player_Options_Page_html()
{
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
 * Implements the shortcode for Spinitron Player.
 *
 * Captures and returns the output from 'www/today.php', which is expected to be part of the plugin's functionality.
 * This output is then used as the content for the 'spinitron_player' shortcode, allowing it to be placed within WordPress posts or pages.
 * Usage: [spinitron_player]
 *
 * @return string The captured HTML content from the included file.
 */
function Spinitron_Player_shortcode()
{
    ob_start();
    include plugin_dir_path(__FILE__) . 'www/today.php';
    return ob_get_clean();
}
add_shortcode('spinitron_player', 'Spinitron_Player_shortcode');


/**
 * Renders a play button for a radio stream.
 *
 * This function outputs the HTML for a play/pause button that controls a radio stream.
 * It checks the plugin options for a stream URL and adds an audio element and button to the page if the URL is provided.
 * The function ensures the audio element is added only once and controls it with JavaScript.
 * Usage: [spinitron_play_button]
 *
 * @return string The HTML content for the play button and associated script.
 */
function Spinitron_Play_Button_shortcode()
{
    static $audio_added = false;
    ob_start();

    // Retrieve the entire options array for your plugin.
    $spinitron_player_options = get_option('spinitron_player_options', '');

    // Check if the option is retrieved successfully and is not empty.
    if (! empty($spinitron_player_options) && is_array($spinitron_player_options) ) {

        // Access the specific setting from the array.
        $stream_url = isset($spinitron_player_options['spinitron_player_field_stream_url']) ? $spinitron_player_options['spinitron_player_field_stream_url'] : '';

        // Proceeds only if there is a stream URL entered.
        if (! empty($stream_url) ) {

            if (! $audio_added ) {
                ?>
                <!-- Only add the audio element once -->
                <audio id="spinitron-stream" src="<?php echo esc_url($stream_url); ?>" type="audio/mpeg" style="display:none;"></audio>
                <script>
                document.addEventListener('DOMContentLoaded', function () {
                    var radioStream = document.getElementById('spinitron-stream');

                    // Function to update all play button texts and aria-labels.
                    function updatePlayButtons(isPlaying) {
                        var playButtons = document.querySelectorAll('.spinitron-stream-button-text');
                        playButtons.forEach(function(buttonText) {
                            buttonText.textContent = isPlaying ? '<?php echo esc_js(__('Pause', 'spinitron-player')); ?>' : '<?php echo esc_js(__('Play', 'spinitron-player')); ?>';
                            buttonText.closest('button').setAttribute('aria-label', isPlaying ? '<?php echo esc_attr(__('Pause', 'spinitron-player')); ?>' : '<?php echo esc_attr(__('Play', 'spinitron-player')); ?>');
                        });
                    }

                    // Event listener for all play button clicks.
                    document.body.addEventListener('click', function (e) {
                        if (e.target.classList.contains('spinitron-stream-button') || e.target.closest('.spinitron-stream-button')) {
                            if (radioStream.paused) {
                                radioStream.play(); // Play the stream.
                                updatePlayButtons(true); // Update all buttons to 'Pause'.
                            } else {
                                radioStream.pause(); // Pause the stream.
                                updatePlayButtons(false); // Update all buttons to 'Play'.
                            }
                        }
                    });
                });
                </script>
                <?php
                $audio_added = true;
            }
            ?>
            <!-- Radio Stream Play/Pause Button for Accessibility, works with multiple instances -->
            <button id="spinitron-stream-button" class="spinitron-stream-button" aria-controls="radioStream" aria-label="<?php echo esc_attr(__('Play', 'spinitron-player')); ?>" role="button">
                <span class="spinitron-stream-button-text"><?php echo esc_html(__('Play', 'spinitron-player')); ?></span>
            </button>
            <?php
        }
    }

    return ob_get_clean(); // Return the buffered content.
}
add_shortcode('spinitron_play_button', 'Spinitron_Play_Button_shortcode');

?>
