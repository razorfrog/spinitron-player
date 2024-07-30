=== Spinitron Player ===
Contributors: razorfrog
Tags: spinitron, radio, music, player, stream
Requires at least: 5.0
Tested up to: 6.6.1
Requires PHP: 7.2
Stable tag: 1.0.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A streaming player for radio stations using Spinitron, with live data integration.

== Description ==
The Spinitron Player plugin integrates live streaming and playlist data from Spinitron into WordPress sites, offering listeners real-time track information and audio streaming. Designed for ease of use and customization, it provides radio stations with a straightforward solution to share their live content and connect with audiences online.

== Third-Party Service Integration ==
This plugin makes use of the Spinitron API to fetch and display live radio show and playlist information. The integration with Spinitron's services is essential for providing up-to-date content within the plugin.

- Spinitron Website: https://spinitron.com/
- Spinitron API Documentation: https://spinitron.github.io/v2api/

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

We will be working on new UI options in future releases.

== Changelog ==

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
