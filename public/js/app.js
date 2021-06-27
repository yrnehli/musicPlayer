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
						Music.sharedInstance.queue().push(
							$target.data('song-id')
						);
					} else if ($target.data('album-id')) {
						var res = await $.get(`/api/album/${$target.data('album-id')}`);
						res
							.data
							.songIds
							.forEach(songId => Music.sharedInstance.queue().push(songId))
						;
					}
					
					showToastNotification(true, "Added to queue");					
				}
			},
			GO_TO_ALBUM: {
				text: "Go to album",
				callback: function($target) {
					PartialManager.sharedInstance.loadPartial(`/album/${$target.data('album-id')}`);
				}
			},
			REMOVE_FROM_QUEUE: {
				text: "Remove from queue",
				callback: function($target) {
					var index;

					$target.parent().children().each(function(i) {
						if ($(this).is($target)) {
							index = i;
						}
					});

					if (index >= 0) {
						Music.sharedInstance.queue().splice(index, 1);
						$target.remove();
					}
				}
			},
			FLAG: {
				text: "Flag",
				callback: async function($target) {
					var songId = $target.data('song-id');
					
					await $.ajax({
						type: 'PUT',
						url: `/api/saved/${songId}?flagged=true`
					});
	
					$target.find('.heart-button').addClass('active');
					$target.find('.flag-icon').addClass('active');
					
					if (songId.toString() === Music.sharedInstance.songId().toString()) {
						MusicControl.sharedInstance.elements().$saveButton.addClass('active');
					}

					showToastNotification(true, "Marked as flagged");

					$target.data(
						CustomContextMenu.CONTEXT_MENU_ACTIONS_DATA_SUFFIX,
						$target.data(CustomContextMenu.CONTEXT_MENU_ACTIONS_DATA_SUFFIX).replace("FLAG", "UNFLAG")
					);
				}
			},
			UNFLAG: {
				text: "Unflag",
				callback: async function($target) {
					var songId = $target.data('song-id');
					
					await $.ajax({
						type: 'PUT',
						url: `/api/saved/${songId}?flagged=false`
					});

					$target.find('.flag-icon').removeClass('active');

					showToastNotification(true, "Unmarked as flagged");

					$target.data(
						CustomContextMenu.CONTEXT_MENU_ACTIONS_DATA_SUFFIX,
						$target.data(CustomContextMenu.CONTEXT_MENU_ACTIONS_DATA_SUFFIX).replace("UNFLAG", "FLAG")
					);
				}
			}
		}
	);

		
	PartialManager.sharedInstance.on('preupdate', e =>  SearchHandler.sharedInstance.reset());
	PartialManager.sharedInstance.on('pathchange', e => {
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

function showToastNotification(success, message, timeoutDuration = 3000) {
	var $toastNotification = $('#toastNotification');
	var now = new Date();
	var epoch = Math.round(now.getTime() / 1000);

	$toastNotification
		.text(message)
		.removeClass(success ? 'fail' : 'success')
		.addClass(success ? 'success' : 'fail')
		.css('margin-left', `${$toastNotification.outerWidth() / 2 * -1}px`)
		.addClass('show')
		.data('fadeTime', epoch + 3)
	;

	setTimeout(() => {
		var now = new Date();
		var epoch = Math.round(now.getTime() / 1000);	

		if (epoch >= $toastNotification.data('fadeTime')) {
			$toastNotification.removeClass('show');
		}
	}, timeoutDuration);
}

function updateBodyColour(hex, gradient = true) {
	$('meta[name="theme-color"]').attr('content', hex);
	$('body').css(
		'background',
		(gradient) ? `linear-gradient(${hex} 0%, ${hex} 5%, #181818 75%)` : hex
	);
}