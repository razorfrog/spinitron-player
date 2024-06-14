<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

$options = get_option('spinitron_player_options');
$fallback_image = isset($options['spinitron_player_field_image_fallback']) ? esc_url($options['spinitron_player_field_image_fallback']) : '';
$separate_time_dj = isset($options['spinitron_player_field_separate_time_dj']) ? $options['spinitron_player_field_separate_time_dj'] : 0;
$duplicate_show_image = isset($options['spinitron_player_field_duplicate_show_image']) ? $options['spinitron_player_field_duplicate_show_image'] : 0;

global $spinitron_instance_count;
$container_id = 'spinitron-show-container-' . $spinitron_instance_count;
?>

<div id="<?php echo $container_id; ?>">
		<div class="spinitron-player spinitron-player-loading">
				<?php if ($duplicate_show_image) : ?>
						<img class="show-image-outter" src="<?php echo $fallback_image; ?>" alt="Fallback radio cover image" />
				<?php endif; ?>
				<div class="show-image">
						<img src="<?php echo $fallback_image; ?>" alt="Fallback radio cover image" />
				</div>
				<div class="show-details">
						<p class="show-title">Loading...</p>
				</div>
		</div>
</div>
