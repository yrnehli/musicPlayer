class Music extends Howl {
	static sharedInstance;

	constructor(volume) {
		if (Music.sharedInstance) {
			return;
		}

		Music.sharedInstance = super({
			src: [null],
			format: 'mp3',
			volume: volume || 0.0625,
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
		this.__history = [];
		
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
		this.__history = [];
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
		
		if (this.__history.length > 0) {
			if (this.__songId) {
				this.__queue.unshift(this.__songId);
			}
			this.changeSong(this.__history.pop(), this.playing());
		} else {
			this.disable();
		}
	}

	skip(e) {
		var wasPlaying = (e || this.playing() || (this._queue.some(item => item.event === "play") && !this._queue.some(item => item.event === "pause")));

		if (this.__queue.length > 0) {
			this.enable();
			if (this.__songId) {
				this.__history.push(this.__songId);
			}
			this.changeSong(this.__queue.shift(), wasPlaying, e);
		} else {
			this.disable();
		}

		this._emit('skip');
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