<link rel="stylesheet" href="/public/css/queue.css">

<div id="root" class="px-4 pb-4" data-simplebar>
	<?= $searchResults ?>
	<h2>Queue</h2>
	<div id="queueRowsContainer"></div>
</div>
<script>
	var $queueRowsContainer = $('#queueRowsContainer');
	var queueRows;

	$(async function() {
		queueRows = $queueRowsContainer
			.append(
				await Promise.all(
					MusicControl.sharedInstance.music().queue().map(songId => createQueueRow(songId))
				)
			)
			.children()
			.get()
		;

		queueRows.forEach(queueRow => {
			queueRow.addEventListener('dragstart', handleDragStart, false);
			queueRow.addEventListener('dragend', handleDragEnd, false);
			queueRow.addEventListener('dragover', handleDragOver, false);
			queueRow.addEventListener('dragenter', handleDragEnter, false);
			queueRow.addEventListener('dragleave', handleDragLeave, false);
			queueRow.addEventListener('drop', handleDrop, false);
		});
	});

	function handleDragStart(e) {
		dragSource = this;

		this.classList.add('active');

		const DRAG_GHOST_ID = 'dragGhost';
		var songName = $(this).find('.details').children().eq(0).text();
		var artistName = $(this).find('.details').children().eq(1).text();
		
		document.getElementById(DRAG_GHOST_ID)?.remove();

		var dragGhost = $('<div></div>')
			.attr('id', DRAG_GHOST_ID)
			.html(`${songName} · ${artistName}`)
			.css({
				position: 'absolute',
				top: '-1000px'
			})
			.get(0)
		;

		document.body.appendChild(dragGhost);
		e.dataTransfer.setDragImage(dragGhost, -10, 0);
		e.dataTransfer.effectAllowed = 'move';
		e.dataTransfer.setData(
			'text/plain',
			JSON.stringify({
				html: this.innerHTML,
				songId: this.getAttribute('data-song-id')
			})
		);
	}

	function handleDragEnd(e) {
		this.classList.remove('active');
		queueRows.forEach(tracklistRow => tracklistRow.classList.remove('over-above', 'over-below'));
	}
	
	function handleDragOver(e) {
		if (e.preventDefault) {
			e.preventDefault();
		}

		return false;
	}

	function handleDragEnter(e) {
		var thisIndex, dragSourceIndex;

		queueRows.forEach((tracklistRow, i) => {
			if (tracklistRow === this) {
				thisIndex = i;
			} else if (tracklistRow === dragSource) {
				dragSourceIndex = i;
			}
		});

		(thisIndex > dragSourceIndex) ? this.classList.add('over-below') : this.classList.add('over-above');
	}

	function handleDragLeave(e) {
		this.classList.remove('over-above', 'over-below');
	}

	function handleDrop(e) {
		e.stopPropagation();

		var queueRowsToShift = [];
		var thisIndex, dragSourceIndex;

		if (dragSource !== self) {
			queueRows.forEach((tracklistRow, i) => {
				if (tracklistRow === this) {
					thisIndex = i;
				} else if (tracklistRow === dragSource) {
					dragSourceIndex = i;
				}
			});

			if (thisIndex > dragSourceIndex) {
				for (var i = dragSourceIndex + 1; i <= thisIndex; i++) {
					queueRowsToShift.push({
						html: queueRows[i].innerHTML,
						songId: queueRows[i].getAttribute('data-song-id')
					});
				}
				
				for (var i = dragSourceIndex, j = 0; i < thisIndex; i++, j++) {
					queueRows[i].innerHTML = queueRowsToShift[j].html;
					queueRows[i].setAttribute('data-song-id', queueRowsToShift[j].songId);
				}
			} else {
				for (var i = thisIndex; i < dragSourceIndex; i++) {
					queueRowsToShift.push({
						html: queueRows[i].innerHTML,
						songId: queueRows[i].getAttribute('data-song-id')
					});
				}
				
				for (var i = thisIndex + 1, j = 0; i <= dragSourceIndex; i++, j++) {
					queueRows[i].innerHTML = queueRowsToShift[j].html;
					queueRows[i].setAttribute('data-song-id', queueRowsToShift[j].songId);
				}
			}
			
			var data = JSON.parse(e.dataTransfer.getData('text/plain'));
			this.setAttribute('data-song-id', data.songId);
			this.innerHTML = data.html;
		}

		queueRows = $queueRowsContainer.children().get();

		MusicControl.sharedInstance.music().queue(
			queueRows.map(queueRow => queueRow.getAttribute('data-song-id'))
		);

		return false;
	}

	async function createQueueRow(songId) {
		var res = await $.get(`/api/song/${songId}`);
		var $queueRow = $(`<div class="music-row" draggable="true" data-song-id=${songId} data-album-id=${res.albumId} data-context-menu-actions="REMOVE_FROM_QUEUE" data-activable></div>`);
		var $img = $('<img>').prop('src', res.albumArtUrl);
		var $artwork = $('<div class="artwork"></div>').append($img);
		var $totalTime = $('<div class="total-time"></div>').text(secondsToTimeString(res.songDuration));
		var $details = $('<div class="details"></div>').append([
			$('<div></div>').text(res.songName),
			$('<div></div>').text(res.songArtist),
		]);

		$queueRow.append([
			$('<div></div>').append([$artwork, $details]),
			$totalTime
		]);

		return $queueRow;
	}
</script>