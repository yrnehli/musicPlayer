class PartialManager {
	static sharedInstance;

	constructor($partial) {
		if (PartialManager.sharedInstance) {
			return;
		} else {
			PartialManager.sharedInstance = this;
		}

		this.initiatedHistory = false;
		this.$partial = $partial;
	}

	initEvents() {
		this
			.$partial
			.find('.simplebar-content-wrapper')
			.scrollStopped(() => history.replaceState(this.getCurrentState(), "", document.URL))
		;

		$(window).on('popstate', e => {
			if (e.originalEvent.state) {
				this.updatePartial(e.originalEvent.state.html, e.originalEvent.state.scroll)
			}
		});
	}

	async loadPartial(url) {
		if (window.location.pathname === url) {
			return;
		}

		CustomContextMenu.sharedInstance.hide();
		
		if (!this.initiatedHistory) {
			history.pushState(this.getCurrentState(), "", document.URL);
			this.initiatedHistory = true;
		}
	
		this.updatePartial(
			await $.ajax(
				url,
				{
					data: { partial: true },
					statusCode: { 404: () => location.reload() }
				}
			),
			0
		);
		
		SearchHandler.sharedInstance.reset();
		history.pushState(this.getCurrentState(), "", url);
	}

	updatePartial(html, scroll) {
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
		});
	}

	updateCurrentState() {
		history.replaceState(this.getCurrentState(), "", document.URL);
	}
	
	getCurrentState() {
		return { html: this.$partial.html(), scroll: this.$partial.find('.simplebar-content-wrapper').scrollTop() };
	}
}