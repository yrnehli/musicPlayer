<!-- <link rel="stylesheet" href="/public/css/queue.css"> -->
<style>
	#lyrics .active {
		color: red;
	}
</style>

<div id="root" class="px-4 pb-4" data-simplebar>
	<?= $searchResults ?>
	<h2>Lyrics</h2>
	<div id="lyrics">
		<?php foreach ($lyrics as $lyric): ?>
			<?php if (empty($lyric->line)): ?>
				<br>
			<?php else: ?>
				<h5 data-time="<?= $lyric->milliseconds ?>">
					<?= $lyric->line ?>
				</h5>
			<?php endif; ?>
		<?php endforeach; ?>
	</div>
</div>
<script>
	const $lyrics = $('#lyrics');

	$(function() {
		setInterval(() => {		
			const time = Music.sharedInstance.seek() * 1000;
			let $activeLyric;
	
			$lyrics.children().each(function() {
				const $lyric = $(this);

				if ($lyric.data('time') === undefined) {
					return true;
				}

				if ($lyric.data('time') < time) {
					$activeLyric = $lyric;
				} else {
					return false;
				}
			});

			$lyrics.find('.active').removeClass('active');
			$activeLyric.addClass('active');
		}, 500);
	});
</script>
