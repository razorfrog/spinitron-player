<?php
/**
 * Show playlists: the [spinitron_playlists] shortcode.
 *
 * Lists a station's (or one show's) past playlists, and the tracks of one
 * playlist, rendered on the site instead of linking out to spinitron.com.
 * The page reads two query arguments, both plain digits:
 *   ?show_id=63556                      that show's playlists, newest first
 *   ?show_id=63556&playlist_id=23341671 one playlist's tracks
 * and ?pg=2 for older pages of a list. ("page" and "paged" are WordPress's own.)
 *
 * Rendered on the server, so the API key never reaches the browser. Every
 * Spinitron response is cached in a transient; see the TTL constants below.
 *
 * @package SpinitronPlayer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/** Seconds a finished playlist and its tracks are cached. They don't change after the show ends. */
define( 'SPINITRON_PLAYER_PLAYLIST_TTL', DAY_IN_SECONDS );

/** Seconds a playlist that is on air now is cached, so new tracks show up. */
define( 'SPINITRON_PLAYER_LIVE_TTL', 2 * MINUTE_IN_SECONDS );

/** Seconds a list of playlists is cached. */
define( 'SPINITRON_PLAYER_PLAYLIST_LIST_TTL', 15 * MINUTE_IN_SECONDS );

/** Seconds a DJ (persona) name is cached. */
define( 'SPINITRON_PLAYER_PERSONA_TTL', WEEK_IN_SECONDS );

/** Seconds a failed request is cached, so an outage doesn't send every page view to Spinitron. */
define( 'SPINITRON_PLAYER_ERROR_TTL', MINUTE_IN_SECONDS );

/**
 * Requests one Spinitron API path, with caching.
 *
 * Uses its own short timeout rather than SpinitronApiClient's 60 seconds,
 * because this runs while the page itself is being built.
 *
 * @param string   $path   API path without the leading slash, e.g. 'playlists' or 'playlists/123'.
 * @param array    $params Query parameters.
 * @param int|null $ttl    Seconds to cache a good answer. Null lets $ttl_callback decide.
 * @param callable $ttl_callback Optional. Gets the decoded answer, returns the seconds to cache it.
 * @return array|WP_Error Decoded JSON, or WP_Error (codes: spinitron_no_key, spinitron_not_found, spinitron_bad_key, spinitron_unreachable).
 */
function spinitron_player_api_get( $path, $params = array(), $ttl = null, $ttl_callback = null ) {
	$url   = 'https://spinitron.com/api/' . $path . ( $params ? '?' . http_build_query( $params ) : '' );
	$cache = 'spinitron_api_' . md5( $url );

	$cached = get_transient( $cache );
	if ( is_array( $cached ) ) {
		if ( isset( $cached['spinitron_player_error'] ) ) {
			return new WP_Error( $cached['spinitron_player_error'], spinitron_player_api_error_message( $cached['spinitron_player_error'] ) );
		}
		return $cached;
	}

	$options = get_option( 'spinitron_player_options', array() );
	$api_key = ( is_array( $options ) && ! empty( $options['spinitron_player_field_api_key'] ) ) ? $options['spinitron_player_field_api_key'] : '';
	if ( '' === $api_key ) {
		// Not cached: it's fixed the moment someone saves a key.
		return new WP_Error( 'spinitron_no_key', spinitron_player_api_error_message( 'spinitron_no_key' ) );
	}

	$response = wp_remote_get(
		$url,
		array(
			'headers' => array( 'Authorization' => 'Bearer ' . $api_key ),
			'timeout' => 10,
		)
	);

	$code = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );
	$data = ( 200 === $code ) ? json_decode( wp_remote_retrieve_body( $response ), true ) : null;

	if ( ! is_array( $data ) ) {
		if ( 404 === $code ) {
			$error = 'spinitron_not_found';
		} elseif ( 401 === $code || 403 === $code ) {
			$error = 'spinitron_bad_key';
		} else {
			$error = 'spinitron_unreachable';
		}
		set_transient( $cache, array( 'spinitron_player_error' => $error ), SPINITRON_PLAYER_ERROR_TTL );
		return new WP_Error( $error, spinitron_player_api_error_message( $error ) );
	}

	if ( null === $ttl ) {
		$ttl = $ttl_callback ? (int) call_user_func( $ttl_callback, $data ) : SPINITRON_PLAYER_PLAYLIST_LIST_TTL;
	}
	set_transient( $cache, $data, max( 1, $ttl ) );

	return $data;
}

