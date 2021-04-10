<link rel="stylesheet" href="/assets/css/musicControl.css">

<div id="musicControl" class="fixed-bottom d-flex">
	<div id="playerDetails" class="h-100 d-flex">
		<img id="albumArt" class="my-auto"></img>
		<div class="my-auto w-75">
			<div id="songName"></div>
			<div id="artistName"></div>
		</div>
	</div>
	<div id="playerControls" class="h-100">
		<div class="h-75 d-flex">
			<div class="d-flex mx-auto">
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
		</div>
		<div class="h-25 d-flex mx-auto">
			<div class="mb-auto d-flex">
				<div id="elapsedTime" class="time">0:00</div>
				<div id="progressSlider"></div>
				<div id="endTime" class="time">0:00</div>
			</div>
		</div>
	</div>
	<div class="h-100 d-flex ml-auto">
		<svg id="volumeButton" class="my-auto mr-2 mute" role="presentation" height="16" width="16">
			<path></path>
		</svg>
		<div class="d-flex h-100">
			<div id="volumeSlider" class="my-auto"></div>
		</div>
	</div>
</div>

<script>
	(function() {
		navigator.mediaSession.metadata = new MediaMetadata();
		navigator.mediaSession.setActionHandler('play', () => musicControl.togglePlay());
		navigator.mediaSession.setActionHandler('pause', () => musicControl.togglePlay());
		navigator.mediaSession.setActionHandler('previoustrack', () => musicControl.previous());
		navigator.mediaSession.setActionHandler('nexttrack', () => musicControl.skip());

		var $songName = $('#songName');
		var $prevButton = $('#prevButton');
		var $playButton = $('#playButton');
		var $skipButton = $('#skipButton');
		var $volumeSlider = $('#volumeSlider');
		var $volumeButton = $('#volumeButton');
		var $musicControl = $('#musicControl');
		var $progressSlider = $('#progressSlider');
		var $elapsedTime = $('#elapsedTime');
		var $endTime = $('#endTime');
		musicControl = new MusicControl($musicControl, navigator.mediaSession.metadata);

		initStateInterval();
		initSliders();
		initEvents();

		function initStateInterval() {
			setInterval(() => {
				if (!musicControl.loaded() || musicControl.disabled()) {
					return;
				}

				localStorage.setItem(
					"state",
					JSON.stringify({
						volume: musicControl.volume(),
						queue: musicControl.queue(),
						nextUp: musicControl.nextUp(),
						seek: musicControl.seek(),
						songId: musicControl.songId()
					})
				);
			}, 1000);
		}

		function initSliders() {
			const PROGRESS_INTERVAL_TIMEOUT = 100;

			var updateVolume = function(e, ui) {
				var volume = ui.value / 100;

				$volumeButton.removeClass('mute low-volume medium-volume high-volume');
			
				if (volume === 0) {
					$volumeButton.addClass('mute');
				} else if (volume <= 0.33) {
					$volumeButton.addClass('low-volume');
				} else if (volume <= 0.66) {
					$volumeButton.addClass('medium-volume');
				} else {
					$volumeButton.addClass('high-volume');
				}

				musicControl.volume(volume);
			}

			var progressIntervalCallback = function() {
				if (!musicControl.loaded() || musicControl.disabled()) {
					return;
				}

				var duration = musicControl.duration();
				var progress = musicControl.seek() / duration;
				var elapsedSeconds = progress * duration;

				$elapsedTime.text(getTimeString(elapsedSeconds));
				$progressSlider.slider("value", progress * 100);
			};

			var progressInterval = setInterval(progressIntervalCallback, PROGRESS_INTERVAL_TIMEOUT);

			initSlider($volumeSlider, musicControl.volume() * 100, { change: updateVolume, slide: updateVolume });
			initSlider(
				$progressSlider,
				0,
				{
					slide: (e, ui) => $elapsedTime.text(getTimeString(ui.value / 100 * musicControl.duration())),
					start: e => clearInterval(progressInterval),
					stop: (e, ui) => {
						musicControl.seek(ui.value / 100 * musicControl.duration());
						progressInterval = setInterval(progressIntervalCallback, PROGRESS_INTERVAL_TIMEOUT);
					}
				},
				musicControl.disabled()
			);
		}

		function initEvents() {
			$songName.click(e => partialManager.loadPartial(`/album/${$songName.data('albumId')}`));
			$prevButton.click(e => musicControl.previous());
			$playButton.click(e => musicControl.togglePlay());
			$skipButton.click(e => musicControl.skip());
			$volumeButton.click(e => updateVolumeButton());
			$(window).keydown(e => assignKeydown(e));
			$(window).keyup(e => assignKeyup(e));

			$volumeSlider.parent().on('mousewheel', function(e) {
				var volume = $volumeSlider.slider("value");

				$volumeSlider.slider(
					"value",
					(e.originalEvent.wheelDelta > 0) ? volume + 10 : volume - 10
				);
			});
		}

		function updateVolumeButton() {
			var volume = $volumeSlider.slider("value");

			if (volume !== 0) {
				$volumeSlider.data("volume", volume);
				$volumeSlider.slider("value", 0);
			} else {
				$volumeSlider.slider("value", $volumeSlider.data("volume") || 10);		
			}
		}

		function assignKeydown(e) {
			// Ctrl + S
			if (e.ctrlKey && e.keyCode === 83) {
				e.preventDefault();
			}

			// Space
			if (e.keyCode === 32) {
				e.preventDefault();
				musicControl.togglePlay();
			}
		}

		function assignKeyup(e) {
			// Ctrl + S
			if (e.ctrlKey && e.keyCode === 83) {
				e.preventDefault();

				musicControl.playNextUp({
					list: shuffle([<?= implode(", ", $songIds) ?>]),
					i: 0
				});
			}
		}
	})();
</script>