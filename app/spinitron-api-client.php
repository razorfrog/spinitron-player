<?php
/**
 * Spinitron API Client Class File
 *
 * This file defines the SpinitronApiClient class, which facilitates communication with the Spinitron API.
 * It provides methods to search for and fetch data from various endpoints such as spins, shows, and playlists.
 *
 * PHP version 7.2
 *
 * @category Media
 * @package SpinitronPlayer
 * @license GPLv2 or later http://www.gnu.org/licenses/gpl-2.0.html
 * @link https://razorfrog.com/
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Spinitron API Client.
 *
 * This class is used for communicating with the Spinitron API. It provides methods to fetch show and persona
 * details, handling the request caching to optimize performance by reducing the number of API calls.
 */
if (!class_exists('SpinitronApiClient')) {

    class SpinitronApiClient
    {
        protected $api_base_url = 'https://spinitron.com/api';
        private $_api_key;

        /**
         * Constructor for the SpinitronApiClient class.
         * @param string $_api_key The API key for Spinitron.
         */
        public function __construct($_api_key)
        {
            $this->api_key = $_api_key;
        }

        /**
         * Request resources from an endpoint using search parameters.
         * @param string $endpoint e.g., 'spins', 'shows'... This is the specific API endpoint to query.
         * @param array $params e.g., ['playlist_id' => 1234, 'page' => 2]. These are the search parameters for the query.
         * @throws \Exception If the request to the API fails or if the response cannot be decoded.
         * @return array Response with an array of resources of the endpoint's type plus metadata.
         */
        public function search($endpoint, $params)
        {
            $url = '/' . $endpoint;
            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }

            return json_decode($this->queryApi($url), true);
        }

        /**
         * Request a resource from an endpoint using its ID.
         * @param string $endpoint e.g. 'shows', 'personas', ...
         * @param int $id e.g. 2344.
         * @throws \Exception If the request fails.
         * @return array Response with one resource of the endpoint's type plus metadata.
         */
        public function fetch($endpoint, $id)
        {
            $url = '/' . $endpoint . '/' . $id;

            return json_decode($this->queryApi($url), true);
        }

        /**
         * Queries the API and returns the response JSON document.
         * @param string $url The API endpoint URL.
         * @throws \Exception When the request fails or the response is invalid.
         * @return string JSON document.
         */
        protected function queryApi($url)
        {
            $args = array(
                'headers' => array(
                    'User-Agent' => 'Mozilla/5.0 Spinitron v2 API demo client',
                    'Authorization' => 'Bearer ' . esc_attr($this->api_key),
                ),
                'redirection' => 5,
                'timeout' => 60,
                'sslverify' => true,
            );
            $full_url = esc_url_raw($this->api_base_url . $url);

            $response = wp_remote_get($full_url, $args);

            if (is_wp_error($response)) {
                throw new \Exception('Error requesting ' . esc_url($full_url) . ': ' . esc_html($response->get_error_message()));
            }

            $body = wp_remote_retrieve_body($response);
            if (false === $body) {
                throw new \Exception('Error retrieving body from ' . esc_url($full_url));
            }

            return $body;
        }
    }
}
