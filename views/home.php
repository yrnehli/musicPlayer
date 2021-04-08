<link rel="stylesheet" href="/assets/css/home.css">

<div id="root" class="py-2 px-2 d-flex" data-simplebar>
	<div class="mx-auto centre">
		<?php foreach ($albums as $album): ?>
			<div class="album-container mx-2 my-2" data-album-id="<?= $album['id'] ?>">
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

<script>
	$(function() {
		$('.album-container').click(function() {
			partialManager.loadPartial(`/album/${$(this).data('album-id')}`);
		});
	});
</script>