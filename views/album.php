<?php // var_dump($album); die() ?>

<link rel="stylesheet" href="/assets/css/album.css">

<div id="root" class="py-5 px-4" data-simplebar>
	<div class="d-flex mt-3 mb-4">
		<img id="albumArtLarge" class="mx-3" src="<?= $album['albumArtFilepath'] ?>">		
		<div class="d-flex mx-2">
			<div class="mt-auto">
				<div id="album">ALBUM</div>
				<div id="albumName">
					<?= $album['albumName'] ?>
				</div>
				<div class="album-details">
					<div id="albumArtist">
						<?= $album['albumArtist'] ?>
					</div>
					<div class="dot"></div>
					<div>
						<?= "Placeholder year" ?>
					</div>
					<div class="dot"></div>
					<div>PLACEHOLDER - 17 songs, 1 hr 17 min</div>
				</div>
			</div>
		</div>
	</div>
	<div class="d-flex my-3 mx-3">
		<button class="btn-spotify mr-1">
			<svg class="my-auto mr-2" height="16" width="16">
				<path d="M4.018 14L14.41 8 4.018 2z"></path>
			</svg>
			Play
		</button>
		<button class="btn-spotify mx-1">
			<svg class="my-auto mr-2" height="16" width="16">
				<path d="M4.5 6.8l.7-.8C4.1 4.7 2.5 4 .9 4v1c1.3 0 2.6.6 3.5 1.6l.1.2zm7.5 4.7c-1.2 0-2.3-.5-3.2-1.3l-.6.8c1 1 2.4 1.5 3.8 1.5V14l3.5-2-3.5-2v1.5zm0-6V7l3.5-2L12 3v1.5c-1.6 0-3.2.7-4.2 2l-3.4 3.9c-.9 1-2.2 1.6-3.5 1.6v1c1.6 0 3.2-.7 4.2-2l3.4-3.9c.9-1 2.2-1.6 3.5-1.6z"></path>
			</svg>
			Shuffle
		</button>
		<button class="btn-spotify mx-1">
			<svg class="my-auto mr-2" height="16" width="16">
				<path d="M14 7H9V2H7v5H2v2h5v5h2V9h5z"></path>
			</svg>
			Add to queue
		</button>
	</div>
	<div>
		<div id="tableHeader">
			<div>#</div>
			<div id="title">TITLE</div>
			<div id="timeIcon">
				<svg width="16" height="16">
					<path d="M7.999 3H6.999V7V8H7.999H9.999V7H7.999V3ZM7.5 0C3.358 0 0 3.358 0 7.5C0 11.642 3.358 15 7.5 15C11.642 15 15 11.642 15 7.5C15 3.358 11.642 0 7.5 0ZM7.5 14C3.916 14 1 11.084 1 7.5C1 3.916 3.916 1 7.5 1C11.084 1 14 3.916 14 7.5C14 11.084 11.084 14 7.5 14Z" fill="currentColor"></path>
				</svg>
			</div>
		</div>
		<?php foreach ($songs as $song): ?>
			<div class="tracklist-row" data-song-id="<?= $song['id'] ?>">
				<div class="track-number">
					<svg class="play" height="16" width="16">
						<path></path>
					</svg>
					<div>
						<?= $song['trackNumber'] ?>
					</div>
				</div>
				<div class="track-name">
					<div>
						<?= $song['songName'] ?>
					</div>
					<div>
						<?= $song['songArtist'] ?>
					</div>
				</div>
				<div class="total-time">
					<?php
					
					$minutes = floor($song['duration'] / 60);
					$seconds = $song['duration'] - ($minutes * 60);

					print "$minutes:$seconds";
					
					?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</div>

<script>
	$(function() {
		$('.tracklist-row').dblclick(function() {
			var $self = $(this);
			var album = { list: [], i: 0 };

			$(this).parent().find('.tracklist-row').each(function(i) {
				album.list.push($(this).data('song-id'));

				if ($(this).get(0) === $self.get(0)) {
					album.i = i;
				}
			});
			
			musicPlayer.queue([]);
			musicPlayer.history([]);
			musicPlayer.album(album);
			musicPlayer.changeSong($self.data('song-id'), true);
		});
	});
</script>