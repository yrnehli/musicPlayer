<link rel="stylesheet" href="/public/css/control.css">

<div id="control" class="fixed-bottom d-flex">
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
		var $artistName = $('#artistName');
		var $prevButton = $('#prevButton');
		var $playButton = $('#playButton');
		var $skipButton = $('#skipButton');
		var $nowPlayingButton = $('#nowPlayingButton');
		var $volumeSlider = $('#volumeSlider');
		var $volumeButton = $('#volumeButton');
		var $control = $('#control');
		var $progressSlider = $('#progressSlider');
		var $elapsedTime = $('#elapsedTime');
		var $endTime = $('#endTime');
		var state = JSON.parse(localStorage.getItem("state")) || {};
		
		new MusicControl(
			{
				$prevButton: $prevButton,
				$playButton: $playButton,
				$skipButton: $skipButton,
				$progressSlider: $progressSlider,
				$songName: $songName,
				$artistName: $artistName,
				$albumArt: $albumArt,
				$volumeSlider: $volumeSlider,
				$elapsedTime: $elapsedTime,
				$endTime: $endTime,
				$nowPlayingButton: $nowPlayingButton
			},
			state
		);

		initStateInterval();
		initSliders();
		initEvents();

		function initStateInterval() {
			setInterval(() => {
				if (!MusicControl.sharedInstance.music().loaded() || MusicControl.sharedInstance.music().disabled()) {
					return;
				}

				localStorage.setItem(
					"state",
					JSON.stringify({
						volume: MusicControl.sharedInstance.music().volume(),
						queue: MusicControl.sharedInstance.music().queue(),
						nextUp: MusicControl.sharedInstance.music().nextUp(),
						seek: MusicControl.sharedInstance.music().seek(),
						songId: MusicControl.sharedInstance.music().songId()
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

				MusicControl.sharedInstance.music().volume(volume);
			}

			var progressIntervalCallback = function() {
				if (!MusicControl.sharedInstance.music().loaded() || MusicControl.sharedInstance.music().disabled()) {
					return;
				}

				var duration = MusicControl.sharedInstance.music().duration();
				var progress = MusicControl.sharedInstance.music().seek() / duration;
				var elapsedSeconds = progress * duration;

				$elapsedTime.text(secondsToTimeString(elapsedSeconds));
				$progressSlider.slider("value", progress * 100);
			};

			var progressInterval = setInterval(progressIntervalCallback, PROGRESS_INTERVAL_TIMEOUT);

			initSlider(
				$volumeSlider,
				Math.pow(
					(state.volume || state.volume === 0) ? state.volume : MusicControl.sharedInstance.music().volume(),
					1/4
				) * 100,
				{
					change: updateVolume,
					slide: updateVolume
				}
			);
			initSlider(
				$progressSlider,
				0,
				{
					slide: (e, ui) => $elapsedTime.text(secondsToTimeString(ui.value / 100 * MusicControl.sharedInstance.music().duration())),
					start: e => clearInterval(progressInterval),
					stop: (e, ui) => {
						MusicControl.sharedInstance.music().seek(ui.value / 100 * MusicControl.sharedInstance.music().duration());
						progressInterval = setInterval(progressIntervalCallback, PROGRESS_INTERVAL_TIMEOUT);
					}
				},
				MusicControl.sharedInstance.music().disabled()
			);
		}

		function initEvents() {
			var timeout;

			MusicControl.sharedInstance.music().on('disable', e => localStorage.clear());
			MusicControl.sharedInstance.on('updateauto', e => {
				if ($nowPlayingButton.hasClass('active')) {
					PartialManager.sharedInstance.loadPartial(`/album/${MusicControl.sharedInstance.albumId()}`);
				}
			});
			MusicControl.sharedInstance.on('updatemanual', e => {
				clearTimeout(timeout);

				if ($nowPlayingButton.hasClass('active')) {
					timeout = setTimeout(
						() => PartialManager.sharedInstance.loadPartial(`/album/${MusicControl.sharedInstance.albumId()}`),
						3000
					);
				}
			});
			$albumArt.click(e => PartialManager.sharedInstance.loadPartial('/'));
			$songName.click(e => PartialManager.sharedInstance.loadPartial(`/album/${MusicControl.sharedInstance.albumId()}`));
			$prevButton.click(e => MusicControl.sharedInstance.music().previous());
			$playButton.click(e => MusicControl.sharedInstance.music().togglePlay());	
			$skipButton.click(e => MusicControl.sharedInstance.music().skip());
			$nowPlayingButton.click(e => {
				$nowPlayingButton.toggleClass('active');

				if (MusicControl.sharedInstance.albumId()) {
					PartialManager.sharedInstance.loadPartial("/album/" + MusicControl.sharedInstance.albumId());
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
					MusicControl.sharedInstance.music().togglePlay();
				}
			}
		}

		function assignKeyup(e) {
			// Ctrl + S
			if (e.ctrlKey && e.keyCode === 83) {
				e.preventDefault();
				MusicControl.sharedInstance.music().playNextUp({
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