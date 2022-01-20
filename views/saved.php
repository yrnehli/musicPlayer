<link rel="stylesheet" href="/public/css/saved.css">

<div id="root" class="px-4 pb-4" data-simplebar>
	<?= $searchResults ?>
	<div class="d-flex pt-3">
		<h1 class="mx-3 my-0" id="saved">
			SAVED
		</h1>
	</div>
	<div class="d-flex my-3 mx-3">
		<button id="spotifyExportButton" class="btn-spotify mr-2">
			<span class="my-auto mr-2">
				<i class="fal fa-arrow-up"></i>
			</span>
			Spotify Export
		</button>
		<button id="clearButton" class="btn-spotify mr-2">
			<span class="my-auto mr-2">
				<i class="fal fa-times"></i>
			</span>
			Clear
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
		<?php foreach ($savedSongs as $savedSong): ?>
			<div class="tracklist-row" data-song-id="<?= $savedSong['id'] ?>" data-album-id="<?= $savedSong['albumId'] ?>" data-context-menu-actions="PLAY_NEXT,PLAY_LAST,GO_TO_ALBUM<?= ($savedSong['isFlagged']) ? ',UNFLAG' : ',FLAG' ?>" data-activable>
				<div class="track-number">
					<img class="equalizer" src="/public/img/equalizer.gif">
					<svg class="play">
						<path></path>
					</svg>
					<div class="text-center">
						<?= $savedSong['trackNumber'] ?>
					</div>
				</div>
				<div class="track-name">
					<div>
						<?= $savedSong['songName'] ?>
					</div>
					<div>
						<?= $savedSong['songArtist'] ?>
					</div>
				</div>
				<i class="<?= ($savedSong['isFlagged']) ? 'flag-icon active' : 'flag-icon' ?> fal fa-asterisk fa-xs"></i>
				<svg class="heart-button active" height="16" width="16">
					<path></path>
				</svg>
				<div class="total-time">
					<?= $savedSong['time'] ?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</div>

<script>
	(function() {
		var $tracklistRows = $('.tracklist-row');
		var $spotifyExportButton = $('#spotifyExportButton');
		var $clearButton = $('#clearButton');

		$(function() {
			updateBodyColour('#121212', false);
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
				var queue = [];
				var history = [];
				var positionFound = false;

				$tracklistRows.each(function(i) {
					if ($(this).is($self)) {
						positionFound = true;
					}

					var songId = $(this).data('song-id');

					if (positionFound) {
						queue.push(songId);
					} else {
						history.push(songId);
					}
				});
				
				Music.sharedInstance.queue(queue);
				Music.sharedInstance.skip();
				Music.sharedInstance.history(history);

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
					showToastNotification(true, "Added to Saved Songs");
					if (songId.toString() === Music.sharedInstance.songId().toString()) {
						MusicControl.sharedInstance.elements().$saveButton.addClass('active');
					}
				} else if (action === 'DELETE') {
					showToastNotification(true, "Removed from Saved Songs")
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

			$spotifyExportButton.click(async () => {
				$spotifyExportButton.prop('disabled', true);
				var res = await $.get('/saved/export');
				$spotifyExportButton.prop('disabled', false);
				showToastNotification(true, res.message);
			});

			$clearButton.click(() => {
				if (!confirm("Are you sure you want to clear all removed songs?")) {
					return;
				}

				$.get('/saved/clear');

				$tracklistRows.find('.heart-button').each(function() {
					$(this).removeClass('active');
					$(this).siblings('.flag-icon').removeClass('active');
				});
			});
		}
	})();
</script>
