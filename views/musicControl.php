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
		<div id="playButton" class="my-auto"></div>
	</div>
	<div class="h-100 d-flex ml-auto">
		<svg id="volumeButton" class="my-auto mr-2 mute" role="presentation" height="16" width="16">
			<path></path>
		</svg>
		<input id="volume" type="range">
	</div>
</div>

<script>
	navigator.mediaSession.metadata = new MediaMetadata();
	navigator.mediaSession.setActionHandler('play', () => musicPlayer.togglePlay());
	navigator.mediaSession.setActionHandler('pause', () => musicPlayer.togglePlay());
	navigator.mediaSession.setActionHandler('previoustrack', () => musicPlayer.previous());
	navigator.mediaSession.setActionHandler('nexttrack', () => musicPlayer.skip());

	var $playButton = $('#playButton');
	var $volume = $('#volume');
	var $volumeButton = $('#volumeButton');
	var $musicControl = $('#musicControl');
	var musicPlayer = new MusicPlayer($musicControl, navigator.mediaSession.metadata);

	updateSliderStyling("#volume", $volume.val());

	$volume.on('input', () => {
		var volume = parseInt($volume.val());

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
		updateSliderStyling("#volume", $volume.val());
	});

	$volumeButton.click(() => {
		$volume.val(0);
		$volume.trigger('input');
	});

	$playButton.click(() => musicPlayer.togglePlay());

	window.addEventListener('keydown', e => {
		var key = e.which || e.keyCode;

		if (key === 32) {
			e.preventDefault();
			musicPlayer.togglePlay();
		}
	});
</script>