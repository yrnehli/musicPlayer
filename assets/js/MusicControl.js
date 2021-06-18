class MusicControl extends Howl {
	constructor($musicControl) {
		var state = JSON.parse(localStorage.getItem("state")) || {};
		
		super({
			src: [null],
			format: 'mp3',
			volume: state.volume || 0,
			html5: true
		});

		navigator.mediaSession.metadata = new MediaMetadata();
		navigator.mediaSession.setActionHandler('play', () => this.togglePlay());
		navigator.mediaSession.setActionHandler('pause', () => this.togglePlay());
		navigator.mediaSession.setActionHandler('previoustrack', () => this.previous());
		navigator.mediaSession.setActionHandler('nexttrack', () => this.skip());

		this.__disabled = false;
		this.__queue = state.queue || [];
		this.__nextUp = state.nextUp || { list: [], i: 0 };
		this.__$prevButton = $musicControl.find('#prevButton');
		this.__$playButton = $musicControl.find('#playButton');
		this.__$skipButton = $musicControl.find('#skipButton');
		this.__$progressSlider = $musicControl.find('#progressSlider');
		this.__$songName = $musicControl.find('#songName');
		this.__$artistName = $musicControl.find('#artistName');
		this.__$albumArt = $musicControl.find("#albumArt");
		this.__$volumeSlider = $musicControl.find("#volumeSlider");
		this.__$elapsedTime = $musicControl.find("#elapsedTime");
		this.__$endTime = $musicControl.find("#endTime");
		this.__$followAlbumButton = $musicControl.find("#followAlbumButton");
		this.__metadata = navigator.mediaSession.metadata;
		this.on('end', e => this.skip(e));
		this.on('load', () => this.__$endTime.text((this.__disabled) ? "0:00" : secondsToTimeString(this.duration())));

		if (state.followAlbum) {
			this.__$followAlbumButton.addClass('active');
		}

		if (!state.songId) {
			this.disable();
			return;
		}

		$.ajax(`/mp3/${state.songId}`, {
			statusCode: { 500: () => this.disable() },
			success: () => {
				this.changeSong(state.songId, false, false, true);
				this.seek(state.seek || 0);
			},
		});
	}

	disabled() {
		return this.__disabled;
	}

	songId() {
		return this.__songId;
	}

	albumId() {
		return this.__albumId;
	}

	disable() {
		this.__songId = null;
		this.__queue = [];
		this.__nextUp = { list: [], i: 0 };
		this.__$albumArt.removeAttr('src');
		this.__$songName.text('');
		this.__$artistName.text('');
		this.__$elapsedTime.text('0:00');
		this.__$endTime.text('0:00');
		this.pause();
		localStorage.clear();

		if (this.__$progressSlider.hasClass('ui-slider')) {
			this.__$progressSlider.slider("value", 0);
			this.__$progressSlider.slider("disable");
		}

		this.__disabled = true;
	}

	enable() {
		this.__$progressSlider.slider('enable');
		this.__disabled = false;
	}

	loaded() {
		return (this._state === "loaded");
	}

	async changeSong(songId, play, delayFollowAlbumArt = true, disableFollowAlbumArt = false) {
		this.__songId = songId;
		this.enable();
		
		this.unload();
		this._duration = 0;
		this._sprite = {};
		this._src = `/mp3/${songId}`;
		this.load();
		
		// Play even if we want don't want to so we get the media session metadata
		this.play();
		
		if (!play) {
			this.pause();
		}
		
		await this.updateMusicControl();

		if (!disableFollowAlbumArt && this.__$followAlbumButton.hasClass('active')) {
			clearTimeout(this.__timeout);

			if (delayFollowAlbumArt) {
				this.__timeout = setTimeout(() => partialManager.loadPartial(`/album/${this.__albumId}`), 1000);
			} else {
				partialManager.loadPartial(`/album/${this.__albumId}`);
			}
		}
	}

	togglePlay() {
		this.playing() ? this.pause() : this.play();
	}

	play() {
		if (this.__disabled) {
			return;
		}

		this.__$playButton.removeClass("paused");
		super.play();

		$('.tracklist-row.active').removeClass('active');
		$(`.tracklist-row[data-song-id="${this.__songId}"]`).addClass('active');
	}

	pause() {
		if (this.__disabled) {
			return;
		}

		this.__$playButton.addClass("paused");
		super.pause();

		$('.tracklist-row.active').removeClass('active');
	}

	previous() {
		if (this.__disabled) {
			return;
		}

		if (this.seek() >= 3) {
			this.seek(0);
			return;
		}

		var wasPlaying = this.playing();
		
		if (this.__nextUp.list.length > 0 && this.__nextUp.i - 1 >= 0) {
			this.changeSong(this.__nextUp.list[--this.__nextUp.i], wasPlaying);
		} else {
			this.disable();
		}
	}

	skip(e) {
		if (this.__disabled) {
			return;
		}

		var wasPlaying = (e || this.playing());

		if (this.__queue.length > 0) {
			this.changeSong(this.__queue.shift(), wasPlaying, !e);
		} else if (this.__nextUp.list.length > 0 && this.__nextUp.i + 1 < this.__nextUp.list.length) {
			this.changeSong(this.__nextUp.list[++this.__nextUp.i], wasPlaying, !e);
		} else {
			this.disable();
		}
	}

	nextUp(nextUp) {
		if (nextUp) {
			this.__nextUp = nextUp;
		} else {
			return this.__nextUp;
		}
	}

	queue(queue) {
		if (queue) {
			this.__queue = queue;
		} else {
			return this.__queue;
		}
	}

	playNextUp(nextUp) {
		this.__queue = [];
		this.__nextUp = nextUp;
		this.changeSong(nextUp.list[nextUp.i], true);
	}

	async updateMusicControl() {
		var res = await $.get(`/api/song/${this.__songId}`);
		
		this.__albumId = res.albumId;
		this.__$songName.text(res.songName);
		this.__$artistName.text(res.songArtist);
		this.__$albumArt.prop('src', res.albumArtFilepath);
		this.__metadata.title = res.songName;
		this.__metadata.artist = res.songArtist;
		this.__metadata.album = res.albumName;
		this.__metadata.artwork = [{ src: res.albumArtFilepath, sizes: '512x512', type: 'image/png' }];
	}
}