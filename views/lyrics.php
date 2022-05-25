<!-- <link rel="stylesheet" href="/public/css/queue.css"> -->
<style>
	#lyrics .active {
		color: white;
		font-weight: bolder;
	}

	#lyrics {
		font-size: 24px;
	}
</style>

<div id="root" class="px-4 pb-4" data-simplebar>
	<?= $searchResults ?>
	<h1>Lyrics</h1>
	<div id="lyrics">
		<?php foreach ($lyrics as $lyric): ?>
			<?php if (empty($lyric->line)): ?>
				<br>
			<?php else: ?>
				<div data-time="<?= $lyric->milliseconds ?>" data-duration="<?= $lyric->duration ?>">
					<?= $lyric->line ?>
				</div>
			<?php endif; ?>
		<?php endforeach; ?>
	</div>
</div>
<script>
	var $lyrics = $('#lyrics');

	$(function() {
		setInterval(() => {		
			const time = Math.floor(Music.sharedInstance.seek() * 1000);
			let $activeLyric;
	
			$lyrics.children().each(function() {
				const $lyric = $(this);
				const lyricTime = $lyric.data('time');
				const lyricDuration = $lyric.data('duration');

				if (lyricTime === undefined || time > lyricTime + lyricDuration) {
					return true;
				}

				if (time >= lyricTime) {
					$activeLyric = $lyric;
				} else {
					return false;
				}
			});

			$lyrics.find('.active').removeClass('active');

			if ($activeLyric) {
				$activeLyric.addClass('active');

				SimpleBar
					.instances
					.get($('[data-simplebar]').get(0))
					.getScrollElement()
					.scrollTop = $activeLyric.scrollTop();
				;

				if (Music.sharedInstance.playing()) {
					setTimeout(() => {
						if (Music.sharedInstance.playing()) {
							$activeLyric.removeClass('active');
						}
					}, $activeLyric.data('duration') + 1000);
				}
			}
		}, 500);
	});
</script>
