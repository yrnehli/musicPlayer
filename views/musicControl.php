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
					<svg class="previous" height="16" width="16">
						<path></path>
					</svg>
				</button>
				<button id="playButton" class="my-auto mx-2 paused">
					<svg class="play" height="16" width="16">
						<path></path>
					</svg>
				</button>
				<button id="skipButton" class="my-auto mx-2">
					<svg class="skip" height="16" width="16">
						<path></path>
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
		<svg id="nowPlayingButton" class="my-auto mx-2" height="16" width="16">
			<path></path>
			<path></path>
		</svg>
		<svg id="volumeButton" class="my-auto mx-2 mute" height="16" width="16">
			<path></path>
		</svg>
		<div class="d-flex h-100">
			<div id="volumeSlider" class="my-auto"></div>
		</div>
	</div>
</div>

<script>
	(function() {
		var $albumArt = $('#albumArt');
		var $songName = $('#songName');
		var $prevButton = $('#prevButton');
		var $playButton = $('#playButton');
		var $skipButton = $('#skipButton');
		var $nowPlayingButton = $('#nowPlayingButton');
		var $volumeSlider = $('#volumeSlider');
		var $volumeButton = $('#volumeButton');
		var $musicControl = $('#musicControl');
		var $progressSlider = $('#progressSlider');
		var $elapsedTime = $('#elapsedTime');
		var $endTime = $('#endTime');
		
		new MusicPlayer($musicControl);

		initStateInterval();
		initSliders();
		initEvents();

		function initStateInterval() {
			setInterval(() => {
				if (!MusicPlayer.sharedInstance.loaded() || MusicPlayer.sharedInstance.disabled()) {
					return;
				}

				localStorage.setItem(
					"state",
					JSON.stringify({
						volume: MusicPlayer.sharedInstance.volume(),
						queue: MusicPlayer.sharedInstance.queue(),
						nextUp: MusicPlayer.sharedInstance.nextUp(),
						seek: MusicPlayer.sharedInstance.seek(),
						songId: MusicPlayer.sharedInstance.songId()
					})
				);
			}, 1000);
		}

		function initSliders() {
			const PROGRESS_INTERVAL_TIMEOUT = 100;

			var updateVolume = function(e, ui) {
				var volume = Math.pow(ui.value / 100, 4);

				$volumeButton.removeClass('mute low-volume medium-volume high-volume');
			
				if (ui.value === 0) {
					$volumeButton.addClass('mute');
				} else if (ui.value <= 33) {
					$volumeButton.addClass('low-volume');
				} else if (ui.value <= 66) {
					$volumeButton.addClass('medium-volume');
				} else {
					$volumeButton.addClass('high-volume');
				}

				MusicPlayer.sharedInstance.volume(volume);
			}

			var progressIntervalCallback = function() {
				if (!MusicPlayer.sharedInstance.loaded() || MusicPlayer.sharedInstance.disabled()) {
					return;
				}

				var duration = MusicPlayer.sharedInstance.duration();
				var progress = MusicPlayer.sharedInstance.seek() / duration;
				var elapsedSeconds = progress * duration;

				$elapsedTime.text(secondsToTimeString(elapsedSeconds));
				$progressSlider.slider("value", progress * 100);
			};

			var progressInterval = setInterval(progressIntervalCallback, PROGRESS_INTERVAL_TIMEOUT);

			initSlider($volumeSlider, Math.pow(MusicPlayer.sharedInstance.volume(), 1/4) * 100, { change: updateVolume, slide: updateVolume });
			initSlider(
				$progressSlider,
				0,
				{
					slide: (e, ui) => $elapsedTime.text(secondsToTimeString(ui.value / 100 * MusicPlayer.sharedInstance.duration())),
					start: e => clearInterval(progressInterval),
					stop: (e, ui) => {
						MusicPlayer.sharedInstance.seek(ui.value / 100 * MusicPlayer.sharedInstance.duration());
						progressInterval = setInterval(progressIntervalCallback, PROGRESS_INTERVAL_TIMEOUT);
					}
				},
				MusicPlayer.sharedInstance.disabled()
			);
		}

		function initEvents() {
			$albumArt.click(e => PartialManager.sharedInstance.loadPartial('/'));
			$songName.click(e => PartialManager.sharedInstance.loadPartial(`/album/${MusicPlayer.sharedInstance.albumId()}`));
			$prevButton.click(e => MusicPlayer.sharedInstance.previous());
			$playButton.click(e => MusicPlayer.sharedInstance.togglePlay());
			$skipButton.click(e => MusicPlayer.sharedInstance.skip());
			$nowPlayingButton.click(e => {
				$nowPlayingButton.toggleClass('active');

				if (MusicPlayer.sharedInstance.albumId()) {
					PartialManager.sharedInstance.loadPartial("/album/" + MusicPlayer.sharedInstance.albumId(), false);
				}
			});
			$volumeButton.click(e => updateVolumeButton());
			$volumeSlider.parent().on('mousewheel', e => adjustVolume(e));
			$(window).keydown(e => assignKeydown(e));
			$(window).keyup(e => assignKeyup(e));
		}

		function adjustVolume(e) {
			var volume = $volumeSlider.slider("value");

			$volumeSlider.slider(
				"value",
				(e.originalEvent.wheelDelta > 0) ? volume + 10 : volume - 10
			);
		}

		function updateVolumeButton() {
			var volume = $volumeSlider.slider("value");

			if (volume === 0) {
				$volumeSlider.slider("value", $volumeSlider.data("volume") || 10);
				return;
			}

			$volumeSlider.data("volume", volume);
			$volumeSlider.slider("value", 0);
		}

		function assignKeydown(e) {
			// Ctrl + S
			if (e.ctrlKey && e.keyCode === 83) {
				e.preventDefault();
			}

			// Esc
			if (e.keyCode === 27) {
				e.preventDefault();
			}

			// Space
			if (e.keyCode === 32) {
				if ($(':focus').length === 0) {
					e.preventDefault();
					MusicPlayer.sharedInstance.togglePlay();
				}
			}
		}

		function assignKeyup(e) {
			// Ctrl + S
			if (e.ctrlKey && e.keyCode === 83) {
				e.preventDefault();
				MusicPlayer.sharedInstance.playNextUp({
					list: shuffle([<?= implode(", ", $songIds) ?>]),
					i: 0
				});
			}

			// Esc
			if (e.keyCode === 27) {
				e.preventDefault();
				PartialManager.sharedInstance.loadPartial('/');
			}
		}
	})();
</script>