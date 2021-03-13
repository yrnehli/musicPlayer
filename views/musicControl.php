<div id="musicControl" class="fixed-bottom">
	<div id="songName"></div>
	<div id="playButton"></div>
</div>

<script>
	var musicPlayer = new MusicPlayer($('#musicControl'));

	$('#playButton').click(function() {
		if ($(this).hasClass('playing'))
			musicPlayer.pause();
		else
			musicPlayer.play();
	});

</script>