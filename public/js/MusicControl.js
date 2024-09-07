class MusicControl extends EventEmitter {
	static sharedInstance;

	constructor(elements, songIds, state) {
		if (MusicControl.sharedInstance) {
			return MusicControl.sharedInstance;
		}

		MusicControl.sharedInstance = super();
		
		this._music = new Music(state.volume);
		this._elements = elements;
		this._songIds = songIds;
		this._metadata = navigator.mediaSession.metadata = new MediaMetadata();

		navigator.mediaSession.setActionHandler('play', e => this._music.togglePlay());
		navigator.mediaSession.setActionHandler('pause', e => this._music.togglePlay());
		navigator.mediaSession.setActionHandler('previoustrack', e => this._music.previous());
		navigator.mediaSession.setActionHandler('nexttrack', e => this._music.skip());

		this._initEvents();
		
		if (!state.songId) {
			this._music.disable();
			return;
		}

		$.ajax(`/mp3/${state.songId}`, {
			statusCode: { 500: () => this._music.disable() },
			success: () => {
				this._music.changeSong(state.songId, false);

				if (state.seek) {
					this._music.seek(state.seek);
				}

				if (state.queue) {
					this._music.queue(state.queue);
				}
		
				if (state.history) {
					this._music.history(state.history);
				}
			}
		});
	}

	_initEvents() {
		this._music.on('enable', e => this._elements.$progressSlider.slider('enable'));
		this._music.on('play', async () => this._elements.$playButton.removeClass("paused"));
		this._music.on('pause', async () => this._elements.$playButton.addClass("paused"));
		this._music.on('songchange', e => {
			$.get(`/api/now-playing/${this._music.songId()}`);

			this._elements.$endTime.text(
				this._music.disabled() ? "0:00" : secondsToTimeString(this._music.duration())
			);
		});
		this._music.on('manualsongchange', e => this._update(false));
		this._music.on('autosongchange', e =>this._update(true));
		this._music.on('autoskip', e => $.get(`/api/scrobble/${this._music.lastSongId()}`));
		this._music.on('disable', e => {
			this._elements.$albumArt.removeAttr('src');
			this._elements.$songName.text('');
			this._elements.$artistName.text('');
			this._elements.$elapsedTime.text('0:00');
			this._elements.$endTime.text('0:00');
			this._elements.$saveButton.hide();

			if (this._elements.$progressSlider.hasClass('ui-slider')) {
				this._elements.$progressSlider.slider("value", 0);
				this._elements.$progressSlider.slider("disable");
			}
		});
		this._music.on('error', () => {
			this._music.disable();
			showToastNotification(false, "Playback Error");
		});
	}

	async _update(auto) {
		var res = await $.get(`/api/song/${this._music.songId()}`);

		this._albumId = res.data.albumId;
		this._elements.$songName.text(res.data.songName);
		this._elements.$artistName.text(res.data.songArtist);
		this._elements.$artistName.data('artistId', res.data.artistId);
		this._elements.$albumArt.prop('src', res.data.albumArtUrl);
		this._metadata.title = res.data.songName;
		this._metadata.artist = res.data.songArtist;
		this._metadata.album = res.data.albumName;
		this._metadata.artwork = [{ src: res.data.albumArtUrl, sizes: '512x512', type: 'image/png' }];	

		if (
			window.Notification &&
			Notification.permission === "granted" &&
			'serviceWorker' in navigator
		) {
			const sw = await navigator.serviceWorker.getRegistration('/');

			sw.showNotification(
				res.data.songName,
				{
					body: res.data.songArtist,
					icon: res.data.albumArtUrl,
					actions: [
						{
							action: "skip-action",
							title: "Skip ⏭️"
						},
						{
							action: "previous-action",
							title: "Previous"
						},
					],
					silent: true
				}
			);
		}

		if (res.data.isDeezer) {
			this._elements.$saveButton.show();
			(res.data.isSaved) 
				? this._elements.$saveButton.addClass('active')
				: this._elements.$saveButton.removeClass('active')
			;
		} else {
			this._elements.$saveButton.hide();
		}

		this._emit(
			(auto) ? 'updateauto' : 'updatemanual'
		);
	}

	music() {
		return this._music;
	}

	albumId() {
		return this._albumId;
	}

	elements() {
		return this._elements;
	}

	songIds(songIds) {
		if (songIds === undefined) {
			return this._songIds;
		} else {
			this._songIds = songIds;
		}
	}
}
