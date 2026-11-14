/* Fills every [spinitron_recent_tracks] list and keeps it current. */
(function () {
	'use strict';

	var REFRESH_MS = 30000;

	function fetchTracks(container) {
		var body = new URLSearchParams();
		body.append('action', 'fetch_spinitron_recent_tracks');
		body.append('count', container.getAttribute('data-count') || '5');
		body.append('art', container.getAttribute('data-art') || '0');

		fetch(spinitron_tracks_params.ajax_url, {
			method: 'POST',
			credentials: 'same-origin',
			cache: 'no-store',
			body: body
		})
			.then(function (response) {
				if (!response.ok) {
					throw new Error('HTTP ' + response.status);
				}
				return response.text();
			})
			.then(function (html) {
				// Leave the list alone when nothing changed, so screen readers
				// and text selection aren't interrupted every refresh.
				if (container.spinitronLastHtml === html) {
					return;
				}
				container.spinitronLastHtml = html;
				container.innerHTML = html;
			})
			.catch(function (error) {
				console.log('Error fetching Spinitron recent tracks:', error);
			});
	}

	function init() {
		var containers = document.querySelectorAll('.spinitron-recent-tracks');
		Array.prototype.forEach.call(containers, function (container) {
			fetchTracks(container);
			setInterval(function () {
				// No requests from background tabs.
				if (!document.hidden) {
					fetchTracks(container);
				}
			}, REFRESH_MS);
		});

		// Catch up as soon as a background tab is looked at again.
		document.addEventListener('visibilitychange', function () {
			if (!document.hidden) {
				Array.prototype.forEach.call(containers, fetchTracks);
			}
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
