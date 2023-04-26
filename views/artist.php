<link rel="stylesheet" href="/public/css/artist.css">
<div id="root" class="px-4 pb-4" data-simplebar>
	<div class="d-flex pt-3 pb-4">
		<?php if (isset($art)): ?>
			<img id="art" class="mx-3" src="<?= $art ?>">		
		<?php endif; ?>
		<div class="d-flex row mx-1 mt-auto">
			<div class="col-12 mb-2">
				<div id="artist">
					ARTIST
				</div>
				<h1 id="artistNameBig" style="visibility: hidden;">
					<?= $artistName ?>
				</h1>
			</div>
			<div class="col-12">
				<div class="d-flex">
					<button id="playArtistButtom" class="btn-spotify mr-1">
						<svg class="my-auto mr-2 play" height="16" width="16">
							<path></path>
						</svg>
						Play
					</button>
					<button id="shuffleArtistButton" class="btn-spotify mx-1">
						<svg class="my-auto mr-2 shuffle" height="16" width="16">
							<path></path>
						</svg>
						Shuffle
					</button>
					<button id="playNextButton" class="btn-spotify mx-1">
						<i class="fal fa-arrow-to-right fa-fw mr-1 my-auto"></i>
						Play Next
					</button>
					<button id="playLastButton" class="btn-spotify mx-1">
						<i class="fal fa-arrow-to-bottom fa-fw mr-1 my-auto"></i>
						Play Last
					</button>
				</div>
			</div>
		</div>
	</div>
	<div id="albums" class="mx-auto centre">
		<?php foreach ($albums as $album): ?>
			<div class="album-container mx-2 my-2" data-album-id="<?= $album['id'] ?>" data-context-menu-actions="PLAY_NEXT,PLAY_LAST,GO_TO_ALBUM">
				<img class="lazy album-art" data-src="<?= $album['artFilepath'] ?>">
				<div class="title">
					<?= $album['name'] ?>
				</div>
				<div class="artist mb-2">
					<?= $album['recordType'] ?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</div>
<script>
	(function() {
		var $playArtistButtom = $('#playArtistButtom');
		var $shuffleArtistButton = $('#shuffleArtistButton');
		var $playNextButton = $('#playNextButton');
		var $playLastButton = $('#playLastButton');
		var $tracklistRows = $('.tracklist-row');

		$(function() {
			new LazyLoad({});
			updateBodyColour('<?= $accentColour ?>');
			scaleArtistNameText();
			initEvents();
		});

		async function getSongIds(albumIds) {
			var songIds = [];

			for (const albumId of albumIds) {
				var res = await $.get('/api/album/' + albumId);

				for (const songId of res.data.songIds) {
					songIds.push(songId);
				}
			}

			return songIds;	
		}

		function initEvents() {
			$('.album-container').click(function() {
				PartialManager.sharedInstance.loadPartial(`/album/${$(this).data('album-id')}`)
			});

			$playArtistButtom.click(async () => {
				var songIds = await getSongIds(
					$('.album-container').get().map(albumContainer => $(albumContainer).data('album-id'))
				);

				Music.sharedInstance.queue(songIds);
				Music.sharedInstance.skip(true);
			});

			$shuffleArtistButton.click(async () => {
				var songIds = await getSongIds(
					$('.album-container').get().map(albumContainer => $(albumContainer).data('album-id'))
				);

				Music.sharedInstance.queue(shuffle(songIds));
				Music.sharedInstance.skip(true);
			});

			$playNextButton.click(async () => {
				var songIds = await getSongIds(
					$('.album-container').get().map(albumContainer => $(albumContainer).data('album-id'))
				);

				songIds
					.reverse()
					.forEach(songId => Music.sharedInstance.queue().unshift(songId))
				;

				showToastNotification(true, "Playing Next");
			});

			$playLastButton.click(async () => {
				var songIds = await getSongIds(
					$('.album-container').get().map(albumContainer => $(albumContainer).data('album-id'))
				);
					
				songIds.forEach(songId => Music.sharedInstance.queue().push(songId))

				showToastNotification(true, "Playing Last");
			});

			$(window).resize(() => scaleArtistNameText());
		}
		
		function scaleArtistNameText() {
			var fontSize = 96;
			var $artist = $('#artistNameBig');
			var maxWidth = $(window).width() - 367;

			$artist.css({
				"font-size": `${fontSize}px`,
				"line-height": `${fontSize}px`,
				"white-space": "nowrap",
			});

			if ($artist.width() > maxWidth) {
				fontSize = Math.max(
					Math.floor(fontSize / ($artist.width() / maxWidth)),
					48
				);
			}

			$artist.css({
				"font-size": `${fontSize}px`,
				"line-height": `${fontSize}px`,
				"white-space": "normal",
				"visibility": "visible"
			});
		}
	})();
</script>
