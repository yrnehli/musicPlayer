class MusicPlayer {
	constructor($musicControl, metadata) {
		this.sound = new Howl({
			src: [null],
			format: 'mp3',
			html5: true
		});
		this.queue = [];
		this.history = [];
		this.$playButton = $musicControl.find('#playButton');
		this.$songName = $musicControl.find('#songName');
		this.metadata = metadata;
		this.sound.on('end', e => this.skip(e));
	}

	changeSong(songId, play) {
		this.songId = songId;
		this.sound.unload();
		this.sound._duration = 0
		this.sound._src = `/mp3/${songId}`;
		this.sound.load();
		this.updateMusicControl();

		if (play)
			this.play();
	}

	togglePlay() {
		this.sound.playing() ? this.pause() : this.play();
	}

	play() {
		this.sound.play();
		this.$playButton
			.addClass('playing')
			.removeClass('paused')
			.text('Pause');
	}

	pause() {
		this.sound.pause();
		this.$playButton
			.addClass('paused')
			.removeClass('playing')
			.text('Play');
	}

	enqueue(songId) {
		this.queue.push(songId);
	}

	previous() {
		if (this.history.length === 0) {
			this.sound.unload();
			return;
		}

		var wasPlaying = this.sound.playing();

		this.queue.unshift(this.songId);
		this.changeSong(this.history.pop(), wasPlaying);
	}

	skip(e) {
		if (this.queue.length === 0) {
			this.sound.unload();
			return;
		}

		var wasPlaying = this.sound.playing();

		this.history.push(this.songId);
		this.changeSong(this.queue.shift(), (wasPlaying || e));
	}

	volume(volume) {
		this.sound.volume(volume);
	}

	async updateMusicControl() {
		var res = await $.get(`/api/musicPlayer/${this.songId}`);
		
		this.$songName.text(`${res.songArtist} - ${res.songName}`);
		this.metadata.title = res.songName;
		this.metadata.artist = res.songArtist;
		this.metadata.album = res.albumName;
		this.metadata.artwork = [{ src: res.albumArtFilepath, sizes: '512x512', type: 'image/png' }];
	}
}