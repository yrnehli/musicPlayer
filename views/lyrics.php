<link rel="stylesheet" href="/public/css/lyrics.css">
<div id="root" class="px-4 pb-4" data-simplebar>
	<?= $searchResults ?>
	<div class="my-3 mx-4">
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
</div>
<script>
	var $lyrics = $('#lyrics');

	$(function() {
		var interval = setInterval(() => {
			var r = new RegExp('.*\/lyrics\/.*', 'i');

			if (!r.test(window.location.pathname)) {
				clearInterval(interval);
				return;
			}

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

				$activeLyric.get(0).scrollIntoView({
					behavior: 'smooth',
					block: 'center',
					inline: 'center'
				});

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
