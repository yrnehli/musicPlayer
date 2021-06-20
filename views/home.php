<link rel="stylesheet" href="/assets/css/home.css">

<div id="root" class="py-2 px-2 d-flex" data-simplebar>
	<div class="mx-auto centre">
		<div class="d-flex mx-2 my-2 w-100">
			<input type="text" id="searchBar" class="mx-auto" placeholder="Search..." style="visibility: hidden;">
			<i id="clearSearchBar" class="fal fa-times" style="display: none;"></i>
		</div>
		<div id="searchResults" style="display: none;">
			<div class="my-3">
				<div id="songs" style="display: none;">
					<h2>Songs</h2>
					<div id="songsContainer"></div>
				</div>
			</div>
			<div class="my-3">
				<div id="albums" style="display: none;">
					<h2>Albums</h2>
					<div id="albumsContainer"></div>
				</div>
			</div>
		</div>
		<div id="albums" class="mx-auto centre">
			<?php foreach ($albums as $album): ?>
				<div class="album-container mx-2 my-2" data-album-id="<?= $album['id'] ?>">
					<img class="album-art" src="<?= $album['artFilepath'] ?>">
					<div class="title">
						<?= $album['name'] ?>
					</div>
					<div class="artist mb-2">
						<?= $album['artist'] ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>

<script>
	(function() {
		var $searchBar = $('#searchBar');
		var $searchResults = $('#searchResults');
		var $clearSearchBar = $('#clearSearchBar');
		var $songs = $('#songs');
		var $albums = $('#albums');
		var $songsContainer = $('#songsContainer');
		var $albumsContainer = $('#albumsContainer');
		var timeout;

		$(function() {
			resizeSearch();
			initEvents();
		});

		function initEvents() {
			$(window).keydown(e => assignKeydown(e));
			$(window).keyup(e => assignKeyup(e));
			$(window).resize(e => resizeSearch());
			$searchBar.keyup(() => search());
			$clearSearchBar.click(e => {
				$searchBar.val(null);
				search();
			});
			$('.album-container').click(function() {
				partialManager.loadPartial(`/album/${$(this).data('album-id')}`)
			});
		}

		function search() {
			partialManager.updateCurrentState();
			clearInterval(timeout);

			timeout = setTimeout(
				async() => {
					var term = $searchBar.val();
		
					$searchBar.attr('value', term);
		
					if (term.trim() === "") {
						$clearSearchBar.hide();
						$searchResults.hide();
						$songs.hide();
						$albums.hide();
						return;
					}
		
					$clearSearchBar.show();
		
					var res = await $.get('/api/search', { term: term });
		
					$songsContainer.empty().append(res.songs.map(song => createResultRow('song', song.id, song.name, song.artist, song.duration, song.artFilepath)));
					$albumsContainer.empty().append(res.albums.map(album => createResultRow('album', album.id, album.name, album.artist, album.duration, album.artFilepath)));
		
					(res.songs.length > 0 || res.albums.length > 0) ? $searchResults.show() : $searchResults.hide();
					(res.songs.length > 0) ? $songs.show(): $songs.hide();
					(res.albums.length > 0) ? $albums.show(): $albums.hide();
				},
				100
			);
		}

		function assignKeydown(e) {
			// Ctrl + F
			if (e.ctrlKey && e.keyCode === 70) {
				e.preventDefault();
			}
		}

		function assignKeyup(e) {
			// Ctrl + F
			if (e.ctrlKey && e.keyCode === 70) {
				e.preventDefault();
				$searchBar.focus();
			}
		}

		function createResultRow(type, id, name, artist, duration, artFilepath) {
			var $resultRow = $(`<div class="result-row" data-${type}-id=${id}></div>`);
			var $img = $('<img>').prop('src', artFilepath);
			var $artwork = $('<div class="artwork"></div>').append($img);
			var $details = $('<div class="details"></div>').append([
				$('<div></div>').text(name),
				$('<div></div>').text(artist),
			]);
			var $totalTime = (duration) ? $('<div class="total-time"></div>').text(secondsToTimeString(duration)) : "";

			$resultRow.dblclick(() => (type === 'song') ? playSong($resultRow) : playAlbum($resultRow));
			$resultRow.append([
				$('<div></div>').append([$artwork, $details]),
				$totalTime
			]);

			return $resultRow;
		}

		function playSong($resultRow) {
			musicPlayer.changeSong($resultRow.data('song-id'), true);
		}

		async function playAlbum($resultRow) {
			var res = await $.get(`/api/album/${$resultRow.data('album-id')}`);
			musicPlayer.playNextUp({
				list: res.songIds,
				i: 0
			});
		}

		function resizeSearch() {
			var width = Math.floor($('#root').width() / 257) * 257 - 16;

			$searchResults.css('width', `${width}px`);
			$searchBar.css({
				width: `${width}px`,
				visibility: "visible"
			});
			$clearSearchBar.css('right', `${($searchBar.outerWidth(true) - $searchBar.outerWidth()) / 2 + 33}px`);
		}
	})();
</script>