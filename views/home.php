<link rel="stylesheet" href="/assets/css/home.css">

<div id="root" class="py-2 px-2 d-flex" data-simplebar>
	<div class="mx-auto centre">
		<div id="searchResults" style="display: none;">
			<div class="my-3">
				<div id="songs" style="display: none;">
					<h2>Songs</h2>
					<div id="songsContainer"></div>
				</div>
			</div>
			<div class="my-3">
				<div id="albums" style="display: none;">
					<h2>Albums</h2>
					<div id="albumsContainer"></div>
				</div>
			</div>
		</div>
		<div id="albums" class="mx-auto centre">
			<?php foreach ($albums as $album): ?>
				<div class="album-container mx-2 my-2" data-album-id="<?= $album['id'] ?>" data-context-menu-actions="QUEUE,GO_TO_ALBUM">
					<img class="album-art" src="<?= $album['artFilepath'] ?>">
					<div class="title">
						<?= $album['name'] ?>
					</div>
					<div class="artist mb-2">
						<?= $album['artist'] ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>

<script>
	(function() {
		// $(function() {
		// 	initEvents();
		// });

		// function initEvents() {
		// 	$(window).keydown(e => assignKeydown(e));
		// 	$(window).keyup(e => assignKeyup(e));
		// }

		// function assignKeydown(e) {
		// 	// Ctrl + F
		// 	if (e.ctrlKey && e.keyCode === 70) {
		// 		e.preventDefault();
		// 	}
		// }

		// function assignKeyup(e) {
		// 	// Ctrl + F
		// 	if (e.ctrlKey && e.keyCode === 70) {
		// 		e.preventDefault();
		// 		$searchBar.focus();
		// 	}
		// }
	})();
</script>