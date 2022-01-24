<link rel="stylesheet" href="/public/css/wrapped.css">
<div id="root" class="px-4 d-flex" data-simplebar>
	<?= $searchResults ?>
	<div id="wrapped" style="display: none;">
		<div class="row">
			<div class="col-6 d-flex">
				<div class="ml-auto">
					<img id="mainAlbumArt" class="mb-4" src="<?= $mainAlbumArt ?>">
					<h2 class="m-0">
						Minutes Listened
					</h2>
					<h1>
						<?= $minutes ?>
					</h1>
					<h2 class="m-0">
						Scrobbles
					</h2>
					<h1>
						<?= $scrobbles ?>
					</h1>
				</div>
			</div>
			<div class="col-6 d-flex">
				<div class="mr-auto">
					<div>
						<h1>
							Top Artists
						</h1>
						<?php foreach ($artists as $artist): ?>
							<h2 class="m-0">
								<?= $artist ?>
							</h2>
						<?php endforeach; ?>
					</div>
					<hr style="visibility: hidden">
					<div>
						<h1>
							Top Albums
						</h1>
						<?php foreach ($albums as $album): ?>
							<h2 class="m-0">
								<?= $album ?>
							</h2>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script>
	(function() {
		$(function() {
			updateBodyColour('<?= $accentColour?>');
			centreWrapped();
			$(window).resize(() => centreWrapped());
		});

		function centreWrapped() {
			var $root = $('#root');
			var $wrapped = $('#wrapped');

			if ($wrapped.height() < $root.height()) {
				$wrapped.css(
					'padding-top', 
					($root.height() - $wrapped.height()) / 2
				);
			}

			$wrapped.show();
		}
	})();
</script>
