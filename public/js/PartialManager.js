class PartialManager extends EventEmitter {
	static sharedInstance;

	constructor($partial, scrollableSelector) {
		if (PartialManager.sharedInstance) {
			return PartialManager.sharedInstance;
		}

		PartialManager.sharedInstance = super();
		
		this._$partial = $partial;
		this._scrollableSelector = scrollableSelector;
		this._initiatedHistory = false;

		this._initEvents();
	}

	_initEvents() {
		this._$partial
			.find(this._scrollableSelector)
			.scrollStopped(() => history.replaceState(this._getCurrentState(), "", document.URL))
		;

		$(window).on('popstate', e => {
			if (e.originalEvent.state) {
				this._updatePartial(e.originalEvent.state.html, e.originalEvent.state.scroll)
				this._emit('pathchange');
			}
		});
	}

	async loadPartial(url) {
		if (window.location.pathname === url) {
			return;
		}

		CustomContextMenu.sharedInstance.hide();
		
		if (!this._initiatedHistory) {
			history.pushState(this._getCurrentState(), "", document.URL);
			this._initiatedHistory = true;
		}
	
		this._updatePartial(
			await $.ajax(
				url,
				{
					data: { partial: true },
					statusCode: { 404: () => window.location.href = "/" }
				}
			),
			0
		);
		
		history.pushState(this._getCurrentState(), "", url);
		this._emit('pathchange');
	}

	_updatePartial(html, scroll) {
		this._emit('preupdate');
		this._$partial.removeClass('fade');
		this._$partial.css('opacity', 0);
		this._$partial.html(html);
		setTimeout(() => {
			this._$partial.waitForImages(() => {
				this._$partial.addClass('fade');
				this._$partial.css('opacity', 1);
				this._$partial.find(this._scrollableSelector)
					.scrollTop(scroll)
					.off('scroll')
					.scrollStopped(() => this.updateCurrentState())
				;
			});
		}, 0);
	}

	updateCurrentState() {
		history.replaceState(this._getCurrentState(), "", document.URL);
	}
	
	_getCurrentState() {
		var $temp = $('<div></div>');

		// Reset LazyLoad images
		$temp
			.html(this._$partial.html())
			.find('img.lazy')
			.removeClass(['entered', 'loaded'])
			.removeAttr('src')
			.removeAttr('data-ll-status')
		;

		return { html: $temp.html(), scroll: this.scrollTop() };
	}

	scrollTop() {
		return this._$partial.find(this._scrollableSelector).scrollTop();
	}
}
