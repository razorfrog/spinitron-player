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

$shows = $client->search('shows', array( 'count' => 1 ));

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

    if ($show_start <= $time_now && $time_now <= $show_end ) {
        $show_status = 'On air';
    } else {
        $show_status = 'Up next';
    }

    echo(
    '<div class="spinitron-player">
            <div class="show-image">
                <img src="' . esc_html($show_image) . '" alt="' . esc_attr($show_title) . '" />
            </div>
            <div class="show-details">
                <p class="show-status">' . esc_html($show_status) . '</p>
                <p class="show-title">' . esc_html($show_title) . '</p>
                <p class="show-time">' . esc_html($show_start->format('g:i A')) . ' - ' . esc_html($show_end->format('g:i A')) . ' with ' . esc_html($show_dj) . '</p>
            </div>
        </div>'
    );

endforeach;
