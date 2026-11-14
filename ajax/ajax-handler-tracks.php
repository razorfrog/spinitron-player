<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Most tracks one [spinitron_recent_tracks] list can show. The plugin always
 * fetches and caches this many, then trims to what each list asks for, so
 * lists with different counts share one API request.
 */
define( 'SPINITRON_PLAYER_TRACKS_MAX', 20 );

/**
 * How long, in seconds, the recent tracks are cached. Spinitron is contacted
 * at most once per this period, however many visitors are polling.
 */
define( 'SPINITRON_PLAYER_TRACKS_TTL', 30 );

/**
 * Returns the station's most recent spins, newest first, from cache or Spinitron.
 *
 * @return array|WP_Error List of spins (possibly empty), or WP_Error when Spinitron can't be reached.
 */
function spinitron_player_get_recent_tracks() {
	$cached = get_transient( 'spinitron_recent_tracks' );
	if ( is_array( $cached ) ) {
		if ( isset( $cached['error'] ) ) {
			return new WP_Error( 'spinitron_player_tracks', $cached['error'] );
		}
		return $cached;
	}

	// Only set up the API client when the cache is empty.
	require plugin_dir_path( __FILE__ ) . '../app/spinitron-get-client.php';

	global $spinitron_player_client;

	$error = '';
	$spins = array();

	try {
		$response = $spinitron_player_client->search( 'spins', array( 'count' => SPINITRON_PLAYER_TRACKS_MAX ) );

		if ( ! is_array( $response ) || ! isset( $response['items'] ) || ! is_array( $response['items'] ) ) {
			// Spinitron answers errors with a JSON body carrying the HTTP status.
			// Only 401/403 mean the key is wrong; anything else is an outage or rate limit.
			$status = ( is_array( $response ) && isset( $response['status'] ) ) ? (int) $response['status'] : 0;
			if ( 401 === $status || 403 === $status ) {
				$error = __( 'Invalid API key. Please make sure your Spinitron API key is valid.', 'spinitron-player' );
			} else {
				$error = __( 'Unable to reach the Spinitron API right now. Please try again shortly.', 'spinitron-player' );
			}
		} else {
			// Keep only the fields the list prints, so the cached value stays small.
			foreach ( $response['items'] as $spin ) {
				$spins[] = array(
					'start'    => isset( $spin['start'] ) ? (string) $spin['start'] : '',
					'timezone' => isset( $spin['timezone'] ) ? (string) $spin['timezone'] : '',
					'artist'   => isset( $spin['artist'] ) ? (string) $spin['artist'] : '',
					'song'     => isset( $spin['song'] ) ? (string) $spin['song'] : '',
					'release'  => isset( $spin['release'] ) ? (string) $spin['release'] : '',
					// On an HTTPS site an http:// cover would be blocked as mixed content.
					'image'    => ! empty( $spin['image'] ) ? esc_url_raw( is_ssl() ? set_url_scheme( $spin['image'], 'https' ) : $spin['image'] ) : '',
				);
			}
		}
	} catch ( \Exception $e ) {
		$error = __( 'Unable to reach the Spinitron API right now. Please try again shortly.', 'spinitron-player' );
	}

	if ( '' !== $error ) {
		// Cache the failure briefly too, so an outage doesn't send every poll to Spinitron.
		set_transient( 'spinitron_recent_tracks', array( 'error' => $error ), SPINITRON_PLAYER_TRACKS_TTL );
		return new WP_Error( 'spinitron_player_tracks', $error );
	}

	set_transient( 'spinitron_recent_tracks', $spins, SPINITRON_PLAYER_TRACKS_TTL );

	return $spins;
}

/**
 * Handles the AJAX request for the [spinitron_recent_tracks] list.
 *
 * Reads only public data and changes nothing, so it takes no nonce: pages
 * holding the list are often served from a page cache, where a nonce would
 * go stale.
 *
 * @return void
 */
function spinitron_player_fetch_spinitron_recent_tracks_callback() {
	// Prevent browser and server-side caching
	header( 'Expires: 0' );
	header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
	header( 'Pragma: no-cache' );
	header( 'Content-Type: text/html; charset=utf-8' );

	// Clear any output buffers that might interfere
	if ( ob_get_length() ) {
		ob_clean();
	}

	// phpcs:disable WordPress.Security.NonceVerification.Missing -- public, read-only request (see above).
	$count    = isset( $_POST['count'] ) ? absint( wp_unslash( $_POST['count'] ) ) : 5;
	$show_art = ! empty( $_POST['art'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['art'] ) );
	// phpcs:enable WordPress.Security.NonceVerification.Missing
	$count = max( 1, min( SPINITRON_PLAYER_TRACKS_MAX, $count ) );

	$spins = spinitron_player_get_recent_tracks();

	if ( is_wp_error( $spins ) ) {
		echo '<p class="spinitron-tracks-message">' . esc_html( $spins->get_error_message() ) . '</p>';
		wp_die();
	}

	$spins = array_slice( $spins, 0, $count );

	if ( empty( $spins ) ) {
		echo '<p class="spinitron-tracks-message">' . esc_html__( 'No recent tracks to show right now.', 'spinitron-player' ) . '</p>';
		wp_die();
	}

	echo '<ol class="spinitron-tracks">';

	foreach ( $spins as $spin ) {
		$time = '';
		if ( '' !== $spin['start'] ) {
			try {
				// Spinitron sends the station's timezone with every spin; the site's own is the fallback.
				$site_tz = get_option( 'timezone_string' );
				$tz      = new DateTimeZone( '' !== $spin['timezone'] ? $spin['timezone'] : ( $site_tz ? $site_tz : 'UTC' ) );
				$time = ( new DateTime( $spin['start'] ) )->setTimezone( $tz )->format( 'g:i A' );
			} catch ( \Exception $e ) {
				$time = '';
			}
		}

		echo '<li class="spinitron-track">';

		if ( $show_art && '' !== $spin['image'] ) {
			// Decorative: the artist, song and release are in the text beside it.
			echo '<img class="track-art" src="' . esc_url( $spin['image'] ) . '" alt="" width="50" height="50" loading="lazy" />';
		} elseif ( $show_art ) {
			// No cover for this track: keep its text lined up with the others.
			echo '<span class="track-art track-art-empty" aria-hidden="true"></span>';
		}

		echo '<span class="track-text">';

		if ( '' !== $time ) {
			echo '<span class="track-time">' . esc_html( $time ) . '</span> ';
		}
		if ( '' !== $spin['artist'] ) {
			echo '<span class="track-artist">' . esc_html( $spin['artist'] ) . '</span> ';
		}
		if ( '' !== $spin['song'] ) {
			echo '<span class="track-song">' . esc_html( $spin['song'] ) . '</span> ';
		}
		if ( '' !== $spin['release'] ) {
			echo '<span class="track-release">' . esc_html( $spin['release'] ) . '</span>';
		}

		echo '</span></li>';
	}

	echo '</ol>';

	wp_die(); // This is required to terminate immediately and return a proper response.
}

add_action( 'wp_ajax_fetch_spinitron_recent_tracks', 'spinitron_player_fetch_spinitron_recent_tracks_callback' );
add_action( 'wp_ajax_nopriv_fetch_spinitron_recent_tracks', 'spinitron_player_fetch_spinitron_recent_tracks_callback' );
