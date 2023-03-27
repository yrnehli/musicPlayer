class SearchHandler {
	static sharedInstance;

	constructor($searchBar, $searchResults, $clearSearchBarButton) {	
		if (SearchHandler.sharedInstance) {
			return SearchHandler.sharedInstance;
		}

		SearchHandler.sharedInstance = this;
		
		this._$searchBar = $searchBar;
		this._$searchResults = $searchResults;
		this._$clearSearchBarButton = $clearSearchBarButton;
		
		this._initEvents();
	}

	_initEvents() {
		this._$clearSearchBarButton.click(e => this.reset());
		this._$searchBar.keyup(e => this._search());
		this._$searchBar.focus(e => this._search());
		$(document).click(e => {
			if (!$(e.target).closest(this._$searchBar).length && !$(e.target).closest(this._$searchResults).length) {
				this._$searchResults.fadeOut(100);
			}
		});
	}

	_search() {
		clearTimeout(this._timeout);

		this._timeout = setTimeout(
			async () => {
				var term = this._$searchBar.val();
	
				this._$searchBar.attr('value', term);
	
				if (term.trim() === "") {
					this._$clearSearchBarButton.css('visibility', 'hidden');
					this._$searchResults.fadeOut(100);
					return;
				}
	
				this._$clearSearchBarButton.css('visibility', 'visible');
	
				var res = await $.get('/api/search', { term: term });

				this._clearOldResults(res.data.songs, res.data.albums);
	
				this._$searchResults
					.append(res.data.songs.map(song => this._createResultRow('song', song.id, song.albumId, song.name, song.artist, song.artFilepath, song.explicit)))
					.append(res.data.albums.map(album => this._createResultRow('album', album.id, album.id, album.name, album.artist, album.artFilepath, album.explicit)))
					.append(res.data.artists.map(artist => this._createResultRow('artist', artist.id, null, artist.name, artist.name, artist.artFilepath, false)))
				;
	
				(res.data.songs.length > 0 || res.data.albums.length > 0) ? this._$searchResults.show() : this._$searchResults.fadeOut(100);
			},
			100
		);
	}

	_clearOldResults(songs, albums) {
		this._$searchResults.children().each(function() {
			const $this = $(this);

			if ($this.data('song-id')) {
				if (!songs.some(song => song.id == $this.data('song-id'))) {
					$this.remove();
				}
			} else {
				if (!albums.some(album => album.id == $this.data('album-id'))) {
					$this.remove();
				}
			}
		});
	}

	_createResultRow(type, id, albumId, name, artist, artFilepath, explicit) {
		if (type === 'song') {
			if (this._$searchResults.find(`[data-song-id="${id}"]`).length) {
				return;
			}
		} else if (type ==="album") {
			if (this._$searchResults.find(`[data-album-id="${id}"]`).not('[data-song-id]').length) {
				return;
			}
		} else  {
			if (this._$searchResults.find(`[data-artist-id="${id}"]`).not('[data-artist-id]').length) {
				return;
			}
		}

		var $resultRow = $(
			`<div
				class="music-row result-row"
				data-${type}-id=${id}
				data-activable
			>
			</div>
		`);

		if (['song', 'album'].includes(type)) {
			$resultRow.attr('data-album-id', albumId);
			$resultRow.attr('data-context-menu-actions', "PLAY_NEXT,PLAY_LAST,GO_TO_ALBUM");
			$resultRow.dblclick(() => {
				(type === 'song') ? this._playSong($resultRow) : this._playAlbum($resultRow);
				SearchHandler.sharedInstance.reset();
			});
		} else {
			$resultRow.dblclick(() => {
				PartialManager.sharedInstance.loadPartial('/artist/' + id)
				SearchHandler.sharedInstance.reset();
			});
		}

		var $img = $('<img>').prop('src', artFilepath);
		var $artwork = $('<div class="artwork"></div>').append($img);
		var $name = $('<div></div>').text(name);
		var $details = $('<div class="details"></div>').append([
			(explicit === true) ? $name.append('<div class="explicit">E</div>') : $name,
			$('<div class="d-flex"></div>').html(
				`<span>${type[0].toUpperCase() + type.slice(1)}</span>
				<div class="dot"></div>
				<span class="artist">${artist}</span>`
			)
		]);	

		$resultRow.append(
			$('<div></div>').append([$artwork, $details])
		);

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
