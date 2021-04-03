class MusicPlayer extends Howl {
	constructor($musicControl, metadata) {
		super({
			src: ['/assets/misc/silence.mp3'],
			format: 'mp3',
			volume: localStorage.getItem("volume") || 0,
			html5: true
		});

		this.queue = JSON.parse(localStorage.getItem("queue")) || [];
		this.history = JSON.parse(localStorage.getItem("history")) || [];
		this.$playButton = $musicControl.find('#playButton');
		this.$songName = $musicControl.find('#songName');
		this.$artistName = $musicControl.find('#artistName');
		this.$albumArt = $musicControl.find("#albumArt");
		this.$volumeSlider = $musicControl.find("#volumeSlider");
		this.$endTime = $musicControl.find("#endTime");
		this.metadata = metadata;
		this.on('end', e => this.skip(e));
		this.on('load', e => this.$endTime.text(getTimeString(this.duration())));

		var songId = localStorage.getItem("songId");

		if (songId) {
			this.changeSong(songId, false);
		}
	}

	isLoaded() {
		return this._state === "loaded";
	}

	changeSong(songId, play) {
		this.songId = songId;
		this.unload();
		this._duration = 0
		this._src = `/mp3/${songId}`;
		this.load();
		this.updateMusicControl();

		localStorage.setItem("songId", this.songId);

		if (play) {
			this.play();
		}
	}

	togglePlay() {
		this.playing() ? this.pause() : this.play();
	}

	play() {
		super.play();
		this.$playButton.removeClass("paused");
		localStorage.setItem("queue", JSON.stringify(this.queue));
		localStorage.setItem("history", JSON.stringify(this.history));
	}

	pause() {
		super.pause();
		this.$playButton.addClass("paused");
		localStorage.setItem("queue", JSON.stringify(this.queue));
		localStorage.setItem("history", JSON.stringify(this.history));	
	}

	enqueue(songId) {
		this.queue.push(songId);
	}

	previous() {
		if (this.seek() > 1) {
			this.seek(0);
			return;
		}

		if (this.history.length === 0) {
			this.pause();
			return;
		}

		var wasPlaying = this.playing();

		this.queue.unshift(this.songId);
		this.changeSong(this.history.pop(), wasPlaying);
	}

	skip(e) {
		if (this.queue.length === 0) {
			this.seek(this.duration());
			this.pause();
			return;
		}

		var wasPlaying = this.playing();

		this.history.push(this.songId);
		this.changeSong(this.queue.shift(), (wasPlaying || e));
	}

	volume(volume) {
		if (volume) {
			localStorage.setItem("volume", volume);
		}

		return super.volume(volume);
	}

	async updateMusicControl() {
		var res = await $.get(`/api/musicPlayer/${this.songId}`);
		
		this.$songName.text(res.songName);
		this.$artistName.text(res.songArtist);
		this.$albumArt.prop('src', res.albumArtFilepath);
		this.metadata.title = res.songName;
		this.metadata.artist = res.songArtist;
		this.metadata.album = res.albumName;
		this.metadata.artwork = [{ src: res.albumArtFilepath, sizes: '512x512', type: 'image/png' }];
	}
}