class Music extends Howl {
	constructor() {
		super({
			src: [null],
			format: 'mp3',
			volume: 0.0625,
			html5: true
		});

		this._onenable = [];
		this._ondisable = [];
		this._onsongchangeauto = [];
		this._onsongchangemanual = [];

		this.__songId = null;
		this.__disabled = true;
		this.__queue = [];
		this.__nextUp =  { list: [], i: 0 };
		
		this.on('end', e => this.skip(e));
	}

	disabled() {
		return this.__disabled;
	}

	songId() {
		return this.__songId;
	}

	disable() {
		this.pause();
		this.__songId = null;
		this.__queue = [];
		this.__nextUp = { list: [], i: 0 };
		this.__disabled = true;
		this._emit('disable');
	}

	enable() {
		this.__disabled = false;
		this._emit('enable');
	}

	loaded() {
		return (this._state === "loaded");
	}

	async changeSong(songId, play, auto) {
		this.__songId = songId;
		this.enable();
		
		this.unload();
		this._duration = 0;
		this._sprite = {};
		this._src = `/api/mp3/${songId}`;
		this.load();
		
		// Play even if we want don't want to so we get the media session metadata
		this.play();
		
		if (!play) {
			this.pause();
		}

		this._emit(
			(auto) ? 'songchangeauto' : 'songchangemanual'
		);
	}

	togglePlay() {
		this.playing() ? this.pause() : this.play();
	}

	play() {
		if (!this.__disabled) {
			super.play();
		} else {
			return;
		}
	}

	pause() {
		if (!this.__disabled) {
			super.pause();
		} else {
			return;
		}
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
			this.changeSong(this.__queue.shift(), wasPlaying, e);
		} else if (this.__nextUp.list.length > 0 && this.__nextUp.i + 1 < this.__nextUp.list.length) {
			this.changeSong(this.__nextUp.list[++this.__nextUp.i], wasPlaying, e);
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
}