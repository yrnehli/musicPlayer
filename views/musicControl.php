<div id="musicControl" class="fixed-bottom">
	<div id="songName"></div>
	<div id="playButton"></div>
	<div>
		<input type="range" id="volume">	
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
	var $musicControl = $('#musicControl');
	var musicPlayer = new MusicPlayer($musicControl, navigator.mediaSession.metadata);

	$playButton.click(() => musicPlayer.togglePlay());
	$volume.on('input', () => musicPlayer.volume($volume.val() / 100));

	window.addEventListener('keydown', e => {
		var key = e.which || e.keyCode;

		if (key === 32) {
			e.preventDefault();
			musicPlayer.togglePlay();
		}
	});
</script>