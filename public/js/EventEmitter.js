class EventEmitter {
	constructor() {
		this._events = {};
		this._queue = [];
	}

	on(event, fn) {
		var self = this;
		var namespace;

		if (event.includes(".")) {
			[event, namespace] = event.split(".");
		}

		var events = self['_on' + event];

		if (!events) {
			events = self['_on' + event] = [];
		}

		if (typeof fn === 'function') {
			events.push({namespace: namespace, fn: fn, id: undefined});
		}

		return self;
	}

	off(event, fn) {
		var self = this;

		if (event.includes(".")) {
			var [event, namespace] = event.split(".");
		}

		var events = self['_on' + event] || [];

		if (fn || namespace) { // Loop through event store and remove the passed function.
			for (var i = 0; i < events.length; i++) {
				var isNamespace = (namespace === events[i].namespace);

				if (fn === events[i].fn && isNamespace || !fn && isNamespace) {
					events.splice(i, 1);
					break;
				}
			}
		} else if (event) { // Clear out all events of this type.
			self['_on' + event] = [];
		} else { // Clear out all events of every type.
			var keys = Object.keys(self);
			for (i = 0; i < keys.length; i ++) {
				if ((keys[i].indexOf('_on') === 0) && Array.isArray(self[keys[i]])) {
					self[keys[i]] = [];
				}
			}
		}

		return self;
	}

	_emit(event, id, msg) {
		var self = this;
		var events = self['_on' + event] || [];

		// Loop through event store and fire all functions.
		for (var i = events.length - 1; i >= 0; i--) { // Only fire the listener if the correct ID is used.
			if (!events[i].id || events[i].id === id || event === 'load') {
				setTimeout(function (fn) {
					fn.call(this, id, msg);
				}.bind(self, events[i].fn), 0);

				// If this event was setup with `once`, remove it.
				if (events[i].once) {
					self.off(event, events[i].fn, events[i].id);
				}
			}
		}

		// Pass the event type into load queue so that it can continue stepping.
		self._loadQueue(event);

		return self;
	}

	_loadQueue(event) {
		var self = this;

		if (self._queue.length > 0) {
			var task = self._queue[0];

			// Remove this task if a matching event was passed.
			if (task.event === event) {
				self._queue.shift();
				self._loadQueue();
			}

			// Run the task if no event type is passed.
			if (!event) {
				task.action();
			}
		}

		return self;
	}
}
