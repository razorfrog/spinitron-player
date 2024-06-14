jQuery(document).ready(function($) {
		function fetchSpinitronShowToday() {
				$.ajax({
						url: spinitron_params.ajax_url,
						type: 'POST',
						data: {
								action: 'fetch_spinitron_show_today',
								cache_bust: new Date().getTime() // Cache-busting parameter
						},
						success: function(response) {
								$('#spinitron-show-container').html(response);
						},
						error: function(error) {
								console.log('Error fetching Spinitron show:', error);
						}
				});
		}

		// Fetch the show data on page load
		fetchSpinitronShowToday();

		// Optional: Refresh data periodically (e.g., every 5 minutes)
		setInterval(fetchSpinitronShowToday, 300000); // 300000 ms = 5 minutes
});
