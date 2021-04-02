class MusicPlayer extends Howl {
	constructor($musicControl, metadata) {
		super({
			src: [null],
			format: 'mp3',
			html5: true
		});
		this.queue = [];
		this.history = [];
		this.$playButton = $musicControl.find('#playButton');
		this.$songName = $musicControl.find('#songName');
		this.$artistName = $musicControl.find('#artistName');
		this.$albumArt = $musicControl.find("#albumArt");
		this.metadata = metadata;
		this.on('end', e => this.skip(e));
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
	}

	pause() {
		super.pause();
		this.$playButton.addClass("paused");
	}

	enqueue(songId) {
		this.queue.push(songId);
	}

	previous() {
		if (this.history.length === 0) {
			this.unload();
			return;
		}

		var wasPlaying = this.playing();

		this.queue.unshift(this.songId);
		this.changeSong(this.history.pop(), wasPlaying);
	}

	skip(e) {
		if (this.queue.length === 0) {
			this.unload();
			return;
		}

		var wasPlaying = this.playing();

		this.history.push(this.songId);
		this.changeSong(this.queue.shift(), (wasPlaying || e));
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