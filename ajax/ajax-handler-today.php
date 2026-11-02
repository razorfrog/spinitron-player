<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Handles AJAX request to fetch the current Spinitron show for the 'TODAY' layout.
 * @return void
 */
function spinitron_player_fetch_spinitron_show_today_callback() {
	// Prevent browser and server-side caching
	header( 'Expires: 0' );
	header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
	header( 'Pragma: no-cache' );
	header( 'Content-Type: text/html; charset=utf-8' );

	// Clear any output buffers that might interfere
	if ( ob_get_length() ) {
		ob_clean();
	}

	// Retrieve plugin options
	$options              = get_option( 'spinitron_player_options' );
	$separate_time_dj     = isset( $options['spinitron_player_field_separate_time_dj'] ) ? $options['spinitron_player_field_separate_time_dj'] : 0;
	$duplicate_show_image = isset( $options['spinitron_player_field_duplicate_show_image'] ) ? $options['spinitron_player_field_duplicate_show_image'] : 0;

	// Ensure the fallback image is set, since it's required
	$fallback_image = '';
	if ( isset( $options['spinitron_player_field_image_fallback'] ) && ! empty( $options['spinitron_player_field_image_fallback'] ) ) {
		$fallback_image = esc_url_raw( $options['spinitron_player_field_image_fallback'] );
	}

	// Try to get the transient
	$shows = get_transient( 'spinitron_show_today' );

	if ( false === $shows ) {
		// Only set up the API client when the cache is empty, so cached polls
		// don't call Spinitron at all.
		require plugin_dir_path( __FILE__ ) . '../app/spinitron-get-client.php';

		global $spinitron_player_client;

		try {
			// If the transient doesn't exist, fetch from API
			$shows = $spinitron_player_client->search(
				'shows',
				array(
					'count'      => 1,
					'cache_bust' => time(),
				)
			);

			if ( isset( $shows['error'] ) ) {
				echo '<p>Invalid API key. Please make sure your Spinitron API key is valid.</p>';
				wp_die();
			}

			if ( empty( $shows['items'] ) ) {
				echo '<p>No shows found. Please check the Spinitron API for available shows.</p>';
				wp_die();
			}

			// Resolve the DJ name and validate the show image once per fetch
			// (instead of inside the render loop below) so both are cached
			// alongside the show data rather than re-requested from Spinitron,
			// and re-validated over HTTP, on every single AJAX poll.
			foreach ( $shows['items'] as $key => $show ) {
				$show_dj = '';
				if ( ! empty( $show['_links']['personas'][0]['href'] ) ) {
					try {
						$persona_url   = esc_url( $show['_links']['personas'][0]['href'] );
						$persona_parts = explode( '/', $persona_url );
						$persona_id    = end( $persona_parts );
						$persona_array = $spinitron_player_client->fetch( 'personas', $persona_id );
						$show_dj       = isset( $persona_array['name'] ) ? $persona_array['name'] : '';
					} catch ( \Exception $e ) {
						$show_dj = '';
					}
				}
				$shows['items'][ $key ]['dj_name'] = $show_dj;

				// Check if the image URL is valid
				$show_image = ! empty( $show['image'] ) ? esc_url_raw( $show['image'] ) : '';
				if ( ! empty( $show_image ) ) {
					$image_response = wp_safe_remote_head(
						$show_image,
						array(
							'timeout'     => 5,
							'redirection' => 3,
						)
					);
					if ( is_wp_error( $image_response ) || 200 !== (int) wp_remote_retrieve_response_code( $image_response ) ) {
						$show_image = $fallback_image;
					}
				} else {
					$show_image = $fallback_image;
				}
				$shows['items'][ $key ]['image'] = $show_image;
			}

			// Cache for 5 minutes, aligned to the wall-clock so all visitors
			// refresh together. Guard against an expiration of exactly 0:
			// WordPress treats set_transient(..., 0) as "never expire", which
			// previously froze the display on whatever show was fetched at
			// that instant (e.g. permanently stuck on an overnight show)
			// instead of refreshing every 5 minutes as intended.
			$current_time    = time();
			$next_cache_time = ceil( $current_time / 300 ) * 300; // 300 seconds = 5 minutes
			$expiration      = $next_cache_time - $current_time;
			if ( $expiration < 1 ) {
				$expiration = 300;
			}

			// Set the transient
			set_transient( 'spinitron_show_today', $shows, $expiration );
		} catch ( \Exception $e ) {
			echo '<p>Unable to reach the Spinitron API right now. Please try again shortly.</p>';
			wp_die();
		}
	}

	ob_start();

	foreach ( $shows['items'] as $show ) {
		$timezone    = isset( $show['timezone'] ) ? $show['timezone'] : 'America/Los_Angeles';
		$tz          = new DateTimeZone( $timezone );
		// Raw values here; each is escaped once where it's printed below.
		$show_title  = isset( $show['title'] ) ? $show['title'] : '';
		// Image URLs are stored raw (esc_url_raw) and escaped once with esc_url() when printed.
		$show_image  = ! empty( $show['image'] ) ? $show['image'] : $fallback_image;
		$show_start  = ( new DateTime( $show['start'] ) )->setTimezone( $tz );
		$show_end    = ( new DateTime( $show['end'] ) )->setTimezone( $tz );
		$time_now    = ( new DateTime( 'now' ) )->setTimezone( $tz );
		$show_dj     = isset( $show['dj_name'] ) ? $show['dj_name'] : '';

		$show_status = ( $show_start <= $time_now && $time_now <= $show_end ) ? 'On air' : 'Up next';

		echo '<div class="spinitron-player">';

		if ( $duplicate_show_image ) {
			echo '<img class="show-image-outter" src="' . esc_url( $show_image ) . '" alt="' . esc_attr( $show_title ) . '" />';
		}

		echo '<div class="show-image">
					<img src="' . esc_url( $show_image ) . '" alt="' . esc_attr( $show_title ) . '" />
				</div>
				<div class="show-details">
					<p class="show-status">' . esc_html( $show_status ) . '</p>
					<p class="show-title">' . esc_html( $show_title ) . '</p>';

		if ( $separate_time_dj ) {
			echo '<p class="show-time">' . esc_html( $show_start->format( 'g:i A' ) ) . ' - ' . esc_html( $show_end->format( 'g:i A' ) ) . '</p>
						<p class="show-dj">With ' . esc_html( $show_dj ) . '</p>';
		} else {
			echo '<p class="show-time-dj">' . esc_html( $show_start->format( 'g:i A' ) ) . ' - ' . esc_html( $show_end->format( 'g:i A' ) ) . ' with ' . esc_html( $show_dj ) . '</p>';
		}

		echo '</div></div>';
	}

	$output = ob_get_clean();

	// $output is composed of escaped HTML fragments above.
	echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

	wp_die(); // This is required to terminate immediately and return a proper response.
}

add_action( 'wp_ajax_fetch_spinitron_show_today', 'spinitron_player_fetch_spinitron_show_today_callback' );
add_action( 'wp_ajax_nopriv_fetch_spinitron_show_today', 'spinitron_player_fetch_spinitron_show_today_callback' );
