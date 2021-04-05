<?php foreach ($songs as $song): ?>
	<div class='song' data-song-id='<?= $song['id'] ?>'>
		<?= $song['songName'] ?>
	</div>
<?php endforeach; ?>

<script>
	$(function() {
		$('.song').dblclick(function() {
			var $self = $(this);
			var album = { list: [], i: 0 };

			$(this).parent().find('.song').each(function(i) {
				album.list.push($(this).data('song-id'));

				if ($(this).get(0) === $self.get(0)) {
					album.i = i;
				}
			});
			
			musicPlayer.queue([]);
			musicPlayer.history([]);
			musicPlayer.album(album);
			musicPlayer.changeSong($self.data('song-id'), true);
		});
	});
</script>