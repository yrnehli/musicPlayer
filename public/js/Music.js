class Music extends EventEmitter {
	static sharedInstance;

	constructor(volume) {
		if (Music.sharedInstance) {
			return Music.sharedInstance;
		}

		Music.sharedInstance = super();

		this._audio = document.createElement('audio');
		this._backup = document.createElement('audio');
		this._lastSongId = null;
		this._songId = null;
		this._disabled = true;
		this._pausing = false;
		this._queue = [];
		this._history = [];
		
		this.volume(volume);
		this.on('end', () => this.skip(true, true));
	}

	disabled() {
		return this._disabled;
	}

	songId() {
		return this._songId;
	}
	
	lastSongId() {
		return this._lastSongId;
	}

	disable() {
		this.pause(0);
		this._lastSongId = this._songId;
		this._songId = null;
		this._queue = [];
		this._history = [];
		this._disabled = true;
		this._audio.src = '';
		this._backup.src = '';
		this._emit('disable');
	}

	enable() {
		this._disabled = false;
		this._emit('enable');
	}

	async changeSong(songId, play, auto) {
		this._lastSongId = this._songId;
		this._songId = songId;
		this.enable();

		if (this._songId) {
			[this._audio, this._backup] = [this._backup, this._audio];
			this._backup.pause();
			this._audio.src = `/mp3/${songId}`;			
		} else {
			this._audio.src = `/mp3/${songId}`;
			this._backup.src = `/mp3/${songId}`;
		}
		
		// Play even if we want don't want to so we get the media session metadata
		await this.play();
		
		if (!play) {
			this.pause(0);
		}

		this._audio.onended = () => this._emit('end');
		this._emit('songchange');
		this._emit(auto ? 'autosongchange' : 'manualsongchange');
	}

	togglePlay() {
		this.playing() ? this.pause() : this.play();
	}

	async play() {
		if (this._disabled) {
			return;
		}

		await this._audio.play();
		this._emit('play');
	}

	pause(fadeOutDuration = 300) {
		if (this._disabled || this._pausing) {
			return;
		}

		this._pausing = true;
		
		const originalVolume = this._audio.volume;

		$(this._audio).animate(
			{ volume: 0 },
			{
				duration: fadeOutDuration,
				done: () => {
					this._audio.pause();
					this._audio.volume = originalVolume;
					this._pausing = false;
				}
			}
		);

		this._emit('pause');
	}

	previous(force = false) {
		if (this._disabled) {
			return;
		}

		if (!force && this.seek() >= 3) {
			this.seek(0);
			return;
		}
		
		if (this._history.length > 0) {
			if (this._songId) {
				this._queue.unshift(this._songId);
			}

			this.changeSong(this._history.pop(), this.playing());
		} else {
			this.disable();
		}
	}

	skip(play, auto) {
		if (this._queue.length > 0) {
			if (this._songId) {
				this._history.push(this._songId);
			}

			this.changeSong(
				this._queue.shift(),
				(play || this.playing()),
				auto
			);
		} else {
			this.disable();
		}

		this._emit('skip');
		this._emit(auto ? 'autoskip' : 'manualskip');
	}

	queue(queue) {
		if (queue === undefined) {
			return this._queue;
		}

		this._queue = queue;
	}

	history(history) {
		if (history === undefined) {
			return this._history;
		}

		this._history = history;
	}

	playing() {
		return !this._audio.paused;
	}

	volume(volume) {
		if (volume === undefined) {
			return this._audio.volume;
		}

		this._audio.volume = volume;
		this._backup.volume = volume;
	}

	seek(position) {
		if (position === undefined) {
			return this._audio.currentTime || 0;
		}

		this._audio.currentTime = position;
	}

	duration() {
		return this._audio.duration || 0;
	}
}
