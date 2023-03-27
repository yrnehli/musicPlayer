if ('serviceWorker' in navigator) {
	navigator.serviceWorker.register('/service-worker.js');
	navigator.serviceWorker.addEventListener('message', e => {
		switch (e.data) {
			case 'previous-action':
				Music.sharedInstance.previous(true);
				break;
			default:
				Music.sharedInstance.skip();
				break;
		}
	});
}

if (window.Notification) {
	Notification.requestPermission();
}

$(function() {
	var $partial = $('#partial');
	var $contextMenu = $('#contextMenu');
	var $searchBar = $('#searchBar');
	var $searchResults = $('#searchResults');
	var $clearSearchBarButton = $('#clearSearchBarButton');
	var $shuffleAllButton = $('#shuffleAllButton');
	
	new SearchHandler($searchBar, $searchResults, $clearSearchBarButton);
	new PartialManager($partial, '.simplebar-content-wrapper');
	new CustomContextMenu(
		$contextMenu,
		$partial,
		{
			PLAY_NEXT: {
				html: `
					<i class="fal fa-arrow-to-right fa-fw mr-1"></i>
					Play Next
				`,
				callback: function($target, $targets) {
					$targets.get().reverse().forEach(async function(target) {
						var $target = $(target);

						if ($target.data('song-id')) {
							Music.sharedInstance.queue().unshift(
								$(target).data('song-id')
							);
						} else if ($target.data('album-id')) {
							var res = await $.get(`/api/album/${$target.data('album-id')}`);
							res
								.data
								.songIds
								.reverse()
								.forEach(songId => Music.sharedInstance.queue().unshift(songId))
							;
						}

						if ($target.hasClass('result-row')) {
							SearchHandler.sharedInstance.reset();
						}
					});

					if (Music.sharedInstance.disabled()) {
						Music.sharedInstance.skip();
					}
					
					showToastNotification(true, "Playing Next");					
				}
			},
			PLAY_LAST: {
				html: `
					<i class="fal fa-arrow-to-bottom fa-fw mr-1"></i>
					Play Last
				`,
				callback: function($target, $targets) {
					$targets.each(async function() {
						var $target = $(this);

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

						if ($target.hasClass('result-row')) {
							SearchHandler.sharedInstance.reset();
						}
					});

					if (Music.sharedInstance.disabled()) {
						Music.sharedInstance.skip();
					}
					
					showToastNotification(true, "Playing Last");				
				}
			},
			GO_TO_ALBUM: {
				html: `
					<i class="fal fa-record-vinyl fa-fw mr-1"></i>
					Go to Album
				`,
				callback: function($target) {
					PartialManager.sharedInstance.loadPartial(`/album/${$target.data('album-id')}`);

					if ($target.hasClass('result-row')) {
						SearchHandler.sharedInstance.reset();
					}
				}
			},
			REMOVE_FROM_QUEUE: {
				html: `
					<i class="fal fa-times fa-fw mr-1"></i>
					Remove
				`,
				callback: function($target, $targets) {
					var position;

					$targets.each(function() {
						var $target = $(this);

						$target.parent().children().each(function(i) {
							if ($(this).is($target)) {
								position = i;
							}
						});
	
						if (position >= 0) {
							Music.sharedInstance.queue().splice(position, 1);
							$target.remove();
						}
					});
				}
			},
			FLAG: {
				html: `
					<i class="fal fa-flag fa-fw mr-1"></i>
					Flag
				`,
				callback: function($target, $targets) {
					$targets.each(async function() {
						var $target = $(this);
						var songId = $target.data('song-id');
						
						await $.ajax({
							type: 'PUT',
							url: `/api/saved/${songId}?flagged=true`
						});
		
						$target.find('.heart-button').addClass('active');
						$target.find('.flag-icon').addClass('active');
						
						if (songId.toString() === Music.sharedInstance.songId()?.toString()) {
							MusicControl.sharedInstance.elements().$saveButton.addClass('active');
						}
		
						$target.data(
							CustomContextMenu.CONTEXT_MENU_ACTIONS_DATA_SUFFIX,
							$target.data(CustomContextMenu.CONTEXT_MENU_ACTIONS_DATA_SUFFIX).replace("FLAG", "UNFLAG")
						);

						PartialManager.sharedInstance.updateCurrentState();
					});

					showToastNotification(true, "Marked as Flagged");
				}
			},
			UNFLAG: {
				html: `
					<i class="fal fa-flag fa-fw mr-1"></i>
					Unflag
				`,
				callback: function($target, $targets) {
					$targets.each(async function() {
						var $target = $(this);
						var songId = $target.data('song-id');
						
						await $.ajax({
							type: 'PUT',
							url: `/api/saved/${songId}?flagged=false`
						});
	
						$target.find('.flag-icon').removeClass('active');
						$target.data(
							CustomContextMenu.CONTEXT_MENU_ACTIONS_DATA_SUFFIX,
							$target.data(CustomContextMenu.CONTEXT_MENU_ACTIONS_DATA_SUFFIX).replace("UNFLAG", "FLAG")
						);
					});

					showToastNotification(true, "Unmarked as Flagged");
				}
			}
		}
	);
	
	if (window.location.pathname !== "/") {
		$shuffleAllButton.hide();
	}

	PartialManager.sharedInstance.on('pathchange', e => {
		if (window.location.pathname === "/") {
			$shuffleAllButton.show();
		} else {
			$shuffleAllButton.hide();
		}
		
		if (window.location.pathname !== `/album/${MusicControl.sharedInstance.albumId()}`) {
			if (![`/lyrics/${Music.sharedInstance.songId()}`, `/queue`].includes(window.location.pathname)) {
				MusicControl.sharedInstance.elements().$nowPlayingButton.removeClass('active');
				MusicControl.sharedInstance.elements().$lyricsButton.removeClass('active');
			}
		}
	});

	$shuffleAllButton.click(() => {
		Music.sharedInstance.history([]);
		Music.sharedInstance.queue(
			shuffle(MusicControl.sharedInstance.songIds())
		);
		Music.sharedInstance.skip(true);
	});

	$(window).keydown(e => {
		if (e.code === 'ShiftLeft' || e.code === 'ShiftRight') {
			this._shiftDown = true;
		}
	});

	$(window).keyup(e => {
		if (e.code === 'ShiftLeft' || e.code === 'ShiftRight') {
			this._shiftDown = false;
		}
	});

	$(window).mousedown(e => {
		var $element = $(e.target).is(`[data-${SearchHandler.ACTIVABLE_DATA_SUFFIX}]`)
			? $(e.target)
			: $(e.target).parents(`[data-${SearchHandler.ACTIVABLE_DATA_SUFFIX}]`).first()
		;

		var resetActivables = () => {
			$(`[data-${SearchHandler.ACTIVABLE_DATA_SUFFIX}]`)
				.removeClass('active')
				.css({
					'border-bottom-left-radius': '',
					'border-bottom-right-radius': '',
					'border-top-left-radius': '',
					'border-top-right-radius': '',
					'border-radius': ''
				})
			;
		}

		if (!this._shiftDown && e.which === 1 || (!$element.hasClass('active')) && e.which === 3) {
			this._$firstActiveElement = null;
			resetActivables();
		}

		if ($element.length) {
			if (!this._$firstActiveElement) {
				this._$firstActiveElement = $element;
			}

			if (this._shiftDown && this._$firstActiveElement) {
				var $firstActiveElement = this._$firstActiveElement;
				var diff = $element.index() > $firstActiveElement.index();
				var $elements;

				resetActivables();

				if (diff === 0) {
					return;
				}
				
				$elements = (diff > 0)
					? $element.prevUntil($firstActiveElement).addBack().add($firstActiveElement)
					: $elements = $element.nextUntil($firstActiveElement).addBack().add($firstActiveElement);
				;

				$elements.each(function(i) {
					switch (i) {
						case 0:
							$(this).css({
								'border-bottom-left-radius': '0px',
								'border-bottom-right-radius': '0px'
							});
							break;
						case $elements.length - 1:
							$(this).css({
								'border-top-left-radius': '0px',
								'border-top-right-radius': '0px'
							});
							break;
						default:
							$(this).css('border-radius', '0px');
							break;
					}

					$(this).addClass('active');
				});
			}
			
			$element.addClass('active');
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
