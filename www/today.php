<?php
/**
 * Show Display for Spinitron Plugin.
 *
 * This file is responsible for fetching and displaying the current or upcoming show information
 * from the Spinitron API. It includes details such as the show title, image, airing time, and DJ name.
 * The information is dynamically updated based on the current time and the show schedule obtained from the API.
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

 require plugin_dir_path(__FILE__) . '../app/get-client.php';

 // Retrieve plugin options
 $options = get_option('spinitron_player_options');
 $separate_time_dj = isset($options['separate_time_dj']) ? $options['separate_time_dj'] : 0;
 $duplicate_show_image = isset($options['duplicate_show_image']) ? $options['duplicate_show_image'] : 0;

 // Ensure the fallback image is set, since it's required
 $fallback_image = '';
 if (isset($options['spinitron_player_field_image_fallback']) && !empty($options['spinitron_player_field_image_fallback'])) {
     $fallback_image = esc_url($options['spinitron_player_field_image_fallback']);
 }

 $shows = $client->search('shows', array('count' => 1));

 foreach ( $shows['items'] as $show ) :

     $show_title = esc_html($show['title']);
     $show_image = esc_url($show['image']);
     $show_start = ( new DateTime($show['start']) )->setTimezone(new DateTimeZone($show['timezone'] ?? 'America/Los_Angeles'));
     $show_end   = ( new DateTime($show['end']) )->setTimezone(new DateTimeZone($show['timezone'] ?? 'America/Los_Angeles'));
     $time_now   = ( new DateTime('now') )->setTimezone(new DateTimeZone($show['timezone'] ?? 'America/Los_Angeles'));

     $persona_url   = esc_url($show['_links']['personas'][0]['href']);
     $persona_parts = explode('/', $persona_url);
     $persona_id    = end($persona_parts);
     $persona_array = $client->fetch('personas', $persona_id);
     $show_dj       = esc_html($persona_array['name']);

     // Check if the image URL is valid
     $headers = @get_headers($show_image);
     if(!$headers || strpos($headers[0], '200') === false) {
         $show_image = $fallback_image;
     }

     if ($show_start <= $time_now && $time_now <= $show_end ) {
         $show_status = 'On air';
     } else {
         $show_status = 'Up next';
     }

     echo '<div class="spinitron-player">';

     // Check if the duplicate_show_image option is enabled
     if ($duplicate_show_image) {
         echo '<img class="show-image-outter" src="' . esc_html($show_image) . '" alt="' . esc_attr($show_title) . '" />';
     }

     echo '<div class="show-image">
         <img src="' . esc_html($show_image) . '" alt="' . esc_attr($show_title) . '" />
         </div>
         <div class="show-details">
         <p class="show-status">' . esc_html($show_status) . '</p>
         <p class="show-title">' . esc_html($show_title) . '</p>';

     // Check if the separate_time_dj option is enabled
     if ($separate_time_dj) {
         echo '<p class="show-time">' . esc_html($show_start->format('g:i A')) . ' - ' . esc_html($show_end->format('g:i A')) . '</p>
             <p class="show-dj">With ' . esc_html($show_dj) . '</p>';
     } else {
         echo '<p class="show-time-dj">' . esc_html($show_start->format('g:i A')) . ' - ' . esc_html($show_end->format('g:i A')) . ' with ' . esc_html($show_dj) . '</p>';
     }

     echo '</div></div>';

 endforeach;
