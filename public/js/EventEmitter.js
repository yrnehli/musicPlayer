class EventEmitter {
	constructor() {
		this._events = {};
	}
	
	on(name, listener) {
		if (!this._events[name]) {
			this._events[name] = [];
		}
	
		this._events[name].push(listener);

		return this;
	}
	
	off(name, listenerToRemove) {
		if (!this._events[name]) {
			throw new Error(`Can't remove a listener. Event "${name}" doesn't exits.`);
		}
	
		const filterListeners = (listener) => listener !== listenerToRemove;
	
		this._events[name] = this._events[name].filter(filterListeners);

		return this;
	}
	
	_emit(name, data) {
		if (!this._events[name]) {
			throw new Error(`Can't emit an event. Event "${name}" doesn't exits.`);
		}
	
		const fireCallbacks = (callback) => {
			callback(data);
		};
	
		this._events[name].forEach(fireCallbacks);

		return this;
	}
}