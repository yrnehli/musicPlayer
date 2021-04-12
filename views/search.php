<link rel="stylesheet" href="/assets/css/search.css">

<div class="py-4 px-4 d-flex" data-simplebar>
	<input type="text" id="searchBar" placeholder="Search...">
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

<script>
	(function() {
		var $searchBar = $('#searchBar');
		var $songs = $('#songs');
		var $albums = $('#albums');
		var $songsContainer = $('#songsContainer');
		var $albumsContainer = $('#albumsContainer');
		var keyupTimer;

		$(function() {
			$searchBar.focus();
			initEvents();
		});

		function initEvents() {
			$searchBar.keyup(() => search());
			$(window).keyup(e => assignKeyup(e));
		}

		function search() {
			clearInterval(keyupTimer);

			var term = $searchBar.val();

			if (term.trim() === "") {
				$songs.hide();
				$albums.hide();
				return;
			}

			keyupTimer = setInterval(async () => {
				var res = await $.get('/api/search', { term: term});

				$songsContainer.empty().append(res.songs.map(song => createResultRow('song', song.id, song.name, song.artist, song.duration, song.artFilepath)));
				$albumsContainer.empty().append(res.albums.map(album => createResultRow('album', album.id, album.name, album.artist, album.duration, album.artFilepath)));

				$songsContainer.children().each(function(i) {
					if (i >= 5) {
						$(this).hide();
					}
				});

				$albumsContainer.children().each(function(i) {
					if (i >= 5) {
						$(this).hide();
					}
				});

				(res.songs.length > 0) ? $songs.show() : $songs.hide();
				(res.albums.length > 0) ? $albums.show() : $albums.hide();

				clearInterval(keyupTimer);
			}, 300);
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
			var $totalTime = $('<div class="total-time"></div>').text(secondsToTimeString(duration));

			$resultRow.dblclick(() => (type === 'song') ? playSong($resultRow) : playAlbum($resultRow));
			$resultRow.append([
				$('<div></div>').append([$artwork, $details]),
				$totalTime
			]);

			return $resultRow;
		}

		function playSong($resultRow) {
			musicControl.changeSong($resultRow.data('song-id'), true);
		}

		async function playAlbum($resultRow) {
			var res = await $.get(`/api/album/${$resultRow.data('album-id')}`);
			musicControl.playNextUp({ list: res.songIds, i: 0 });
		}
	})();
</script>