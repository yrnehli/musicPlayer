class PartialManager {
	constructor($partial) {
		this.initiatedHistory = false;
		this.$partial = $partial;
		this.$partial.find('.simplebar-content-wrapper').scrollStopped(() => history.replaceState(this.getCurrentState(), "", document.URL));

		$(window).on('popstate', e => {
			if (e.originalEvent.state) {
				this.updatePartial(e.originalEvent.state.html, e.originalEvent.state.scroll)
			}
		});
	}

	async loadPartial(url, selectorToFocus) {
		if (window.location.pathname === url) {
			return;
		}
		
		if (!this.initiatedHistory) {
			history.pushState(this.getCurrentState(), "", document.URL);
			this.initiatedHistory = true;
		}
	
		this.updatePartial(await $.get(url, { partial: true }), 0, selectorToFocus);
		history.pushState(this.getCurrentState(), "", url);
	}

	updatePartial(html, scroll, selectorToFocus) {
		updateTitleBarColour('#121212');
		this.$partial.removeClass('fade');
		this.$partial.css('opacity', 0);
		this.$partial.html(html);
		this.$partial.waitForImages(() => {
			this.$partial.addClass('fade');
			this.$partial.css('opacity', 1);
			this.$partial.find('.simplebar-content-wrapper')
				.scrollTop(scroll)
				.off('scroll')
				.scrollStopped(() => this.updateCurrentState())
			;
			if (selectorToFocus) {
				this.$partial.find(selectorToFocus).focus();
			}
		});
	}

	updateCurrentState() {
		history.replaceState(this.getCurrentState(), "", document.URL);
	}
	
	getCurrentState() {
		return { html: this.$partial.html(), scroll: this.$partial.find('.simplebar-content-wrapper').scrollTop() };
	}
}