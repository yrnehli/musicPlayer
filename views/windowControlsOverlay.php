<div id="windowControlsOverlay">
	<div style="<?= ($os['os_family'] === 'macintosh') ? 'margin-left: 5rem;' : null ?>">
		<i id="searchIcon" class="fal fa-search"></i>
		<input type="text" id="searchBar" placeholder="Search" spellcheck="false">
		<i id="clearSearchBarButton" class="fal fa-times" style="visibility: hidden;"></i>
	</div>
	<button id="shuffleAllButton" type="button" class="my-auto pl-1">
		<svg class="my-auto mr-2 shuffle" height="16" width="16">
			<path></path>
		</svg>
		Shuffle
	</button>
</div>
