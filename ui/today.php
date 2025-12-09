<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Prefixed globals to satisfy coding standards.
$spinitron_options              = get_option( 'spinitron_player_options' );
$spinitron_fallback_image       = isset( $spinitron_options['spinitron_player_field_image_fallback'] ) ? $spinitron_options['spinitron_player_field_image_fallback'] : '';
$spinitron_separate_time_dj     = isset( $spinitron_options['spinitron_player_field_separate_time_dj'] ) ? $spinitron_options['spinitron_player_field_separate_time_dj'] : 0;
$spinitron_duplicate_show_image = isset( $spinitron_options['spinitron_player_field_duplicate_show_image'] ) ? $spinitron_options['spinitron_player_field_duplicate_show_image'] : 0;

global $spinitron_instance_count;
$spinitron_container_id = 'spinitron-show-container-' . $spinitron_instance_count;
?>

<div id="<?php echo esc_attr( $spinitron_container_id ); ?>">
	<div class="spinitron-player spinitron-player-loading">
		<?php if ( $spinitron_duplicate_show_image ) : ?>
			<img class="show-image-outter" src="<?php echo esc_url( $spinitron_fallback_image ); ?>" alt="Fallback radio cover image" />
		<?php endif; ?>
		<div class="show-image">
			<img src="<?php echo esc_url( $spinitron_fallback_image ); ?>" alt="Fallback radio cover image" />
		</div>
		<div class="show-details">
			<p class="show-title">Loading...</p>
		</div>
	</div>
</div>
