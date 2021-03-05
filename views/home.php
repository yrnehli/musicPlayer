<div>
	yes
</div>

<script>
	sound = new Howl({
		src: ['/Songs/3am.mp3']
	});

	sound.on('end', function(){
		console.log('Finished!');
	});

	sound.play();
</script>