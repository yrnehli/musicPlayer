<?php

foreach ($albums as $album) {
	print "
		<div class='album' data-album-id='{$album['id']}'>
			{$album['albumName']}
		</div>
	";
}

?>

<script>
	Howl.prototype.changeSong = function(o) {
		var self = this;
		self.unload();
		self._duration = 0;
		self._sprite = {};
		self._src = typeof o.src !== 'string' ? o.src : [o.src];
		self._format = typeof o.format !== 'string' ? o.format : [o.format];
		self.load();
	};

	sound = new Howl({
		src: ['/mp3/9000'],
		format: ['mp3'],
		html5: true
	});

	$(function() {
		$('.album').click(function() {
			loadPartial(`/album/${$(this).data('album-id')}`);
		});
	});
</script>