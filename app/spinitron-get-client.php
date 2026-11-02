<?php
/**
 * Initializes the Spinitron API client and retrieves plugin options.
 *
 * This file is responsible for fetching the API key from the plugin's options, initializing the
 * Spinitron API client with it, and ensuring that the client is available throughout the plugin.
 * It checks for the presence of an API key, attempts to initialize the SpinitronApiClient with it,
 * and handles cases where the API key is missing or invalid. The file makes use of the WordPress
 * options API to retrieve plugin settings and provides a basic error message if the initialization fails.
 *
 * PHP version 7.2
 *
 * @category Media
 * @package SpinitronPlayer
 * @license GPLv2 or later http://www.gnu.org/licenses/gpl-2.0.html
 * @link https://razorfrog.com/
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

$spinitron_player_options = get_option( 'spinitron_player_options', '' );

if ( ! empty( $spinitron_player_options ) && is_array( $spinitron_player_options ) ) {

    $spinitron_api_key = isset( $spinitron_player_options['spinitron_player_field_api_key'] )
        ? $spinitron_player_options['spinitron_player_field_api_key']
        : '';

    if ( empty( $spinitron_api_key ) ) {
        echo '<p>API key is missing. Please provide a valid API key in the settings.</p>';
        exit;
    }

    if ( ! class_exists( 'SpinitronApiClient' ) ) {
        include plugin_dir_path( __FILE__ ) . 'spinitron-api-client.php';
    }

    // Global client used throughout the plugin.
    global $spinitron_player_client;

    if ( ! isset( $spinitron_player_client ) ) {
        $spinitron_player_client = new SpinitronApiClient( $spinitron_api_key );
        // The API key is checked by the first real request (see ajax-handler-today.php),
        // so there's no separate test request here.
    }
} else {
    echo '<p>Something went wrong. Please make sure your Spinitron API key is valid.</p>';
    exit;
}
