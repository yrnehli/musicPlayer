class MusicPlayer extends Howl {
	constructor($musicControl, metadata) {
		var state = JSON.parse(localStorage.getItem("state")) || {};
		
		super({
			src: ['/assets/misc/silence.mp3'],
			format: 'mp3',
			volume: state.volume || 0,
			html5: true
		});

		this.__disabled = false;
		this.__queue = state.queue || [];
		this.__history = state.history || [];
		this.__album = state.album || { list: [], i: 0 };
		this.__$prevButton = $musicControl.find('#prevButton');
		this.__$playButton = $musicControl.find('#playButton');
		this.__$skipButton = $musicControl.find('#skipButton');
		this.__$progressSlider = $musicControl.find('#progressSlider');
		this.__$songName = $musicControl.find('#songName');
		this.__$artistName = $musicControl.find('#artistName');
		this.__$albumArt = $musicControl.find("#albumArt");
		this.__$volumeSlider = $musicControl.find("#volumeSlider");
		this.__$endTime = $musicControl.find("#endTime");
		this.__metadata = metadata;
		this.on('end', e => this.skip(e));
		this.on('load', e => this.__$endTime.text((this.__disabled) ? "0:00" : getTimeString(this.duration())));

		if (!state.songId) {
			this.disable();
			return;
		}

		$.ajax(`/mp3/${state.songId}`, {
			statusCode: { 500: () => this.disable() },
			success: () => {
				this.changeSong(state.songId, false);
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

	disable() {
		this.__disabled = true;
		this.__songId = null;
		this.__queue = [];
		this.__history = [];
		this.__album = { list: [], i: 0 };
		this.__$albumArt.removeAttr('src');
		this.__$songName.text('');
		this.__$artistName.text('');
		this.__$endTime.text('0:00');
		this.__$prevButton.prop('disabled', true);
		this.__$playButton.prop('disabled', true);
		this.__$skipButton.prop('disabled', true);
		if (this.__$progressSlider.hasClass('ui-slider')) {
			this.__$progressSlider.slider("disable");
		}
		this.pause();
		this.seek(0);
		localStorage.clear();
	}

	enable() {
		if (this.__disabled === false) {
			return;
		}

		this.__disabled = false;
		this.__$prevButton.prop('disabled', false);
		this.__$playButton.prop('disabled', false);
		this.__$skipButton.prop('disabled', false);
		this.__$progressSlider.slider('enable');
	}

	loaded() {
		return (this._state === "loaded");
	}

	changeSong(songId, play) {
		this.__songId = songId;
		this.enable();
		this.updateMusicControl();

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
	}

	togglePlay() {
		this.playing() ? this.pause() : this.play();
	}

	play() {
		super.play();
		this.__$playButton.removeClass("paused");
		$('.tracklist-row.active').removeClass('active');
		$(`.tracklist-row[data-song-id="${this.__songId}"]`).addClass('active');
	}

	pause() {
		super.pause();
		this.__$playButton.addClass("paused");
		$('.tracklist-row.active').removeClass('active');
	}

	previous() {
		if (this.seek() > 1) {
			this.seek(0);
			return;
		}

		var wasPlaying = this.playing();
		
		if (this.__album.list.length > 0 && this.__album.i - 1 >= 0) {
			this.__album.i--;
			this.changeSong(this.__album.list[this.__album.i], wasPlaying);
		} else if (this.__history.length > 0) {
			this.__queue.unshift(this.songId);
			this.changeSong(this.__history.pop(), wasPlaying);
		} else {
			this.pause();
		}
	}

	skip(e) {
		var wasPlaying = (e || this.playing());

		if (this.__queue.length > 0) {
			this.__history.push(this.songId);
			this.changeSong(this.__queue.shift(), (wasPlaying || e));
		} else if (this.__album.list.length > 0 && this.__album.i + 1 < this.__album.list.length) {
			this.__album.i++;
			this.changeSong(this.__album.list[this.__album.i], wasPlaying);
		} else if (this.__album.list.length > 0) {
			this.__album.i = 0;
			this.changeSong(this.__album.list[0], false);
		} else {
			this.disable();
		}
	}

	album(album) {
		if (album) {
			this.__album = album;
		} else {
			return this.__album;
		}
	}

	queue(queue) {
		if (queue) {
			this.__queue = queue;
		} else {
			return this.__queue;
		}
	}

	history(history) {
		if (history) {
			this.__history = history;
		} else {
			return this.__history;
		}
	}

	async updateMusicControl() {
		var res = await $.get(`/api/song/${this.__songId}`);
		
		this.__$songName.text(res.songName).data('albumId', res.albumId);
		this.__$artistName.text(res.songArtist);
		this.__$albumArt.prop('src', res.albumArtFilepath);
		this.__metadata.title = res.songName;
		this.__metadata.artist = res.songArtist;
		this.__metadata.album = res.albumName;
		this.__metadata.artwork = [{ src: res.albumArtFilepath, sizes: '512x512', type: 'image/png' }];
	}
}