/**
 * Visitor-facing text for an API error code.
 *
 * @param string $code Error code from spinitron_player_api_get().
 * @return string
 */
function spinitron_player_api_error_message( $code ) {
	switch ( $code ) {
		case 'spinitron_no_key':
			return __( 'Playlists are not set up yet: add a Spinitron API key in the plugin settings.', 'spinitron-player' );
		case 'spinitron_bad_key':
			return __( 'Invalid API key. Please make sure your Spinitron API key is valid.', 'spinitron-player' );
		case 'spinitron_not_found':
			return __( 'That playlist could not be found.', 'spinitron-player' );
		default:
			return __( 'Unable to reach the Spinitron API right now. Please try again shortly.', 'spinitron-player' );
	}
}

/**
 * Whether a playlist (or anything with an ISO 'end') is still on air.
 *
 * @param array $playlist Playlist from the API.
 * @return bool
 */
function spinitron_player_is_live( $playlist ) {
	$end = isset( $playlist['end'] ) ? strtotime( $playlist['end'] ) : false;
	return false !== $end && $end > time();
}

/**
 * Seconds to cache a list of playlists: short when its newest one is on air.
 *
 * @param array $data Decoded /playlists answer.
 * @return int
 */
function spinitron_player_playlist_list_ttl( $data ) {
	if ( ! empty( $data['items'][0] ) && spinitron_player_is_live( $data['items'][0] ) ) {
		return SPINITRON_PLAYER_LIVE_TTL;
	}
	return SPINITRON_PLAYER_PLAYLIST_LIST_TTL;
}

/**
 * Seconds to cache one playlist: short while it's on air.
 *
 * @param array $data Decoded /playlists/<id> answer.
 * @return int
 */
function spinitron_player_playlist_ttl( $data ) {
	return spinitron_player_is_live( $data ) ? SPINITRON_PLAYER_LIVE_TTL : SPINITRON_PLAYER_PLAYLIST_TTL;
}

/**
 * The DJ name to print for a playlist, or '' when there is none to show.
 *
 * Automation playlists and playlists marked "hide DJ" in Spinitron get none.
 *
 * @param array $playlist Playlist from the API.
 * @return string
 */
function spinitron_player_playlist_dj( $playlist ) {
	if ( ! empty( $playlist['automation'] ) || ! empty( $playlist['hide_dj'] ) || empty( $playlist['persona_id'] ) ) {
		return '';
	}
	$persona = spinitron_player_api_get( 'personas/' . absint( $playlist['persona_id'] ), array(), SPINITRON_PLAYER_PERSONA_TTL );
	return ( ! is_wp_error( $persona ) && ! empty( $persona['name'] ) ) ? (string) $persona['name'] : '';
}

/**
 * Formats an API timestamp in the station's timezone.
 *
 * @param string $iso      ISO 8601 time from the API.
 * @param string $timezone Station timezone from the API ('' falls back to the site's).
 * @param string $format   PHP date format.
 * @return string '' when the time can't be read.
 */
function spinitron_player_format_time( $iso, $timezone, $format ) {
	$timestamp = strtotime( (string) $iso );
	if ( false === $timestamp ) {
		return '';
	}
	try {
		$tz = new DateTimeZone( '' !== (string) $timezone ? $timezone : wp_timezone_string() );
	} catch ( \Exception $e ) {
		$tz = wp_timezone();
	}
	return wp_date( $format, $timestamp, $tz );
}

/**
 * "12:00 PM – 2:00 PM" for a playlist.
 *
 * @param array $playlist Playlist from the API.
 * @return string
 */
