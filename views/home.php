<?php

foreach ($albums as $album) {
	print "
		<div class='album' data-album-id='{$album['id']}'>
			{$album['name']}
		</div>
	";
}

?>

<script>
	$(function() {
		$('.album').click(function() {
			loadPartial(`/album/${$(this).data('album-id')}`);
		});
	});
</script>
