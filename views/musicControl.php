<link rel="stylesheet" href="/assets/css/musicControl.css">

<div id="musicControl" class="fixed-bottom d-flex">
	<div class="h-100 d-flex">
		<img id="albumArt" class="my-auto"></img>
		<div class="my-auto">
			<div id="songName"></div>
			<div id="artistName"></div>
		</div>
	</div>
	<div id="playerControls" class="h-100 mx-auto d-flex">
		<button id="prevButton" class="my-auto mx-2">
			<svg height="16" width="16">
				<path d="M13 2.5L5 7.119V3H3v10h2V8.881l8 4.619z"></path>
			</svg>
		</button>
		<button id="playButton" class="my-auto mx-2 paused">
			<svg height="16" width="16">
				<path></path>
			</svg>
		</button>
		<button id="skipButton" class="my-auto mx-2">
			<svg height="16" width="16">
				<path d="M11 3v4.119L3 2.5v11l8-4.619V13h2V3z"></path>
			</svg>
		</button>
	</div>
	<div class="h-100 d-flex ml-auto">
		<svg id="volumeButton" class="my-auto mr-2 mute" role="presentation" height="16" width="16">
			<path></path>
		</svg>
		<div class="my-auto d-flex">
			<input id="volumeSlider" class="my-auto" type="range">
		</div>
	</div>
</div>

<script>
	navigator.mediaSession.metadata = new MediaMetadata();
	navigator.mediaSession.setActionHandler('play', () => musicPlayer.togglePlay());
	navigator.mediaSession.setActionHandler('pause', () => musicPlayer.togglePlay());
	navigator.mediaSession.setActionHandler('previoustrack', () => musicPlayer.previous());
	navigator.mediaSession.setActionHandler('nexttrack', () => musicPlayer.skip());

	var $prevButton = $('#prevButton');
	var $playButton = $('#playButton');
	var $skipButton = $('#skipButton');
	var $volumeSlider = $('#volumeSlider');
	var $volumeButton = $('#volumeButton');
	var $musicControl = $('#musicControl');
	var musicPlayer = new MusicPlayer($musicControl, navigator.mediaSession.metadata);

	updateVolumeSlider();
	updateVolumeButton();

	$prevButton.click(() => musicPlayer.previous());
	$playButton.click(() => musicPlayer.togglePlay());
	$skipButton.click(() => musicPlayer.skip());
	$volumeSlider.on('input', () => updateVolumeSlider());
	$volumeButton.click(() => updateVolumeButton());
	window.addEventListener('keydown', e => assignHotkeys(e));

	function updateVolumeSlider() {
		var volume = parseInt($volumeSlider.val());

		$volumeButton.removeClass('mute low-volume medium-volume high-volume');
	
		if (volume === 0) {
			$volumeButton.addClass('mute');
		} else if (volume <= 33) {
			$volumeButton.addClass('low-volume');
		} else if (volume <= 66) {
			$volumeButton.addClass('medium-volume');
		} else {
			$volumeButton.addClass('high-volume');
		}

		musicPlayer.volume(volume / 100);
		updateSliderStyling("#volumeSlider", $volumeSlider.val());
	}

	function updateVolumeButton() {
		var muted = (parseInt($volumeSlider.val()) === 0);

		if (muted) {
			$volumeSlider.val($volumeSlider.data('volume') || 50);
		} else {
			$volumeSlider.data('volume', $volumeSlider.val());
			$volumeSlider.val(0);
		}

		updateVolumeSlider();
	}

	function assignHotkeys(e) {
		var key = e.which || e.keyCode;

		if (key === 32) {
			e.preventDefault();
			musicPlayer.togglePlay();
		}
	}
</script>