<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Handles AJAX request to fetch the current Spinitron show for the 'TODAY' layout.
 * @return void
 */
function fetch_spinitron_show_today_callback() {
		require plugin_dir_path(__FILE__) . '../app/spinitron-get-client.php';

		global $client;

		// Retrieve plugin options
		$options = get_option('spinitron_player_options');
		$separate_time_dj = isset($options['spinitron_player_field_separate_time_dj']) ? $options['spinitron_player_field_separate_time_dj'] : 0;
		$duplicate_show_image = isset($options['spinitron_player_field_duplicate_show_image']) ? $options['spinitron_player_field_duplicate_show_image'] : 0;

		// Ensure the fallback image is set, since it's required
		$fallback_image = '';
		if (isset($options['spinitron_player_field_image_fallback']) && !empty($options['spinitron_player_field_image_fallback'])) {
				$fallback_image = esc_url($options['spinitron_player_field_image_fallback']);
		}

		// Try to get the transient
		$shows = get_transient('spinitron_show_today');

		if ($shows === false) {
				// If the transient doesn't exist, fetch from API
				$shows = $client->search('shows', array('count' => 1, 'cache_bust' => time()));

				if (empty($shows['items'])) {
						echo '<p>No shows found. Please check the Spinitron API for available shows.</p>';
						wp_die();
				}

				// Calculate the number of seconds until the next multiple of 5 minutes
				$current_time = time();
				$next_cache_time = ceil($current_time / 300) * 300; // 300 seconds = 5 minutes
				$expiration = $next_cache_time - $current_time;

				// Set the transient
				set_transient('spinitron_show_today', $shows, $expiration);
		}

		ob_start();

		foreach ($shows['items'] as $show) {
				$show_title = esc_html($show['title']);
				$show_image = esc_url($show['image']);
				$show_start = (new DateTime($show['start']))->setTimezone(new DateTimeZone($show['timezone'] ?? 'America/Los_Angeles'));
				$show_end = (new DateTime($show['end']))->setTimezone(new DateTimeZone($show['timezone'] ?? 'America/Los_Angeles'));
				$time_now = (new DateTime('now'))->setTimezone(new DateTimeZone($show['timezone'] ?? 'America/Los_Angeles'));

				$persona_url = esc_url($show['_links']['personas'][0]['href']);
				$persona_parts = explode('/', $persona_url);
				$persona_id = end($persona_parts);
				$persona_array = $client->fetch('personas', $persona_id);
				$show_dj = esc_html($persona_array['name']);

				// Check if the image URL is valid
				if (!empty($show_image)) {
						$headers = @get_headers($show_image);
						if (!$headers || strpos($headers[0], '200') === false) {
								$show_image = $fallback_image;
						}
				} else {
						error_log('Show image URL is empty. Using fallback image.');
						$show_image = $fallback_image;
				}

				$show_status = ($show_start <= $time_now && $time_now <= $show_end) ? 'On air' : 'Up next';

				echo '<div class="spinitron-player">';
				if ($duplicate_show_image) {
						echo '<img class="show-image-outter" src="' . esc_html($show_image) . '" alt="' . esc_attr($show_title) . '" />';
				}

				echo '<div class="show-image">
								<img src="' . esc_html($show_image) . '" alt="' . esc_attr($show_title) . '" />
							</div>
							<div class="show-details">
								<p class="show-status">' . esc_html($show_status) . '</p>
								<p class="show-title">' . esc_html($show_title) . '</p>';

				if ($separate_time_dj) {
						echo '<p class="show-time">' . esc_html($show_start->format('g:i A')) . ' - ' . esc_html($show_end->format('g:i A')) . '</p>
									<p class="show-dj">With ' . esc_html($show_dj) . '</p>';
				} else {
						echo '<p class="show-time-dj">' . esc_html($show_start->format('g:i A')) . ' - ' . esc_html($show_end->format('g:i A')) . ' with ' . esc_html($show_dj) . '</p>';
				}

				echo '</div></div>';
		}

		$output = ob_get_clean();

		echo $output;

		wp_die(); // This is required to terminate immediately and return a proper response.
}
add_action('wp_ajax_fetch_spinitron_show_today', 'fetch_spinitron_show_today_callback');
add_action('wp_ajax_nopriv_fetch_spinitron_show_today', 'fetch_spinitron_show_today_callback');