function spinitron_player_playlist_hours( $playlist ) {
	$tz    = isset( $playlist['timezone'] ) ? $playlist['timezone'] : '';
	$start = spinitron_player_format_time( isset( $playlist['start'] ) ? $playlist['start'] : '', $tz, get_option( 'time_format', 'g:i a' ) );
	$end   = spinitron_player_format_time( isset( $playlist['end'] ) ? $playlist['end'] : '', $tz, get_option( 'time_format', 'g:i a' ) );
	if ( '' === $start ) {
		return '';
	}
	return '' === $end ? $start : $start . ' – ' . $end;
}

/**
 * Builds a link to this same page with the given playlist query arguments.
 *
 * Starts from the page's permalink (not the request URL), so only our own
 * arguments carry over.
 *
 * @param array $args show_id, playlist_id, pg; zero or empty values are left out.
 * @return string Unescaped URL.
 */
function spinitron_player_playlists_url( $args ) {
	$args = array_filter( array_map( 'absint', $args ) );
	if ( isset( $args['pg'] ) && 1 === $args['pg'] ) {
		unset( $args['pg'] );
	}
	return add_query_arg( $args, get_permalink() );
}

/**
 * Implements [spinitron_playlists].
 *
 * Usage:
 *   [spinitron_playlists]                 recent playlists for the whole station, or for ?show_id=
 *   [spinitron_playlists show_id="63556"] always that show
 * Attributes:
 *   show_id    fixed show; when empty, the page's ?show_id= is used (and with neither, all shows)
 *   per_page   playlists per page, 1 to 50, default 10
 *   art        yes/no, cover art beside each track, default yes
 *   automation yes/no, list automation playlists too, default no
 *
 * @param array|string $atts Shortcode attributes.
 * @return string HTML.
 */
function spinitron_player_playlists_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'show_id'    => '',
			'per_page'   => 10,
			'art'        => 'yes',
			'automation' => 'no',
		),
		$atts,
		'spinitron_playlists'
	);

	$yes = array( 'yes', 'true', '1', 'on' );

	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- public, read-only page arguments.
	$fixed_show  = absint( $atts['show_id'] );
	$show_id     = $fixed_show ? $fixed_show : ( isset( $_GET['show_id'] ) ? absint( wp_unslash( $_GET['show_id'] ) ) : 0 );
	$playlist_id = isset( $_GET['playlist_id'] ) ? absint( wp_unslash( $_GET['playlist_id'] ) ) : 0;
	$page        = isset( $_GET['pg'] ) ? max( 1, min( 1000, absint( wp_unslash( $_GET['pg'] ) ) ) ) : 1;
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	$options = array(
		'per_page'   => max( 1, min( 50, absint( $atts['per_page'] ) ) ),
		'art'        => in_array( strtolower( trim( (string) $atts['art'] ) ), $yes, true ),
		'automation' => in_array( strtolower( trim( (string) $atts['automation'] ) ), $yes, true ),
		'fixed_show' => $fixed_show,
	);

	if ( $playlist_id ) {
		$html = spinitron_player_render_playlist( $playlist_id, $show_id, $options );
	} else {
		$html = spinitron_player_render_playlist_list( $show_id, $page, $options );
	}

	return '<div class="spinitron-playlists">' . $html . '</div>';
}
add_shortcode( 'spinitron_playlists', 'spinitron_player_playlists_shortcode' );

/**
 * A message paragraph.
 *
 * @param string $text Plain text.
 * @return string
 */
function spinitron_player_playlists_message( $text ) {
	return '<p class="spinitron-playlists-message">' . esc_html( $text ) . '</p>';
}

/**
 * Playlists fetched per API request when building a list. Lists are paged
 * after automation and empty playlists are filtered out (a show automated for
 * weeks would otherwise leave pages empty), so the plugin reads in batches.
 */
define( 'SPINITRON_PLAYER_PLAYLIST_BATCH', 50 );

/** Most API batches one list reads: 500 playlists, a month of a station or years of a weekly show. */
define( 'SPINITRON_PLAYER_PLAYLIST_BATCHES', 10 );

