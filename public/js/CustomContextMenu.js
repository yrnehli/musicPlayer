class CustomContextMenu {
	static sharedInstance;

	constructor($contextMenu, actions) {		
		if (CustomContextMenu.sharedInstance) {
			return;
		} else {
			CustomContextMenu.sharedInstance = this;
		}

		this.$contextMenu = $contextMenu;

		this.initEvents(actions);
	}

	initEvents(actions) {
		$(document).on("contextmenu", e => {
			e.preventDefault();

			var $target = $(e.target).is('[data-context-menu-actions]') ? $(e.target) : $(e.target).parents('[data-context-menu-actions]').first();

			if (!$target.length) {
				return;
			}

			this.$contextMenu.empty();

			$target
				.data('context-menu-actions')
				.split(",")
				.forEach(action => {
					this.$contextMenu.append(
						$('<li></li>')
							.text(actions[action].text)
							.click(() => {
								actions[action].callback($target);
								this.hide();
							})
					);
				})
			;

			this.$contextMenu
				.fadeIn(CustomContextMenu.FADE_DURATION)
				.css({
					top: `${e.pageY + 8}px`,
					left: (e.pageX + this.$contextMenu.outerWidth() > $(window).width()) ? `${e.pageX - this.$contextMenu.outerWidth()}px` : `${e.pageX}px`
				})
			;

			$('#root').on('scroll touchmove mousewheel', function(e){
				e.preventDefault();
				e.stopPropagation();
				return false;
			});
		});
	
		$(document).on("mousedown", e => {
			if (!$(e.target).parents().get().some(parent => parent === this.$contextMenu.get(0))) {
				this.hide();
			}
		});
	}

	hide() {
		$('#root').off('scroll touchmove mousewheel');
		this.$contextMenu.fadeOut(CustomContextMenu.FADE_DURATION);
	}

	static get FADE_DURATION() {
		return 100;
	}
}