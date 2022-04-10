class CustomContextMenu {
	static sharedInstance;

	constructor($contextMenu, $scrollable, actions) {		
		if (CustomContextMenu.sharedInstance) {
			return;
		}
		
		CustomContextMenu.sharedInstance = this;

		this._$contextMenu = $contextMenu;
		this._$scrollable = $scrollable;
		this._actions = actions;

		this._initEvents();
	}

	_initEvents() {
		$(document).on("contextmenu", e => {
			e.preventDefault();

			var $target = $(e.target).is(`[data-${CustomContextMenu.CONTEXT_MENU_ACTIONS_DATA_SUFFIX}]`)
				? $(e.target)
				: $(e.target).parents(`[data-${CustomContextMenu.CONTEXT_MENU_ACTIONS_DATA_SUFFIX}]`).first()
			;

			if (!$target.length) {
				return;
			}

			this._createActions($target);
			this._suppressScroll();
			this._show(
				`${e.pageY + 8}px`,
				(e.pageX + this._$contextMenu.outerWidth() > $(window).width()) ? `${e.pageX - this._$contextMenu.outerWidth()}px` : `${e.pageX}px`
			);
		});
	
		$(document).on("mousedown", e => {
			if (!$(e.target).parents().get().some(parent => parent === this._$contextMenu.get(0))) {
				this.hide();
			}
		});
	}

	_createActions($target) {
		var $targets = $target.siblings('.active').addBack();

		this._$contextMenu.empty();

		$target
			.data(CustomContextMenu.CONTEXT_MENU_ACTIONS_DATA_SUFFIX)
			.split(",")
			.forEach(action => {
				this._$contextMenu.append(
					$('<li></li>')
						.html(this._actions[action].html)
						.click(() => {
							this._actions[action].callback($target, $targets);
							this.hide();
						})
				);
			})
		;
	}

	_suppressScroll() {
		this._$scrollable.on('scroll.suppress touchmove.suppress mousewheel.suppress', e => {
			e.preventDefault();
			e.stopPropagation();
			return false;
		});
	}

	_unsuppressScroll() {
		this._$scrollable.off('scroll.suppress touchmove.suppress mousewheel.suppress');
	}

	_show(top, left) {
		this._$contextMenu
			.fadeIn(CustomContextMenu.FADE_DURATION)
			.css({ top: top, left: left })
		;
	}

	hide() {
		this._unsuppressScroll();
		this._$contextMenu.fadeOut(CustomContextMenu.FADE_DURATION);
	}

	static get FADE_DURATION() {
		return 100;
	}

	static get CONTEXT_MENU_ACTIONS_DATA_SUFFIX() {
		return 'context-menu-actions';
	}
}
