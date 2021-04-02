function initSlider($slider, change = () => {}, slide = () => {}) {
	$slider.slider({
		min: 0,
		max: 100,
		value: 0,
		range: "min",
		step: 0.1,
		change: change,
		slide: slide
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

	change(null, { value: $slider.slider("value") });
}