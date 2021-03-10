<div id="aaa">yes</div>

<script>

	// audioTag = document.createElement('audio');
	// document.body.appendChild(this.audioTag);
	// audioTag.src = "https://raw.githubusercontent.com/anars/blank-audio/master/10-seconds-of-silence.mp3";
	// audioTag.loop = true;
	// audioTag.play();

	
	// if ('mediaSession' in navigator) {
	// 	navigator.mediaSession.metadata = new MediaMetadata({
	// 		title: 'Unforgettable',
	// 		artist: 'Nat King Cole',
	// 		album: 'The Ultimate Collection (Remastered)',
	// 		artwork: [
	// 			{ src: 'https://dummyimage.com/96x96',   sizes: '96x96',   type: 'image/png' },
	// 			{ src: 'https://dummyimage.com/128x128', sizes: '128x128', type: 'image/png' },
	// 			{ src: 'https://dummyimage.com/192x192', sizes: '192x192', type: 'image/png' },
	// 			{ src: 'https://dummyimage.com/256x256', sizes: '256x256', type: 'image/png' },
	// 			{ src: 'https://dummyimage.com/384x384', sizes: '384x384', type: 'image/png' },
	// 			{ src: 'https://dummyimage.com/512x512', sizes: '512x512', type: 'image/png' },
	// 		]
	// 	});

	// 	navigator.mediaSession.setActionHandler('play', function() { audioTag.play(); sound.play(); });
	// 	navigator.mediaSession.setActionHandler('pause', function() { audioTag.pause(); sound.pause(); });
	// }

	sound = new Howl({
		src: ['/mp3/23.mp3'],
		html5: true
	});

	// sound.play();



	$(function() {
		$('#aaa').click(() => {
			sound.play();
			// loadPartial("/test");
		});
	});
</script>