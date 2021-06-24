if ('serviceWorker' in navigator) {
	navigator.serviceWorker.register('/service-worker.js');
}

$(function() {
	var $root = $('#root');
	var $partial = $('#partial');
	var $contextMenu = $('#contextMenu');
	var $searchBar = $('#searchBar');
	var $clearSearchBarButton = $('#clearSearchBarButton');

	new SearchHandler($searchBar, $clearSearchBarButton);
	new PartialManager($partial, '.simplebar-content-wrapper');
	new CustomContextMenu(
		$contextMenu,
		$root,
		{
			QUEUE: {
				text: "Add to queue",
				callback: async function($target) {
					if ($target.data('song-id')) {
						MusicControl.sharedInstance.music().queue().push(
							$target.data('song-id')
						);
					} else if ($target.data('album-id')) {
						var res = await $.get(`/api/album/${$target.data('album-id')}`);
						res
							.songIds
							.forEach(songId => MusicControl.sharedInstance.music().queue().push(songId))
						;
					}
					
					showToastNotification("Added to queue");					
				}
			},
			GO_TO_ALBUM: {
				text: "Go to album",
				callback: function($target) {
					PartialManager.sharedInstance.loadPartial(`/album/${$target.data('album-id')}`);
				}
			}
		}
	);

	PartialManager.sharedInstance.on('partialloaded', e => {
		SearchHandler.sharedInstance.reset();

		if (window.location.pathname !== `/album/${MusicControl.sharedInstance.albumId()}`) {
			MusicControl.sharedInstance.elements().$nowPlayingButton.removeClass('active');
		}
	});
});

function initSlider($slider, initialValue, events, disabled = false) {
	$slider.slider({
		min: 0,
		max: 100,
		value: initialValue,
		range: "min",
		step: 0.1,
		create: events.create || function() {},
		change: events.change || function() {},
		slide: events.slide || function() {},
		start: events.start || function() {},
		stop: events.stop || function() {},
		disabled: disabled
	});

	var $sliderHandle = $slider.find('.ui-slider-handle');

	$slider.mouseenter(e => $sliderHandle.show());
	$slider.mouseleave(e => {
		if (!$slider.is(":active")) {
			$sliderHandle.hide()
		}
	});
	$slider.mousedown(e => {
		$(document).one('mouseup', e => {
			if (!$slider.is(":hover")) {
				$sliderHandle.hide()
			}
		});
	});

	if ('change' in events) {
		events.change(null, { value: $slider.slider("value") });
	}
}

function secondsToTimeString(totalSeconds) {
	var minutes = Math.floor(totalSeconds / 60);
	var seconds = Math.floor(totalSeconds - (minutes * 60));

	return `${minutes}:${str_pad_left(seconds, "0", 2)}`;
}

function str_pad_left(string, pad, length) {
    return (new Array(length + 1).join(pad) + string).slice(-length);
}

$.fn.scrollStopped = function(callback) {
	var self = this;

	$(self).scroll(function(e) {
		clearTimeout(
			$(self).data('scrollTimeout')
		);
		
		$(self).data(
			'scrollTimeout',
			setTimeout(
				callback.bind(self),
				250,
				e
			)
		);
	});
};

function shuffle(arr, options) {
	if (!Array.isArray(arr)) {
		throw new Error('shuffle expect an array as parameter.');
	}
  
	options = options || {};
  
	var collection = arr;
	var	len = arr.length;
	var	rng = options.rng || Math.random;
	var	random;
	var	temp;
  
	if (options.copy === true) {
		collection = arr.slice();
	}
  
	while (len) {
		random = Math.floor(rng() * len);
		len--;
		temp = collection[len];
		collection[len] = collection[random];
		collection[random] = temp;
	}
  
	return collection;
}

function showToastNotification(message, timeout = 3000) {
	var $toastNotification = $('#toastNotification')
		.text(message)
		.addClass('show')
	;

	setTimeout(() => $toastNotification.removeClass('show'), timeout);
}

function updateBodyColour(hex, gradient = true) {
	$('meta[name="theme-color"]').attr('content', hex);
	$('body').css(
		'background',
		(gradient) ? `linear-gradient(${hex} 0%, ${hex} 5%, #181818 75%)` : hex
	);
}