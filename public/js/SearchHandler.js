class SearchHandler {
	static sharedInstance;

	constructor($searchBar, $clearSearchBarButton) {	
		if (SearchHandler.sharedInstance) {
			return SearchHandler.sharedInstance;
		}

		SearchHandler.sharedInstance = this;
		
		this._$searchBar = $searchBar;
		this._$clearSearchBarButton = $clearSearchBarButton;
		
		this._initEvents();
	}

	_initEvents() {	
		this._$searchBar.focus(e => MusicControl.sharedInstance.elements().$nowPlayingButton.removeClass('active'));
		this._$searchBar.keyup(e => this._search());
		this._$clearSearchBarButton.click(e => this.reset());
	}

	_search() {
		clearTimeout(this._timeout);

		var $searchResults = $('#searchResults');

		this._timeout = setTimeout(
			async () => {
				var term = this._$searchBar.val();
				var $songs = $('#songs');
				var $albums = $('#albums');
				var $songsContainer = $('#songsContainer');
				var $albumsContainer = $('#albumsContainer');
	
				this._$searchBar.attr('value', term);
	
				if (term.trim() === "") {
					this._$clearSearchBarButton.hide();
					$searchResults.hide();
					$songs.hide();
					$albums.hide();
					return;
				}
	
				this._$clearSearchBarButton.show();
	
				var res = await $.get('/api/search', { term: term });
	
				$songsContainer.empty().append(res.data.songs.map(song => this._createResultRow('song', song.id, song.albumId, song.name, song.artist, song.duration, song.artFilepath, song.explicit)));
				$albumsContainer.empty().append(res.data.albums.map(album => this._createResultRow('album', album.id, album.id, album.name, album.artist, album.duration, album.artFilepath, album.explicit)));
	
				(res.data.songs.length > 0 || res.data.albums.length > 0) ? $searchResults.show() : $searchResults.hide();
				(res.data.songs.length > 0) ? $songs.show(): $songs.hide();
				(res.data.albums.length > 0) ? $albums.show(): $albums.hide();

				PartialManager.sharedInstance.updateCurrentState();
			},
			100
		);

		SimpleBar
			.instances
			.get($searchResults.parents('[data-simplebar]').get(0))
			.getScrollElement()
			.scrollTop = 0
		;
	}

	_createResultRow(type, id, albumId, name, artist, duration, artFilepath, explicit) {
		var $resultRow = $(`<div class="music-row" data-${type}-id=${id} data-album-id=${albumId} data-context-menu-actions="PLAY_NEXT,PLAY_LAST,GO_TO_ALBUM" data-activable></div>`);
		var $img = $('<img>').prop('src', artFilepath);
		var $artwork = $('<div class="artwork"></div>').append($img);
		var $name = $('<div></div>').text(name);
		var $details = $('<div class="details"></div>').append([
			explicit ? $name : $name.append('<div class="explicit">E</div>'),
			$('<div></div>').text(artist),
		]);
		var $totalTime = (duration) ? $('<div class="total-time"></div>').text(secondsToTimeString(duration)) : "";

		$resultRow.dblclick(() => (type === 'song') ? this._playSong($resultRow) : this._playAlbum($resultRow));
		$resultRow.append([
			$('<div></div>').append([$artwork, $details]),
			$totalTime
		]);

		return $resultRow;
	}

	_playSong($resultRow) {
		Music.sharedInstance.history([]);
		Music.sharedInstance.changeSong($resultRow.data('song-id'), true);
		Music.sharedInstance.queue([]);
	}

	async _playAlbum($resultRow) {
		var res = await $.get(`/api/album/${$resultRow.data('album-id')}`);
		Music.sharedInstance.history([]);
		Music.sharedInstance.changeSong(res.data.songIds.shift(), true);
		Music.sharedInstance.queue(res.data.songIds);
	}

	focus() {
		this._$searchBar.focus();
	}

	reset() {
		this._$searchBar.val(null);
		this._search();
	}

	static get ACTIVABLE_DATA_SUFFIX() {
		return 'activable';
	}
}
