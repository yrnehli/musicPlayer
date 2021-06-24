class MusicControl extends EventEmitter {
	static sharedInstance;

	constructor(elements, state) {
		if (!MusicControl.sharedInstance) {
			MusicControl.sharedInstance = super();
		} else {
			return;
		}

		this._music = new Music();
		this._elements = elements;
		this._metadata = navigator.mediaSession.metadata = new MediaMetadata();

		navigator.mediaSession.setActionHandler('play', e => this._music.togglePlay());
		navigator.mediaSession.setActionHandler('pause', e => this._music.togglePlay());
		navigator.mediaSession.setActionHandler('previoustrack', e => this._music.previous());
		navigator.mediaSession.setActionHandler('nexttrack', e => this._music.skip());

		this._initEvents();

		if (state.volume >= 0) {
			this._music.volume(state.volume);
		}

		if (state.queue) {
			this._music.queue(state.queue);
		}

		if (state.nextUp) {
			this._music.nextUp(state.nextUp);
		}

		if (!state.songId) {
			this._music.disable();
			return;
		}

		$.ajax(`/api/mp3/${state.songId}`, {
			statusCode: { 500: () => this._music.disable() },
			success: () => {
				this._music.changeSong(state.songId, false);
				this._music.seek(state.seek || 0);
			},
		});
	}

	_initEvents() {
		this._music.on('enable', e => this._elements.$progressSlider.slider('enable'));
		this._music.on('play', e => this._elements.$playButton.removeClass("paused"));
		this._music.on('pause', e => this._elements.$playButton.addClass("paused"));
		this._music.on('songchangeauto', e => this._update(true));
		this._music.on('songchangemanual', e => this._update(false));
		this._music.on('load', e => {
			this._elements.$endTime.text(
				this._music.disabled() ? "0:00" : secondsToTimeString(this._music.duration())
			)	
		});
		this._music.on('disable', e => {
			this._elements.$albumArt.removeAttr('src');
			this._elements.$songName.text('');
			this._elements.$artistName.text('');
			this._elements.$elapsedTime.text('0:00');
			this._elements.$endTime.text('0:00');

			if (this._elements.$progressSlider.hasClass('ui-slider')) {
				this._elements.$progressSlider.slider("value", 0);
				this._elements.$progressSlider.slider("disable");
			}
		});
	}

	async _update(auto) {
		var res = await $.get(`/api/song/${this._music.songId()}`);
		this._albumId = res.albumId;
		this._elements.$songName.text(res.songName);
		this._elements.$artistName.text(res.songArtist);
		this._elements.$albumArt.prop('src', res.albumArtUrl);
		this._metadata.title = res.songName;
		this._metadata.artist = res.songArtist;
		this._metadata.album = res.albumName;
		this._metadata.artwork = [{ src: res.albumArtUrl, sizes: '512x512', type: 'image/png' }];
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
}