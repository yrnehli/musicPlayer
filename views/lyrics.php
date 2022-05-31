<link rel="stylesheet" href="/public/css/lyrics.css"><div id="root" class="px-4 pb-4" data-simplebar>
	<div class="my-3 mx-4">
		<div class="mb-4">
			<h1 class="mb-0">
				<?= $song['songName'] ?>
			</h1>
			<h6>
				<?= $song['songArtist'] ?>
			</h6>
		</div>
		<?php if (empty($lyrics)): ?>
			<h5>No lyrics available</h5>
		<?php endif; ?>
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
			let i = 0;
			let interval = setInterval(() => {
				const r = new RegExp('.*\/lyrics\/.*', 'i');
	
				if (!r.test(window.location.pathname)) {
					clearInterval(interval);
					return;
				}

				if (String(Music.sharedInstance.songId()) !== "<?= $song['songId'] ?>") {
					return;
				}
	
				const time = Music.sharedInstance.seek() * 1000;
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
				
				if ($activeLyric) {
					$lyrics.find('.active').removeClass('active');
					$activeLyric.addClass('active');
	
					if (Music.sharedInstance.playing()) {
						if (i % 4 === 0) {
							$activeLyric.get(0).scrollIntoView({
								behavior: 'smooth',
								block: 'center',
								inline: 'center'
							});
						}
	
						setTimeout(() => {
							if (Music.sharedInstance.playing() || !Music.sharedInstance.songId()) {
								$activeLyric.removeClass('active');
							}
						}, $activeLyric.data('duration') + 1000);
					}
				}

				i++;
			}, 250);
		}
	})();
</script>
