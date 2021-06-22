<link rel="stylesheet" href="/assets/css/album.css">

<div id="root" class="px-4 pb-4" data-simplebar>
	<?= $searchResults ?>
	<div class="d-flex pt-4">
		<img id="albumArtLarge" class="mx-3" src="<?= $album['artUrl'] ?>">		
		<div class="d-flex mx-2">
			<div class="mt-auto">
				<div id="album">ALBUM</div>
				<div id="albumName" style="visibility: hidden;">
					<?= $album['name'] ?>
				</div>
				<div class="album-details">
					<div id="albumArtist">
						<?= $album['artist'] ?>
					</div>
					<div class="dot"></div>
					<div>
						<?= $album['year'] ?>
					</div>
					<div class="dot"></div>
					<div>
						<?= $album['length'], " ", ($album['length'] > 1) ? "songs" : "song" ?>
					</div>
					<div class="dot"></div>
					<div>
						<?php
						
						$hours = floor($album['duration'] / 60 / 60);
						$minutes = floor(($album['duration'] / 60) - ($hours * 60));

						print ($hours > 0) ? "$hours hr $minutes min" : "$minutes min";

						?>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="d-flex my-3 mx-3">
		<button id="playAlbumButton" class="btn-spotify mr-1">
			<svg class="my-auto mr-2 play" height="16" width="16">
				<path></path>
			</svg>
			Play
		</button>
		<button id="shuffleAlbumButton" class="btn-spotify mx-1">
			<svg class="my-auto mr-2 shuffle" height="16" width="16">
				<path></path>
			</svg>
			Shuffle
		</button>
		<button id="queueAlbumButton" class="btn-spotify mx-1">
			<svg class="my-auto mr-2 queue" height="16" width="16">
				<path></path>
			</svg>
			Add to queue
		</button>
	</div>
	<div>
		<div id="tableHeader">
			<div>#</div>
			<div id="title">TITLE</div>
			<div id="timeIcon">
				<svg class="time" width="16" height="16" fill="currentColor">
					<path></path>
				</svg>
			</div>
		</div>
		<?php foreach ($songs as $song): ?>
			<div class="tracklist-row" data-song-id="<?= $song['id'] ?>" data-context-menu-actions="QUEUE">
				<div class="track-number">
					<img class="equalizer" src="/assets/img/equalizer.gif">
					<svg class="play">
						<path></path>
					</svg>
					<div class="text-center">
						<?= $song['trackNumber'] ?>
					</div>
				</div>
				<div class="track-name">
					<div>
						<?= $song['name'] ?>
					</div>
					<div>
						<?= $song['artist'] ?>
					</div>
				</div>
				<div class="total-time">
					<?= secondsToTimeString($song['duration']) ?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</div>

<script>
	(function() {
		var $playAlbumButton = $('#playAlbumButton');
		var $shuffleAlbumButton = $('#shuffleAlbumButton');
		var $queueAlbumButton = $('#queueAlbumButton');

		$(function() {
			updateBodyColour('<?= $colour ?>');
			scaleAlbumNameText();
			initTracklistRows();
			initEvents();
		});

		function initTracklistRows() {
			$('.tracklist-row.playing').removeClass('playing');

			if (MusicPlayer.sharedInstance.playing()) {
				$(`.tracklist-row[data-song-id="${MusicPlayer.sharedInstance.songId()}"]`).addClass('playing');
			}

			$(window).mousedown(function(e) {
				$('.tracklist-row.active').removeClass('active');

				var $tracklistRow = $(e.target).is('.tracklist-row') ? $(e.target) : $(e.target).parents('.tracklist-row').first();

				if ($tracklistRow) {
					$tracklistRow.addClass('active');
				}
			});

			$('.tracklist-row').dblclick(function() {
				var $self = $(this);
				var nextUp = { list: [], i: 0 };

				$('.tracklist-row').each(function(i) {
					nextUp.list.push($(this).data('song-id'));

					if ($(this).get(0) === $self.get(0)) {
						nextUp.i = i;
					}
				});
				
				MusicPlayer.sharedInstance.playNextUp(nextUp);

				$('.tracklist-row.active').removeClass('active');
				$self.addClass('active');
			});
		}

		function initEvents() {
			$(window).resize(() => scaleAlbumNameText());

			$playAlbumButton.click(() => {
				MusicPlayer.sharedInstance.playNextUp({
					list: $('.tracklist-row').get().map(tracklistRow => $(tracklistRow).data('song-id')),
					i: 0
				});
			});

			$shuffleAlbumButton.click(() => {
				MusicPlayer.sharedInstance.playNextUp({
					list: shuffle(
						$('.tracklist-row').get().map(tracklistRow => $(tracklistRow).data('song-id'))
					),
					i: 0
				});
			});

			$queueAlbumButton.click(() => {
				$('.tracklist-row').each(function() {
					MusicPlayer.sharedInstance.queue().push(
						$(this).data('song-id')
					);
					showToastNotification("Added to queue");
				});
			});
		}
		
		function scaleAlbumNameText() {
			var fontSize = 96;
			var $albumName = $('#albumName');
			var maxWidth = $(window).width() - 312;

			$albumName.css({
				"font-size": `${fontSize}px`,
				"line-height": `${fontSize}px`,
				"white-space": "nowrap",
			});

			if ($albumName.width() > maxWidth) {
				fontSize = Math.max(
					Math.floor(fontSize / ($albumName.width() / maxWidth)),
					48
				);
			}

			$albumName.css({
				"font-size": `${fontSize}px`,
				"line-height": `${fontSize}px`,
				"white-space": "normal",
				"visibility": "visible"
			});
		}
	})();
</script>