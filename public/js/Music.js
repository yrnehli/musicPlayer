class Music extends Howl {
	static sharedInstance;

	constructor() {
		if (Music.sharedInstance) {
			return;
		}

		Music.sharedInstance = super({
			src: [null],
			format: 'mp3',
			volume: 0.0625,
			html5: true
		});

		this._onenable = [];
		this._ondisable = [];
		this._onskip = [];
		this._onsongchange = [];
		this._onautosongchange = [];
		this._onmanualsongchange = [];

		this.__songId = null;
		this.__disabled = true;
		this.__queue = [];
		this.__nextUp =	{ list: [], i: 0 };
		
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
		this._queue = [];
		this._sounds = [];
		this._duration = 0;
		this._sprite = {};
		this._src = `/mp3/${songId}`;
		this.load();
		
		// Play even if we want don't want to so we get the media session metadata
		this.play();
		
		if (!play) {
			this.pause();
		}

		this._emit('songchange');
		this._emit(
			(auto) ? 'autosongchange' : 'manualsongchange'
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
		
		if (this.__nextUp.list.length > 0 && this.__nextUp.i - 1 >= 0) {
			this.changeSong(this.__nextUp.list[--this.__nextUp.i], this.playing());
		} else {
			this.disable();
		}
	}

	skip(e) {
		if (this.__disabled) {
			return;
		}

		var wasPlaying = (e || this.playing() || (this._queue.some(item => item.event === "play") && !this._queue.some(item => item.event === "pause")));

		if (this.__queue.length > 0) {
			this.changeSong(this.__queue.shift(), wasPlaying, e);
		} else if (this.__nextUp.list.length > 0 && this.__nextUp.i + 1 < this.__nextUp.list.length) {
			this.changeSong(this.__nextUp.list[++this.__nextUp.i], wasPlaying, e);
		} else {
			this.disable();
		}

		this._emit('skip');
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

	on(event, fn) {
		var self = this;
		var namespace;

		if (event.includes(".")) {
			var parts = event.split(".");
			event = parts[0];
			namespace = parts[1];
		}

		var events = self['_on' + event];

		if (typeof fn === 'function') {
			events.push({ namespace: namespace, fn: fn, id: undefined });
		}

		return self;
	}

	off(event, fn) {
		var self = this;
		var namespace;

		if (event.includes(".")) {
			var parts = event.split(".");
			event = parts[0];
			namespace = parts[1];
		}

		var events = self['_on' + event];
	
		if (fn || namespace) {
			// Loop through event store and remove the passed function.
			for (var i = 0; i < events.length; i++) {
				var isNamespace = (namespace === events[i].namespace);

				if (fn === events[i].fn && isNamespace || !fn && isNamespace) {
					events.splice(i, 1);
					break;
				}
			}
		} else if (event) {
			// Clear out all events of this type.
			self['_on' + event] = [];
		} else {
			// Clear out all events of every type.
			var keys = Object.keys(self);
				for (i = 0; i < keys.length; i++) {
				if ((keys[i].indexOf('_on') === 0) && Array.isArray(self[keys[i]])) {
					self[keys[i]] = [];
				}
			}
		}
	
		return self;
	}
}