<?php

/**
 * Plugin Name:       Spinitron Player
 * Plugin URI:        https://razorfrog.com/spinitron-player/
 * Description:       A streaming player for radio stations using Spinitron.
 * Version:           1.1.2
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Razorfrog Web Design
 * Author URI:        https://razorfrog.com/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       spinitron-player
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

define('SPINITRON_PLAYER_VERSION', '1.1.2');

/**
 * Clears the cached "today" show data whenever the plugin has been updated.
 *
 * Versions prior to 1.1.0 could cache the show transient with no
 * expiration (a divide-aligned expiration calculation could land on
 * exactly 0 seconds, which WordPress treats as "never expire"), freezing
 * the display on whatever show was airing at that instant. Existing sites
 * need this one-time cleanup on top of the code fix, since updating the
 * plugin doesn't by itself clear a transient already stuck in the database.
 *
 * @return void
 */
function Spinitron_Player_maybe_upgrade() {
    if ( get_option( 'spinitron_player_version' ) !== SPINITRON_PLAYER_VERSION ) {
        delete_transient( 'spinitron_show_today' );
        update_option( 'spinitron_player_version', SPINITRON_PLAYER_VERSION );
    }
}
add_action( 'plugins_loaded', 'Spinitron_Player_maybe_upgrade' );

/**
 * Enqueues plugin styles and scripts.
 *
 * @return void
 */
function Spinitron_Player_Enqueue_assets() {
    // Enqueue the plugin's CSS file
    $css_url = plugin_dir_url(__FILE__) . 'style.css';
    $css_version = SPINITRON_PLAYER_VERSION;
    wp_enqueue_style('spinitron-player-styles', $css_url, array(), $css_version);

    // Enqueue the JavaScript file for fetching show data
    $js_url = plugin_dir_url(__FILE__) . 'js/spinitron-fetch-today.js';
    $js_version = SPINITRON_PLAYER_VERSION;
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
     ob_start();

     // Get the livestream URL from settings. There is no default stream: with no URL saved, show nothing to visitors.
     $opts = get_option('spinitron_player_options', array());
     if (!is_array($opts)) { $opts = array(); }
     $stream_url = !empty($opts['spinitron_player_field_stream_url'])
         ? $opts['spinitron_player_field_stream_url']
         : '';
     if ('' === $stream_url) {
         ob_end_clean();
         if (current_user_can('manage_options')) {
             return '<p>' . esc_html__('Spinitron Player: add a Livestream URL in the plugin settings to show the Play button.', 'spinitron-player') . '</p>';
         }
         return '';
     }

     // Hidden shared audio id; button markup remains unchanged
     $audio_id  = 'spinitron-stream-shared-hidden';
     $btn_class = 'spinitron-stream-button';
     ?>
     <!-- Button markup kept IDENTICAL to your original -->
     <button id="spinitron-stream-button" class="spinitron-stream-button" aria-controls="<?php echo esc_attr($audio_id); ?>" aria-label="<?php echo esc_attr(__('Play', 'spinitron-player')); ?>" role="button">
         <span class="spinitron-stream-button-text"><?php echo esc_html(__('Play', 'spinitron-player')); ?></span>
     </button>

     <script>
     (function(){
       var AUDIO_ID   = '<?php echo esc_js($audio_id); ?>';
       var STREAM_URL = '<?php echo esc_js( esc_url_raw( $stream_url ) ); ?>';
       var BTN_CLASS  = '<?php echo esc_js($btn_class); ?>';

       // Singleton: one hidden audio, many buttons
       window.SpinitronSharedAudio = window.SpinitronSharedAudio || {
         initOnce: false,
         audio: null,
         setState: function(playing){
           // Keep all buttons in sync (text + aria)
           document.querySelectorAll('.' + BTN_CLASS).forEach(function(btn){
             var span = btn.querySelector('.spinitron-stream-button-text');
             if (span) span.textContent = playing ? '<?php echo esc_js(__('Pause', 'spinitron-player')); ?>' : '<?php echo esc_js(__('Play', 'spinitron-player')); ?>';
             btn.setAttribute('aria-label', playing ? '<?php echo esc_js(__('Pause', 'spinitron-player')); ?>' : '<?php echo esc_js(__('Play', 'spinitron-player')); ?>');
             btn.setAttribute('aria-pressed', playing ? 'true' : 'false');
           });
         }
       };
       var SA = window.SpinitronSharedAudio;

       if (!SA.initOnce) {
         SA.initOnce = true;

         // Create one hidden audio element
         var audio = document.getElementById(AUDIO_ID);
         if (!audio) {
           audio = document.createElement('audio');
           audio.id = AUDIO_ID;
           audio.src = STREAM_URL;
           audio.preload = 'none';
           audio.crossOrigin = 'anonymous';
           audio.style.display = 'none';
           document.body.appendChild(audio);
         }
         SA.audio = audio;

         // Native events keep all buttons synced
         audio.addEventListener('play',  function(){ SA.setState(true);  });
         audio.addEventListener('pause', function(){ SA.setState(false); });
         audio.addEventListener('ended', function(){ SA.setState(false); });

         // Global delegation: any .spinitron-stream-button toggles the shared audio
         document.addEventListener('click', async function(e){
           var btn = e.target.closest && e.target.closest('.' + BTN_CLASS);
           if (!btn) return;
           e.preventDefault();
           try {
             if (SA.audio.paused) {
               if (SA.audio.src !== STREAM_URL) SA.audio.src = STREAM_URL; // defensive
               await SA.audio.play();
               SA.setState(true);
             } else {
               SA.audio.pause();
               SA.setState(false);
             }
           } catch (err) {
             console.error('Playback error:', err);
             SA.setState(false);
           }
         });

         // Initialize UI for any buttons already on the page
         SA.setState(!SA.audio.paused);
       } else {
         // If audio already exists, sync this instance's button immediately
         SA.setState(SA.audio && !SA.audio.paused);
       }
     })();
     </script>
     <?php

     return ob_get_clean();
 }
 add_shortcode('spinitron_play_button', 'Spinitron_Play_Button_shortcode');

 ?>
