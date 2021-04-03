<link rel="stylesheet" href="/assets/css/home.css">

<div id="root" class="py-3 px-3 d-flex" data-simplebar>
	<div class="mx-auto centre">
		<?php foreach ($albums as $album): ?>
			<div class="album-container mx-2 my-2" data-album-id="<?= $album['id'] ?>">
				<img class="art" src="<?= $album['albumArtFilepath'] ?>">
				<div class="title">
					<?= $album['albumName'] ?>
				</div>
				<div class="artist mb-2">
					<?= $album['albumArtist'] ?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</div>

<script>
	$(function() {
		$('.album-container').click(function() {
			loadPartial(`/album/${$(this).data('album-id')}`);
		});
	});
</script>