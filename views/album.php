<link rel="stylesheet" href="/public/css/album.css">

<div id="root" class="px-4 pb-4" data-simplebar>
	<?= $searchResults ?>
	<div class="d-flex pt-3">
		<img id="albumArtLarge" class="mx-3" src="<?= $album['artUrl'] ?>">		
		<div class="d-flex mx-2">
			<div class="mt-auto">
				<div id="album">ALBUM</div>
				<h1 id="albumName" style="visibility: hidden;">
					<?= $album['name'] ?>
				</h1>
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
			<svg class="my-auto mr-2 plus" height="16" width="16">
				<path></path>
			</svg>
			Add to queue
		</button>
	</div>
	<div>
		<div class="table-header">
			<div>#</div>
			<div id="title">TITLE</div>
			<div id="timeIcon">
				<svg class="time" width="16" height="16" fill="currentColor">
					<path></path>
				</svg>
			</div>
		</div>
		<?php foreach ($songs as $song): ?>
			<div class="tracklist-row" data-song-id="<?= $song['id'] ?>" data-context-menu-actions="<?= ($song['isDeezer']) ? (($song['isFlagged']) ? 'QUEUE,UNFLAG' : 'QUEUE,FLAG') : 'QUEUE' ?>" data-activable>
				<div class="track-number">
					<img class="equalizer" src="/public/img/equalizer.gif">
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
				<?php if ($song['isDeezer']): ?>
					<i class="<?= ($song['isFlagged']) ? 'flag-icon active' : 'flag-icon' ?> fal fa-asterisk fa-xs"></i>
					<svg class="<?= ($song['isSaved']) ? 'heart-button active' : 'heart-button' ?>" height="16" width="16">
						<path></path>
					</svg>
				<?php endif; ?>
				<div class="total-time">
					<?= $song['time'] ?>
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
		var $tracklistRows = $('.tracklist-row');

		$(function() {
			updateBodyColour('<?= $accentColour ?>');
			scaleAlbumNameText();
			initTracklistRows();
			initEvents();
		});

		function initTracklistRows() {
			$tracklistRows.removeClass('playing');

			if (Music.sharedInstance.playing()) {
				$tracklistRows.filter(`[data-song-id="${Music.sharedInstance.songId()}"]`).addClass('playing');
			}

			$tracklistRows.dblclick(function() {
				var $self = $(this);
				var nextUp = { list: [], i: 0 };

				$tracklistRows.each(function(i) {
					nextUp.list.push($(this).data('song-id'));

					if ($(this).is($self)) {
						nextUp.i = i;
					}
				});
				
				Music.sharedInstance.playNextUp(nextUp);

				$tracklistRows.removeClass('active');
				$self.addClass('active');
			});

			$tracklistRows.find('.heart-button').click(async function() {
				var songId = $(this).parents('[data-song-id]').data('song-id');
				var action = $(this).hasClass('active') ? 'DELETE' : 'PUT';

				var res = await $.ajax({
					type: action,
					url: `/api/saved/${songId}`
				});

				var $parent = $(this).parents(`[data-${CustomContextMenu.CONTEXT_MENU_ACTIONS_DATA_SUFFIX}]`);

				$parent.data(
					CustomContextMenu.CONTEXT_MENU_ACTIONS_DATA_SUFFIX,
					$parent.data(CustomContextMenu.CONTEXT_MENU_ACTIONS_DATA_SUFFIX).replace('UNFLAG', 'FLAG')
				);

				$(this).toggleClass('active');
				$(this).siblings('.flag-icon').removeClass('active');
					
				if (action === 'PUT') {
					showToastNotification(true, "Added to saved songs");
					if (songId.toString() === Music.sharedInstance.songId().toString()) {
						MusicControl.sharedInstance.elements().$saveButton.addClass('active');
					}
				} else if (action === 'DELETE') {
					showToastNotification(true, "Removed from saved songs")
					if (songId.toString() === Music.sharedInstance.songId().toString()) {
						MusicControl.sharedInstance.elements().$saveButton.removeClass('active');
					}
				}
			});
		}

		function initEvents() {
			Music.sharedInstance.off('pause.album').on('pause.album', () => $tracklistRows.removeClass('playing'));
			Music.sharedInstance.off('play.album').on('play.album', () => {
				$tracklistRows.removeClass('playing');
				$tracklistRows.filter(`[data-song-id="${Music.sharedInstance.songId()}"]`).addClass('playing');
			});

			$playAlbumButton.click(() => {
				Music.sharedInstance.playNextUp({
					list: $('.tracklist-row').get().map(tracklistRow => $(tracklistRow).data('song-id')),
					i: 0
				});
			});

			$shuffleAlbumButton.click(() => {
				Music.sharedInstance.playNextUp({
					list: shuffle(
						$('.tracklist-row').get().map(tracklistRow => $(tracklistRow).data('song-id'))
					),
					i: 0
				});
			});

			$queueAlbumButton.click(() => {
				$('.tracklist-row').each(function() {
					Music.sharedInstance.queue().push(
						$(this).data('song-id')
					);

					if (Music.sharedInstance.disabled()) {
						Music.sharedInstance.enable();
						Music.sharedInstance.skip();
					}

					showToastNotification(true, "Added to queue");
				});
			});

			$(window).resize(() => scaleAlbumNameText());
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