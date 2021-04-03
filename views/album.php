<?php foreach ($songs as $song): ?>
	<div class='song' data-song-id='<?= $song['id'] ?>'>
		<?= $song['songName'] ?>
	</div>
<?php endforeach; ?>

<script>
	$(function() {
		$('.song').dblclick(function() {
			musicPlayer.queue = [];
			musicPlayer.history = [];

			musicPlayer.changeSong(
				$(this).data('song-id'),
				true
			);

			$(this).nextAll('.song').each(function() {
				musicPlayer.enqueue(
					$(this).data('song-id')
				);
			});
		});
	});
</script>