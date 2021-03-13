class MusicPlayer {
	constructor($musicControl) {
		this.sound = new Howl({
			src: [null],
			format: 'mp3',
			html5: true
		});

		this.queue = [];
		this.$playButton = $musicControl.find('#playButton');
		this.$songName = $musicControl.find('#songName');
		this.sound.on('end', e => this.skip(e));
	}

	changeSong(songId) {
		this.songId = songId;
		this.sound.unload();
		this.sound._duration = 0;
		this.sound._src = `/mp3/${songId}`;
		this.sound.load();
		this.updateMusicControl();
	}

	togglePlay() {
		if (this.sound.playing())
			this.pause();
		else
			this.play();
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

	skip(e) {
		if (this.queue.length === 0)
			return;

		this.changeSong(this.queue.pop());
		
		if (e)
			this.play();
	}

	async updateMusicControl() {
		var res = await $.get(`/api/musicPlayer/${this.songId}`);
		this.$songName.text(`${res.songArtist} - ${res.songName}`);
	}
}