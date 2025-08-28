<?php
/**
 * Plugin Name: Spinitron Player
 * Plugin URI: https://razorfrog.com/spinitron-player/
 * Description: A streaming player for radio stations using Spinitron, with live data integration.
 * Author: Razorfrog Web Design
 * Version: 1.0.8
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
     ob_start();

     // Get URL from settings safely, with a fallback for testing
     $opts = get_option('spinitron_player_options', array());
     if (!is_array($opts)) { $opts = array(); }
     $stream_url = !empty($opts['spinitron_player_field_stream_url'])
         ? $opts['spinitron_player_field_stream_url']
         : 'https://kspc.radioca.st/stream?type=http&nocache=41177';

     // Hidden shared audio id; button markup remains unchanged
     $audio_id  = 'spinitron-stream-shared-hidden';
     $btn_class = 'spinitron-stream-button';
     ?>
     <!-- Button markup kept IDENTICAL to your original -->
     <button id="spinitron-stream-button" class="spinitron-stream-button" aria-controls="radioStream" aria-label="<?php echo esc_attr(__('Play', 'spinitron-player')); ?>" role="button">
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
