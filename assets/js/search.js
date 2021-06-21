(function() {
	var $searchBar;
	var $searchResults;
	var $clearSearchBar;
	var $songs;
	var $albums;
	var $songsContainer;
	var $albumsContainer;
	var timeout;

	$(function() {
		$searchBar = $('#searchBar');
		$searchResults = $('#searchResults');
		$clearSearchBar = $('#clearSearchBar');
		$songs = $('#songs');
		$albums = $('#albums');
		$songsContainer = $('#songsContainer');
		$albumsContainer = $('#albumsContainer');
		initEvents();
	});

	function initEvents() {
		$(window).keydown(e => assignKeydown(e));
		$(window).keyup(e => assignKeyup(e));
		$(window).mousedown(function(e) {
			$('.result-row.active').removeClass('active');

			var $resultRow = $(e.target).is('.result-row') ? $(e.target) : $(e.target).parents('.result-row').first();

			if ($resultRow) {
				$resultRow.addClass('active');
			}
		});
		$searchBar.keyup(() => search());
		$clearSearchBar.click(e => {
			$searchBar.val(null);
			search();
		});
	}

	function search() {
		clearTimeout(timeout);

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
	
				$songsContainer.empty().append(res.songs.map(song => createResultRow('song', song.id, song.albumId, song.name, song.artist, song.duration, song.artFilepath)));
				$albumsContainer.empty().append(res.albums.map(album => createResultRow('album', album.id, album.id, album.name, album.artist, album.duration, album.artFilepath)));
	
				(res.songs.length > 0 || res.albums.length > 0) ? $searchResults.show() : $searchResults.hide();
				(res.songs.length > 0) ? $songs.show(): $songs.hide();
				(res.albums.length > 0) ? $albums.show(): $albums.hide();

				PartialManager.sharedInstance.updateCurrentState();
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

	function createResultRow(type, id, albumId, name, artist, duration, artFilepath) {
		var $resultRow = $(`<div class="result-row" data-${type}-id=${id} data-album-id=${albumId} data-context-menu-actions="QUEUE,GO_TO_ALBUM"></div>`);
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
		MusicPlayer.sharedInstance.changeSong($resultRow.data('song-id'), true);
	}

	async function playAlbum($resultRow) {
		var res = await $.get(`/api/album/${$resultRow.data('album-id')}`);
		MusicPlayer.sharedInstance.playNextUp({
			list: res.songIds,
			i: 0
		});
	}
})();