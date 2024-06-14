jQuery(document).ready(function($) {
		function fetchSpinitronShowToday(containerId) {
				$.ajax({
						url: spinitron_params.ajax_url,
						type: 'POST',
						data: {
								action: 'fetch_spinitron_show_today',
								cache_bust: new Date().getTime() // Cache-busting parameter
						},
						success: function(response) {
								$('#' + containerId).html(response);
								$('#' + containerId + ' .spinitron-player').removeClass('spinitron-player-loading');
						},
						error: function(error) {
								console.log('Error fetching Spinitron show:', error);
						}
				});
		}

		// Iterate over each spinitron-show-container and fetch data
		$('[id^="spinitron-show-container"]').each(function() {
				var containerId = $(this).attr('id');
				fetchSpinitronShowToday(containerId);

				// Optional: Refresh data periodically (e.g., every 5 minutes)
				setInterval(function() {
						fetchSpinitronShowToday(containerId);
				}, 300000); // 300000 ms = 5 minutes
		});
});
