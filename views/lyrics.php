<link rel="stylesheet" href="/public/css/lyrics.css">
<div id="root" class="px-4 pb-4" data-simplebar>
	<?= $searchResults ?>
	<div class="my-3 mx-4">
		<h1>Lyrics</h1>
		<div id="lyrics">
			<?php foreach ($lyrics as $lyric): ?>
				<?php if (!empty($lyric->line)): ?>
					<div data-time="<?= $lyric->milliseconds ?>" data-duration="<?= $lyric->duration ?>">
						<?= $lyric->line ?>
					</div>
				<?php else: ?>
					<br>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
	</div>
</div>
<script>
	(function() {
		const $lyrics = $('#lyrics');

		$(function() {
			<?php if ($accentColour): ?>
				updateBodyColour('<?= $accentColour ?>');
			<?php endif; ?>
			initEvents();
		});

		function initEvents() {
			var interval = setInterval(() => {
				const r = new RegExp('.*\/lyrics\/.*', 'i');
	
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
	
					if (Music.sharedInstance.playing()) {
						$activeLyric.get(0).scrollIntoView({
							behavior: 'smooth',
							block: 'center',
							inline: 'center'
						});
	
						setTimeout(() => {
							if (Music.sharedInstance.playing()) {
								$activeLyric.removeClass('active');
							}
						}, $activeLyric.data('duration') + 1000);
					}
				}
			}, 500);
		}
	})();
</script>
