<?php

foreach ($songs as $song) {
	print "
		<div class='song' data-song-id='{$song['id']}'>
			{$song['songName']}
		</div>
	";
}

?>

<script>
	$(function() {
		$('.song').click(function() {
			sound.changeSong({ src: `/mp3/${$(this).data('song-id')}`});
			sound.play();
		});
	});
</script>