/**
 * Collects playlists newest first, skipping the ones not worth a visitor's
 * click, until it has $want of them or runs out.
 *
 * @param int  $show_id    Show, or 0 for every show.
 * @param int  $want       How many playlists the caller needs.
 * @param bool $automation Keep automation playlists.
 * @return array|WP_Error array( 'items' => playlists ), or WP_Error when the first request fails.
 */
function spinitron_player_collect_playlists( $show_id, $want, $automation ) {
	$items = array();

	for ( $batch = 1; $batch <= SPINITRON_PLAYER_PLAYLIST_BATCHES && count( $items ) < $want; $batch++ ) {
		$params = array(
			'count' => SPINITRON_PLAYER_PLAYLIST_BATCH,
			'page'  => $batch,
		);
		if ( $show_id ) {
			$params['show_id'] = $show_id;
		}

		$data = spinitron_player_api_get( 'playlists', $params, null, 'spinitron_player_playlist_list_ttl' );
		if ( is_wp_error( $data ) ) {
			if ( 1 === $batch ) {
				return $data;
			}
			break; // Show what was read; older pages are simply missing.
		}

		$batch_items = isset( $data['items'] ) && is_array( $data['items'] ) ? $data['items'] : array();
		foreach ( $batch_items as $playlist ) {
			if ( ! $automation && ! empty( $playlist['automation'] ) ) {
				continue;
			}
			// Spinitron sometimes leaves an empty duplicate next to the real playlist.
			if ( isset( $playlist['spinsCount'] ) && 0 === (int) $playlist['spinsCount'] && ! spinitron_player_is_live( $playlist ) ) {
				continue;
			}
			$items[] = $playlist;
		}

		if ( count( $batch_items ) < SPINITRON_PLAYER_PLAYLIST_BATCH ) {
			break; // Last page.
		}
	}

	return array( 'items' => $items );
}

/**
 * Renders a list of playlists, newest first, with Newer/Older links.
 *
 * @param int   $show_id Show to list, or 0 for every show.
 * @param int   $page    Page number, from 1.
 * @param array $options Parsed shortcode options.
 * @return string HTML.
 */
function spinitron_player_render_playlist_list( $show_id, $page, $options ) {
	$result = spinitron_player_collect_playlists( $show_id, $page * $options['per_page'] + 1, $options['automation'] );
	if ( is_wp_error( $result ) ) {
		return spinitron_player_playlists_message( $result->get_error_message() );
	}

	$items     = array_slice( $result['items'], ( $page - 1 ) * $options['per_page'], $options['per_page'] );
	$has_older = count( $result['items'] ) > $page * $options['per_page'];

	$out = '';

	if ( $show_id && ! $options['fixed_show'] ) {
		$first = reset( $items );
		if ( $first && ! empty( $first['title'] ) ) {
			$out .= '<h2 class="spinitron-playlists-title">' . esc_html( $first['title'] ) . '</h2>';
		}
	}

	if ( empty( $items ) ) {
		return $out . spinitron_player_playlists_message( __( 'No playlists to show.', 'spinitron-player' ) );
	}

	$out .= '<ul class="spinitron-playlist-list">';
	foreach ( $items as $playlist ) {
		$tz   = isset( $playlist['timezone'] ) ? $playlist['timezone'] : '';
		$date = spinitron_player_format_time( isset( $playlist['start'] ) ? $playlist['start'] : '', $tz, 'D, M j, Y' );
		$url  = spinitron_player_playlists_url(
			array(
				'show_id'     => $show_id ? $show_id : ( isset( $playlist['show_id'] ) ? $playlist['show_id'] : 0 ),
				'playlist_id' => isset( $playlist['id'] ) ? $playlist['id'] : 0,
			)
		);
		$dj   = spinitron_player_playlist_dj( $playlist );

		$out .= '<li class="spinitron-playlist-item">';
		$out .= '<a class="playlist-link" href="' . esc_url( $url ) . '">';
		// Across all shows the show name leads; on one show's list the date does.
		if ( ! $show_id && ! empty( $playlist['title'] ) ) {
			$out .= '<span class="playlist-show">' . esc_html( $playlist['title'] ) . '</span> ';
		}
		$out .= '<span class="playlist-date">' . esc_html( $date ) . '</span>';
		$out .= '</a> ';
		$out .= '<span class="playlist-hours">' . esc_html( spinitron_player_playlist_hours( $playlist ) ) . '</span>';
		if ( '' !== $dj ) {
			/* translators: %s: DJ name */
			$out .= ' <span class="playlist-dj">' . esc_html( sprintf( __( 'with %s', 'spinitron-player' ), $dj ) ) . '</span>';
		}
		if ( spinitron_player_is_live( $playlist ) ) {
			$out .= ' <span class="playlist-live">' . esc_html__( 'On air now', 'spinitron-player' ) . '</span>';
		}
		$out .= '</li>';
	}
	$out .= '</ul>';

	if ( $page > 1 || $has_older ) {
		$base = array( 'show_id' => $options['fixed_show'] ? 0 : $show_id );
		$out .= '<nav class="spinitron-playlists-pages" aria-label="' . esc_attr__( 'Playlist pages', 'spinitron-player' ) . '">';
		if ( $page > 1 ) {
			$out .= '<a class="playlists-newer" href="' . esc_url( spinitron_player_playlists_url( $base + array( 'pg' => $page - 1 ) ) ) . '">' . esc_html__( '← Newer', 'spinitron-player' ) . '</a>';
		}
		if ( $has_older ) {
			$out .= '<a class="playlists-older" href="' . esc_url( spinitron_player_playlists_url( $base + array( 'pg' => $page + 1 ) ) ) . '">' . esc_html__( 'Older →', 'spinitron-player' ) . '</a>';
		}
		$out .= '</nav>';
	}

	return $out;
}

