class SearchHandler {
	static sharedInstance;

	constructor($searchBar, $clearSearchBarButton) {	
		if (!SearchHandler.sharedInstance) {
			SearchHandler.sharedInstance = this;
		} else {
			return;
		}

		this._$searchBar = $searchBar;
		this._$clearSearchBarButton = $clearSearchBarButton;
		
		this._initEvents();
	}

	_initEvents() {
		$(window).mousedown(function(e) {
			var $element = $(e.target).is(`[data-${SearchHandler.ACTIVABLE_DATA_SUFFIX}]`)
				? $(e.target)
				: $(e.target).parents(`[data-${SearchHandler.ACTIVABLE_DATA_SUFFIX}]`).first()
			;

			$(`[data-${SearchHandler.ACTIVABLE_DATA_SUFFIX}]`).removeClass('active');

			if ($element) {
				$element.addClass('active');
			}
		});
		
		this._$searchBar.keyup(e => this._search());
		this._$clearSearchBarButton.click(e => this.reset());
	}

	_search() {
		clearTimeout(this._timeout);

		this._timeout = setTimeout(
			async() => {
				var term = this._$searchBar.val();
				var $searchResults = $('#searchResults');
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
	
				$songsContainer.empty().append(res.data.songs.map(song => this._createResultRow('song', song.id, song.albumId, song.name, song.artist, song.duration, song.artFilepath)));
				$albumsContainer.empty().append(res.data.albums.map(album => this._createResultRow('album', album.id, album.id, album.name, album.artist, album.duration, album.artFilepath)));
	
				(res.data.songs.length > 0 || res.data.albums.length > 0) ? $searchResults.show() : $searchResults.hide();
				(res.data.songs.length > 0) ? $songs.show(): $songs.hide();
				(res.data.albums.length > 0) ? $albums.show(): $albums.hide();

				PartialManager.sharedInstance.updateCurrentState();
			},
			100
		);
	}

	_createResultRow(type, id, albumId, name, artist, duration, artFilepath) {
		var $resultRow = $(`<div class="music-row" data-${type}-id=${id} data-album-id=${albumId} data-context-menu-actions="QUEUE,GO_TO_ALBUM" data-activable></div>`);
		var $img = $('<img>').prop('src', artFilepath);
		var $artwork = $('<div class="artwork"></div>').append($img);
		var $details = $('<div class="details"></div>').append([
			$('<div></div>').text(name),
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
		Music.sharedInstance.changeSong($resultRow.data('song-id'), true);
	}

	async _playAlbum($resultRow) {
		var res = await $.get(`/api/album/${$resultRow.data('album-id')}`);
		Music.sharedInstance.playNextUp({
			list: res.data.songIds,
			i: 0
		});
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