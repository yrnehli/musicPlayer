class MusicPlayer extends Howl {
	constructor($musicControl) {
		super({
			src: ['/mp3/8848'],
			format: 'mp3',
			html5: true
		});

		this.queue = [];
		this.$playButton = $musicControl.find('#playButton');
		this.$songName = $musicControl.find('#songName');

		this.on('end', MusicPlayer.prototype.skip);
	}

	changeSong(songId) {
		this.unload();
		this.songId = songId;
		this._duration = 0;
		this._src = `/mp3/${songId}`;
		this.load();
		this.updateMusicControl();
	}

	play(sprite, internal) {
		super.play();
		this.$playButton
			.addClass('playing')
			.removeClass('paused')
			.text('Pause');
	}

	pause() {
		super.pause();
		this.$playButton
			.addClass('paused')
			.removeClass('playing')
			.text('Play');
	}

	enqueue(songId) {
		this.queue.push(songId);
	}

	skip() {
		console.log('skip');
	}

	async updateMusicControl() {
		var song = await $.get(`/api/musicPlayer/${this.songId}`);
		this.$songName.text(`${song.songArtist} - ${song.songName}`);
	}
}