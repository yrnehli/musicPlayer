class CustomContextMenu {
	static initialised;

	constructor($contextMenu, actions) {		
		if (CustomContextMenu.initialised) {
			return;
		} else {
			CustomContextMenu.initialised = true;
		}

		$(document).on("contextmenu", e => {
			e.preventDefault();

			var $target = $(e.target).is('[data-context-menu-actions]') ? $(e.target) : $(e.target).parents('[data-context-menu-actions]').first();

			if (!$target.length) {
				return;
			}

			$contextMenu.empty();
			$target
				.data('context-menu-actions')
				.split(",")
				.forEach(action => {
					$contextMenu.append(
						$('<li></li>')
							.text(actions[action].text)
							.click(function() {
								actions[action].callback($target);
								$contextMenu.fadeOut(CustomContextMenu.FADE_DURATION);
							})
					);
				})
			;
			$contextMenu
				.fadeIn(CustomContextMenu.FADE_DURATION)
				.css({
					top: `${e.pageY}px`,
					left: (e.pageX + $contextMenu.outerWidth() > $(window).width()) ? `${e.pageX - $contextMenu.outerWidth()}px` : `${e.pageX}px`
				})
			;
		});
	
		$(document).on("mousedown", function(e) {
			if (!$(e.target).parents().get().some(parent => parent === $contextMenu.get(0))) {
				$contextMenu.fadeOut(CustomContextMenu.FADE_DURATION);
			}
		});
	}

	static get FADE_DURATION() {
		return 100;
	}
}