=== Spinitron Player ===
Contributors: razorfrog
Tags: spinitron, radio, music, player, stream
Requires at least: 5.3
Tested up to: 7.1
Requires PHP: 7.2
Stable tag: 1.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A streaming player for radio stations using Spinitron, with live data integration.

== Description ==
The Spinitron Player plugin integrates live streaming and playlist data from Spinitron into WordPress sites, offering listeners real-time track information and audio streaming. Designed for ease of use and customization, it provides radio stations with a straightforward solution to share their live content and connect with audiences online.

== Third-Party Service Integration ==
This plugin makes use of the Spinitron API to fetch and display live radio show and playlist information. The integration with Spinitron's services is essential for providing up-to-date content within the plugin.

- Spinitron Website: https://spinitron.com/
- Spinitron API Documentation: https://spinitron.github.io/v2api/

Show images, and track cover art in `[spinitron_playlists]` and in `[spinitron_recent_tracks art="yes"]`, are loaded by the visitor's browser from the image addresses Spinitron supplies. Cover art is often hosted by a third party such as Apple (mzstatic.com).

== Legal and Privacy==
Please review Spinitron's Terms of Use and Privacy Policy to understand the data usage and legal considerations:

- terms of Service: https://forum.spinitron.com/tos
- Privacy Policy: https://forum.spinitron.com/privacy

By using the Spinitron Player plugin, you agree to comply with these terms and acknowledge the data interactions with Spinitron's services.

== Installation ==
1. Upload the plugin files to the `/wp-content/plugins/spinitron-player` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.

== Usage ==
The following shortcodes are available:

- `[spinitron_player]` - For Spinitron live player interface.
- `[spinitron_play_button]` - For stream play button.
- `[spinitron_recent_tracks]` - For a list of the most recent tracks played, refreshed every 30 seconds. Options: `count` (1 to 20, default 5) and `art` (`yes` to show cover art), e.g. `[spinitron_recent_tracks count="10" art="yes"]`.
- `[spinitron_playlists]` - Past playlists and their tracks, on your own site. Put it on one page: it lists the station's recent playlists, newest first, and each one links to its tracks on the same page. Link to the page with `?show_id=<Spinitron show ID>` to list one show's playlists. Options: `show_id` (always that show), `per_page` (1 to 50, default 10), `art` (`no` to hide cover art) `automation` (`yes` to list automation playlists too) and `station` (your Spinitron station name as in its web address, e.g. `KZSC`, to link each playlist to its page on spinitron.com, where recent shows can be played back), e.g. `[spinitron_playlists per_page="20"]`.

We will be working on new UI options in future releases.

== Changelog ==

= 1.3.0 =
- New `[spinitron_playlists]` shortcode: a station's or a show's past playlists, and each playlist's tracks (time, artist, song, release, label and cover art), rendered on your own site. Automation playlists and Spinitron's empty duplicate playlists are left out. Data is cached: finished playlists for a day, the playlist on air for 2 minutes. The API key never reaches the browser.
- Now requires WordPress 5.3.

= 1.2.0 =
- New `[spinitron_recent_tracks]` shortcode: a list of the most recent tracks played (time, artist, song and release, with optional cover art) that refreshes every 30 seconds. Spinitron is contacted at most once every 30 seconds, however many visitors are on the page.
- The player now names the DJ who is on air when a playlist is running for the current show, so a substitute DJ is shown instead of the show's usual host.

= 1.1.2 =
- The Play button no longer streams another station by default. With no Livestream URL saved, visitors see nothing and editors see a reminder to add one in the settings.
- The stylesheet and script are now versioned with the plugin, so changes are no longer cached after an update.
- Fixed a PHP 8.2 deprecation notice in the Spinitron API client.
- Accessibility: the Play button now points at the audio element it controls.

= 1.1.1 =
- Cached show data is now served without contacting Spinitron at all; previously every player refresh still made one API request to check the key.
- Show titles and DJ names containing characters like "&" are no longer double-escaped (e.g. "Rock &amp;amp; Roll").
- Show images are checked with WordPress's HTTP API, following redirects.

= 1.1.0 =
- Fixed a bug where the "today" show cache could be stored with no expiration, permanently freezing the display on whatever show happened to be airing at the moment the cache was rebuilt (most noticeably, sites getting stuck on an overnight/graveyard-slot show all day). Sites already affected are cleaned up automatically on update.
- The DJ name and show-image lookups are now resolved once per 5-minute cache refresh instead of being re-fetched from the Spinitron API on every page view.
- Hardened error handling around Spinitron API requests so a temporary API hiccup shows a friendly message instead of a broken response.

= 1.0.9 =
- Security updates
- Updated for WP Core compatibility

= 1.0.8 =
- Fixed play button JS trigger
- Fixed multi button issue
- Updated for WP Core compatibility

= 1.0.7 =
- Fixed AJAX caching issue
- Updated for WP Core compatibility

= 1.0.6 =
- Fixed show image validation in ajax-handler-today.php
- Updated for WP Core compatibility

= 1.0.5 =
- Improved API client requests
- Improved WordPress transients
- Improved AJAX request handling

= 1.0.4 =
- Added ui/today.php
- Updated style.css
- Updated API validations
- Updated player loading interface
- Updated shortcode to allow multiple instances

= 1.0.3 =
- Added ajax/ajax-handler-today.php
- Added app/spinitron-fetch-today.js
- Updated shortcode player builder
- Updated entire code structure
- Updated entire cache handling

= 1.0.2 =
- Added required "Image Fallback" URL field
- Updated cache timeout and API response validation
- Updated settings fields descriptions

= 1.0.1 =
- Added "Separate Time and DJ" checkbox
- Added "Duplicate Show Image" checkbox
- Updated for WP Core compatibility

= 1.0.0 =
- Initial release
