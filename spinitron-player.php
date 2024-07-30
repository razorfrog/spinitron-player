<?php
/**
 * Plugin Name: Spinitron Player
 * Plugin URI: https://razorfrog.com/spinitron-player/
 * Description: A streaming player for radio stations using Spinitron, with live data integration.
 * Author: Razorfrog Web Design
 * Version: 1.0.6
 * Author URI: https://razorfrog.com/
 * License: GPLv2 or later
 * Text Domain: spinitron-player
 * Requires at least: 6.0
 * Requires PHP: 7.2
 *
 * PHP version 7.2
 *
 * @category Media
 * @package SpinitronPlayer
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Enqueues plugin styles and scripts.
 *
 * @return void
 */
function Spinitron_Player_Enqueue_assets() {
    // Enqueue the plugin's CSS file
    $css_url = plugin_dir_url(__FILE__) . 'style.css';
    $css_version = '1.0.5';
    wp_enqueue_style('spinitron-player-styles', $css_url, array(), $css_version);

    // Enqueue the JavaScript file for fetching show data
    $js_url = plugin_dir_url(__FILE__) . 'js/spinitron-fetch-today.js';
    $js_version = '1.0.5';
    wp_enqueue_script('spinitron-player-fetch-today', $js_url, array('jquery'), $js_version, true);
    wp_localize_script('spinitron-player-fetch-today', 'spinitron_params', array(
        'ajax_url' => admin_url('admin-ajax.php'),
    ));
}
add_action('wp_enqueue_scripts', 'Spinitron_Player_Enqueue_assets');

// Include settings and admin page setup
include plugin_dir_path(__FILE__) . 'app/plugin-settings.php';

// Include the AJAX handler for fetching show data
include plugin_dir_path(__FILE__) . 'ajax/ajax-handler-today.php';

/**
 * Implements the shortcode for Spinitron Player.
 *
 * Captures and returns the output for the Spinitron Player shortcode,
 * allowing it to be placed within WordPress posts or pages.
 * Usage: [spinitron_player]
 *
 * @return string The HTML content for the Spinitron show container.
 */
function Spinitron_Player_shortcode() {
    global $spinitron_instance_count;
    if (!isset($spinitron_instance_count)) {
        $spinitron_instance_count = 0;
    }
    $spinitron_instance_count++;

    ob_start();
    include plugin_dir_path(__FILE__) . 'ui/today.php';
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
function Spinitron_Play_Button_shortcode() {
    static $audio_added = false;
    ob_start();

    // Retrieve the entire options array for your plugin.
    $spinitron_player_options = get_option('spinitron_player_options', '');

    // Check if the option is retrieved successfully and is not empty.
    if (!empty($spinitron_player_options) && is_array($spinitron_player_options)) {

        // Access the specific setting from the array.
        $stream_url = isset($spinitron_player_options['spinitron_player_field_stream_url']) ? $spinitron_player_options['spinitron_player_field_stream_url'] : '';

        // Proceeds only if there is a stream URL entered.
        if (!empty($stream_url)) {

            if (!$audio_added) {
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
