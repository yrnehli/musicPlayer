var $partial;

$(function() {
	$partial = $('#partial');

	history.pushState(getCurrentState(), "", document.URL);

	$(window).on('popstate', e => {
		if (e.originalEvent.state) {
			$partial.html(e.originalEvent.state.html);
			$partial.waitForImages(() => $partial.find('.simplebar-content-wrapper').scrollTop(e.originalEvent.state.scroll));
		}
	});
});

async function loadPartial(url) {
	history.replaceState(getCurrentState(), "", document.URL);
	$partial.html(await $.get(url, { partial: true }));
	history.pushState(getCurrentState(), "", url);
}

function getCurrentState() {
	return { html: $partial.html(), scroll: $partial.find('.simplebar-content-wrapper').scrollTop() };
}