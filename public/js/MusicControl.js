class MusicControl extends EventEmitter {
	static sharedInstance;

	constructor(elements, state) {
		if (!MusicControl.sharedInstance) {
			MusicControl.sharedInstance = super();
		} else {
			return;
		}

		this._music = new Music();
		this._$prevButton = elements.$prevButton;
		this._$playButton = elements.$playButton;
		this._$skipButton = elements.$skipButton;
		this._$progressSlider = elements.$progressSlider;
		this._$songName = elements.$songName;
		this._$artistName = elements.$artistName;
		this._$albumArt = elements.$albumArt;
		this._$volumeSlider = elements.$volumeSlider;
		this._$elapsedTime = elements.$elapsedTime;
		this._$endTime = elements.$endTime;
		this._$nowPlayingButton = elements.$nowPlayingButton;
		this._metadata = navigator.mediaSession.metadata = new MediaMetadata();

		navigator.mediaSession.setActionHandler('play', e => this._music.togglePlay());
		navigator.mediaSession.setActionHandler('pause', e => this._music.togglePlay());
		navigator.mediaSession.setActionHandler('previoustrack', e => this._music.previous());
		navigator.mediaSession.setActionHandler('nexttrack', e => this._music.skip());

		this._initEvents();

		if (state.volume) {
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
		this._music.on('enable', e => this._$progressSlider.slider('enable'));
		this._music.on('play', e => this._$playButton.removeClass("paused"));
		this._music.on('pause', e => this._$playButton.addClass("paused"));
		this._music.on('songchange', e => this._update());
		this._music.on('load', e => {
			this._$endTime.text(
				this._music.disabled() ? "0:00" : secondsToTimeString(this._music.duration())
			)	
		});
		this._music.on('disable', e => {
			this._$albumArt.removeAttr('src');
			this._$songName.text('');
			this._$artistName.text('');
			this._$elapsedTime.text('0:00');
			this._$endTime.text('0:00');

			if (this._$progressSlider.hasClass('ui-slider')) {
				this._$progressSlider.slider("value", 0);
				this._$progressSlider.slider("disable");
			}
		});
	}

	async _update() {
		var res = await $.get(`/api/song/${this._music.songId()}`);
		this._albumId = res.albumId;
		this._$songName.text(res.songName);
		this._$artistName.text(res.songArtist);
		this._$albumArt.prop('src', res.albumArtUrl);
		this._metadata.title = res.songName;
		this._metadata.artist = res.songArtist;
		this._metadata.album = res.albumName;
		this._metadata.artwork = [{ src: res.albumArtUrl, sizes: '512x512', type: 'image/png' }];
		this.emit('update');
	}

	music() {
		return this._music;
	}

	albumId() {
		return this._albumId;
	}
}