function updateSliderStyling(cssSelector, progress) {
	const incompleteColour = '#535353';
	const completeColour = '#b3b3b3';
		
	document.styleSheets[0].addRule(
		`${cssSelector}::-webkit-slider-runnable-track`,
		`background: linear-gradient(to right, ${completeColour} 0%, ${completeColour} ${progress}%, ${incompleteColour} ${progress}%, ${incompleteColour} 100%)`
	);
}