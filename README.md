# Spinitron Player

The Spinitron Player plugin integrates live streaming and playlist data from Spinitron into WordPress sites, offering listeners real-time track information and audio streaming. Designed for ease of use and customization, it provides radio stations with a straightforward solution to share their live content and connect with audiences online.

- **WordPress.org:** https://wordpress.org/plugins/spinitron-player/
- **Plugin page:** https://razorfrog.com/spinitron-player/
- **Current release:** 1.1.0 · requires WordPress 5.2 and PHP 7.2 · tested up to WordPress 7.0.3
- **License:** GPLv2 or later

`readme.txt` is the WordPress.org plugin page (description, FAQ, changelog). For help, use the plugin's support forum on WordPress.org.

## Checks

Every push runs `.github/workflows/check.yml`:

- `php -l` on every PHP file under PHP 7.2 and 8.4
- PHP_CodeSniffer with `phpcs.xml.dist`: PHP 7.2+ compatibility (PHPCompatibilityWP) plus WordPress security sniffs (output escaping, nonces, input sanitizing, prepared SQL, deprecated functions)

Run the same locally with `phpcs` from the repo root once WPCS and PHPCompatibilityWP are installed.
