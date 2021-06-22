if ('serviceWorker' in navigator) {
	navigator.serviceWorker.register('/service-worker.js');
}

$(function() {
	var $partial = $('#partial');
	var $contextMenu = $('#contextMenu');
	var $searchBar = $('#searchBar');
	var $clearSearchBar = $('#clearSearchBar');
	var $nowPlayingButton = $("#nowPlayingButton");

	new SearchHandler($searchBar, $clearSearchBar);
	new PartialManager($partial, $nowPlayingButton);
	new CustomContextMenu(
		$contextMenu,
		{
			QUEUE: {
				text: "Add to queue",
				callback: async function($target) {
					if ($target.data('song-id')) {
						MusicPlayer.sharedInstance.queue().push(
							$target.data('song-id')
						);
					} else if ($target.data('album-id')) {
						var res = await $.get('/api/album/' + $target.data('album-id'));
						res
							.songIds
							.forEach(songId => MusicPlayer.sharedInstance.queue().push(songId))
						;
					}
					
					showToastNotification("Added to queue");					
				}
			},
			GO_TO_ALBUM: {
				text: "Go to album",
				callback: function($target) {
					PartialManager.sharedInstance.loadPartial('/album/' + $target.data('album-id'));
				}
			}
		}
	);
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
	var that = this, $this = $(that);
	$this.scroll(function(ev) {
		clearTimeout($this.data('scrollTimeout'));
		$this.data('scrollTimeout', setTimeout(callback.bind(that), 250, ev));
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
		len -= 1;
		temp = collection[len];
		collection[len] = collection[random];
		collection[random] = temp;
	}
  
	return collection;
}

function showToastNotification(message, timeout = 3000) {
	var $toastNotification = $('#toastNotification');

	$toastNotification.text(message);
	$toastNotification.addClass('show');
	setTimeout(() => $toastNotification.removeClass('show'), timeout);
}

function updateBodyColour(hex, gradient = true) {
	$('body').css(
		'background',
		(gradient) ? `linear-gradient(${hex} 0%, ${hex} 5%, rgb(24, 24, 24) 75%)` : hex
	);
	$('meta[name="theme-color"]').attr('content', hex);
}