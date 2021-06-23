class SearchHandler {
	static sharedInstance;

	constructor($searchBar, $clearSearchBar) {	
		if (SearchHandler.sharedInstance) {
			return;
		} else {
			SearchHandler.sharedInstance = this;
		}

		this.$searchBar = $searchBar;
		this.$clearSearchBar = $clearSearchBar;
		
		this.initEvents();
	}

	initEvents() {
		$(window).mousedown(function(e) {
			$('.result-row.active').removeClass('active');

			var $resultRow = $(e.target).is('.result-row') ? $(e.target) : $(e.target).parents('.result-row').first();

			if ($resultRow) {
				$resultRow.addClass('active');
			}
		});
		this.$searchBar.keyup(() => this.search());
		this.$clearSearchBar.click(e => this.reset());
	}

	search() {
		clearTimeout(this.timeout);

		this.timeout = setTimeout(
			async() => {
				var term = this.$searchBar.val();
				var $searchResults = $('#searchResults');
				var $songs = $('#songs');
				var $albums = $('#albums');
				var $songsContainer = $('#songsContainer');
				var $albumsContainer = $('#albumsContainer');
	
				this.$searchBar.attr('value', term);
	
				if (term.trim() === "") {
					this.$clearSearchBar.hide();
					$searchResults.hide();
					$songs.hide();
					$albums.hide();
					return;
				}
	
				this.$clearSearchBar.show();
	
				var res = await $.get('/api/search', { term: term });
	
				$songsContainer.empty().append(res.songs.map(song => this.createResultRow('song', song.id, song.albumId, song.name, song.artist, song.duration, song.artFilepath)));
				$albumsContainer.empty().append(res.albums.map(album => this.createResultRow('album', album.id, album.id, album.name, album.artist, album.duration, album.artFilepath)));
	
				(res.songs.length > 0 || res.albums.length > 0) ? $searchResults.show() : $searchResults.hide();
				(res.songs.length > 0) ? $songs.show(): $songs.hide();
				(res.albums.length > 0) ? $albums.show(): $albums.hide();

				PartialManager.sharedInstance.updateCurrentState();
			},
			100
		);
	}

	createResultRow(type, id, albumId, name, artist, duration, artFilepath) {
		var $resultRow = $(`<div class="result-row" data-${type}-id=${id} data-album-id=${albumId} data-context-menu-actions="QUEUE,GO_TO_ALBUM"></div>`);
		var $img = $('<img>').prop('src', artFilepath);
		var $artwork = $('<div class="artwork"></div>').append($img);
		var $details = $('<div class="details"></div>').append([
			$('<div></div>').text(name),
			$('<div></div>').text(artist),
		]);
		var $totalTime = (duration) ? $('<div class="total-time"></div>').text(secondsToTimeString(duration)) : "";

		$resultRow.dblclick(() => (type === 'song') ? this.playSong($resultRow) : this.playAlbum($resultRow));
		$resultRow.append([
			$('<div></div>').append([$artwork, $details]),
			$totalTime
		]);

		return $resultRow;
	}

	playSong($resultRow) {
		MusicPlayer.sharedInstance.changeSong($resultRow.data('song-id'), true);
	}

	async playAlbum($resultRow) {
		var res = await $.get(`/api/album/${$resultRow.data('album-id')}`);
		MusicPlayer.sharedInstance.playNextUp({
			list: res.songIds,
			i: 0
		});
	}

	reset() {
		this.$searchBar
			.val(null)
			.blur()
		;
		this.search();
	}
}