/**
 * Renders one playlist: show, date, DJ, and its tracks in the order they played.
 *
 * @param int   $playlist_id Playlist ID.
 * @param int   $show_id     Show the visitor came from (0 when unknown), for the back link.
 * @param array $options     Parsed shortcode options.
 * @return string HTML.
 */
function spinitron_player_render_playlist( $playlist_id, $show_id, $options ) {
	$playlist = spinitron_player_api_get( 'playlists/' . $playlist_id, array(), null, 'spinitron_player_playlist_ttl' );
	if ( is_wp_error( $playlist ) ) {
		return spinitron_player_playlists_message( $playlist->get_error_message() );
	}

	$playlist_show = isset( $playlist['show_id'] ) ? absint( $playlist['show_id'] ) : 0;

	// A page fixed to one show shows only that show's playlists.
	if ( $options['fixed_show'] && $playlist_show !== $options['fixed_show'] ) {
		return spinitron_player_playlists_message( spinitron_player_api_error_message( 'spinitron_not_found' ) );
	}

	$live = spinitron_player_is_live( $playlist );
	$out  = '';

	$back_show = $options['fixed_show'] ? 0 : ( $show_id ? $show_id : $playlist_show );
	$title     = isset( $playlist['title'] ) ? (string) $playlist['title'] : '';
	$back_text = '' !== $title
		/* translators: %s: show name */
		? sprintf( __( '← All playlists for %s', 'spinitron-player' ), $title )
		: __( '← All playlists', 'spinitron-player' );
	$out      .= '<p class="spinitron-playlist-back"><a href="' . esc_url( spinitron_player_playlists_url( array( 'show_id' => $back_show ) ) ) . '">' . esc_html( $back_text ) . '</a></p>';

	if ( '' !== $title ) {
		$out .= '<h2 class="spinitron-playlist-title">' . esc_html( $title ) . '</h2>';
	}

	$tz   = isset( $playlist['timezone'] ) ? $playlist['timezone'] : '';
	$meta = array_filter(
		array(
			spinitron_player_format_time( isset( $playlist['start'] ) ? $playlist['start'] : '', $tz, get_option( 'date_format', 'F j, Y' ) ),
			spinitron_player_playlist_hours( $playlist ),
		)
	);
	$dj   = spinitron_player_playlist_dj( $playlist );
	$out .= '<p class="spinitron-playlist-meta">' . esc_html( implode( ', ', $meta ) );
	if ( '' !== $dj ) {
		/* translators: %s: DJ name */
		$out .= ' <span class="playlist-dj">' . esc_html( sprintf( __( 'with %s', 'spinitron-player' ), $dj ) ) . '</span>';
	}
	if ( $live ) {
		$out .= ' <span class="playlist-live">' . esc_html__( 'On air now', 'spinitron-player' ) . '</span>';
	}
	$out .= '</p>';

	if ( ! empty( $playlist['episode_name'] ) ) {
		$out .= '<p class="spinitron-playlist-episode">' . esc_html( $playlist['episode_name'] ) . '</p>';
	}

	// 200 is the API's page limit; a show with more tracks than that shows its first 200.
	$spins = spinitron_player_api_get(
		'spins',
		array(
			'playlist_id' => $playlist_id,
			'count'       => 200,
		),
		$live ? SPINITRON_PLAYER_LIVE_TTL : SPINITRON_PLAYER_PLAYLIST_TTL
	);
	if ( is_wp_error( $spins ) ) {
		return $out . spinitron_player_playlists_message( $spins->get_error_message() );
	}

	// The API sends newest first; a playlist reads in the order it was played.
	$items = isset( $spins['items'] ) && is_array( $spins['items'] ) ? array_reverse( $spins['items'] ) : array();
	if ( empty( $items ) ) {
		return $out . spinitron_player_playlists_message( __( 'No tracks in this playlist yet.', 'spinitron-player' ) );
	}

	$out .= '<table class="spinitron-spins' . ( $options['art'] ? ' has-art' : '' ) . '"><thead><tr>';
	if ( $options['art'] ) {
		$out .= '<th scope="col" class="spin-art"><span class="screen-reader-text">' . esc_html__( 'Cover', 'spinitron-player' ) . '</span></th>';
	}
	$out .= '<th scope="col" class="spin-time">' . esc_html__( 'Time', 'spinitron-player' ) . '</th>'
		. '<th scope="col" class="spin-artist">' . esc_html__( 'Artist', 'spinitron-player' ) . '</th>'
		. '<th scope="col" class="spin-song">' . esc_html__( 'Song', 'spinitron-player' ) . '</th>'
		. '<th scope="col" class="spin-release">' . esc_html__( 'Release', 'spinitron-player' ) . '</th>'
		. '<th scope="col" class="spin-label">' . esc_html__( 'Label', 'spinitron-player' ) . '</th>'
		. '</tr></thead><tbody>';

	foreach ( $items as $spin ) {
		$out .= '<tr>';
		if ( $options['art'] ) {
			$out .= '<td class="spin-art">';
			if ( ! empty( $spin['image'] ) ) {
				$src = is_ssl() ? set_url_scheme( $spin['image'], 'https' ) : $spin['image'];
				// Decorative: the artist, song and release are in the same row.
				$out .= '<img src="' . esc_url( $src ) . '" alt="" width="50" height="50" loading="lazy" />';
			}
			$out .= '</td>';
		}
		$fields = array(
			'spin-time'    => spinitron_player_format_time( isset( $spin['start'] ) ? $spin['start'] : '', isset( $spin['timezone'] ) ? $spin['timezone'] : $tz, get_option( 'time_format', 'g:i a' ) ),
			'spin-artist'  => isset( $spin['artist'] ) ? $spin['artist'] : '',
			'spin-song'    => isset( $spin['song'] ) ? $spin['song'] : '',
			'spin-release' => isset( $spin['release'] ) ? $spin['release'] : '',
			'spin-label'   => isset( $spin['label'] ) ? $spin['label'] : '',
		);
		foreach ( $fields as $class => $value ) {
			$out .= '<td class="' . esc_attr( $class ) . '">' . esc_html( (string) $value ) . '</td>';
		}
		$out .= '</tr>';
	}

	$out .= '</tbody></table>';

	return $out;
}
