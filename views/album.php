<?php

foreach ($songs as $song) {
	print "
		<div class='song' data-song-id='{$song['id']}'>
			{$song['name']}
		</div>
	";
}

?>

<script>
	$(function() {
		$('.song').dblclick(async function() {
			musicPlayer.changeSong($(this).data('song-id'));
			musicPlayer.play();
		});
	});
</script>
