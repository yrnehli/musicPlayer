<link rel="stylesheet" href="/public/css/queue.css">

<?php

$songs = [
	[
		'id' => 'A',
		'trackNumber' => 'A',
		'name' => 'A',
		'artist' => 'A',
		'duration' => 1
	],
	[
		'id' => 'B',
		'trackNumber' => 'B',
		'name' => 'B',
		'artist' => 'B',
		'duration' => 2
	],
	[
		'id' => 'C',
		'trackNumber' => 'C',
		'name' => 'C',
		'artist' => 'C',
		'duration' => 3
	]
];

?>

<div id="root" class="px-4 pb-4" data-simplebar>
	<?= $searchResults ?>
	<div>
		<?php foreach ($songs as $song): ?>
			<div class="tracklist-row" data-song-id="<?= $song['id'] ?>" draggable="true" data-context-menu-actions="QUEUE" data-drag-counter="0">
				<div class="track-number">
					<img class="equalizer" src="/public/img/equalizer.gif">
					<svg class="play">
						<path></path>
					</svg>
					<div class="text-center">
						<?= $song['trackNumber'] ?>
					</div>
				</div>
				<div class="track-name">
					<div>
						<?= $song['name'] ?>
					</div>
					<div>
						<?= $song['artist'] ?>
					</div>
				</div>
				<div class="total-time">
					<?= secondsToTimeString($song['duration']) ?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</div>
<script>
	var tracklistRows = $('.tracklist-row').get();

	function handleDragStart(e) {
		this.classList.add('active');
		dragSource = this;
		e.dataTransfer.effectAllowed = 'move';
		e.dataTransfer.setData('text/html', this.innerHTML);
	}

	function handleDragEnd(e) {
		this.classList.remove('active');
		tracklistRows.forEach(tracklistRow => tracklistRow.classList.remove('over'));
	}
	
	function handleDragOver(e) {
		if (e.preventDefault) {
			e.preventDefault();
		}

		return false;
	}

	function handleDragEnter(e) {
		var $tracklistRow = $(this).is('.tracklist-row') ? $(this) : $(this).parents('.tracklist-row').get(0);
		
		$tracklistRow
			.addClass('over')
			.data(
				'drag-counter', 
				$tracklistRow.data('drag-counter') + 1
			)
		;
	}

	function handleDragLeave(e) {
		var $tracklistRow = $(this).is('.tracklist-row') ? $(this) : $(this).parents('.tracklist-row').get(0);

		$tracklistRow.data(
			'drag-counter', 
			$tracklistRow.data('drag-counter') - 1
		);

        if ($tracklistRow.data('drag-counter') === 0) { 
			$tracklistRow.removeClass('over');
        }
	}

	function handleDrop(e) {
		e.stopPropagation();

		var self = this;
		var tracklistRowsToShiftHtml = [];
		var selfIndex, dragSourceIndex;

		if (dragSource !== self) {
			tracklistRows.forEach((tracklistRow, i) => {
				if (tracklistRow === self) {
					selfIndex = i;
				} else if (tracklistRow === dragSource) {
					dragSourceIndex = i;
				}
			});

			if (selfIndex > dragSourceIndex) {
				for (var i = dragSourceIndex + 1; i <= selfIndex; i++) {
					tracklistRowsToShiftHtml.push(tracklistRows[i].innerHTML);
				}
				
				for (var i = dragSourceIndex, j = 0; i < selfIndex; i++, j++) {
					tracklistRows[i].innerHTML = tracklistRowsToShiftHtml[j];
				}
			} else {
				for (var i = selfIndex; i < dragSourceIndex; i++) {
					tracklistRowsToShiftHtml.push(tracklistRows[i].innerHTML);
				}
				
				for (var i = selfIndex + 1, j = 0; i <= dragSourceIndex; i++, j++) {
					tracklistRows[i].innerHTML = tracklistRowsToShiftHtml[j];
				}
			}
			
			self.innerHTML = e.dataTransfer.getData('text/html');
		}

		// update MusicPlayer.sharedInstance.queue

		return false;
	}

	tracklistRows.forEach(tracklistRow => {
		tracklistRow.addEventListener('dragstart', handleDragStart, false);
		tracklistRow.addEventListener('dragend', handleDragEnd, false);
		tracklistRow.addEventListener('drop', handleDrop, false);
		tracklistRow.addEventListener('dragover', handleDragOver, false);
		tracklistRow.addEventListener('dragenter', handleDragEnter, false);
		tracklistRow.addEventListener('dragleave', handleDragLeave, false);
	});
</script>