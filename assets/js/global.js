if ('serviceWorker' in navigator) {
	navigator.serviceWorker.register('/assets/js/sw.js');
}

var partialManager;

$(function() {
	partialManager = new PartialHandler($('#partial'));
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

function getTimeString(totalSeconds) {
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