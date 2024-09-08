<link rel="stylesheet" href="/public/css/queue.css">
<div id="root" class="px-4 pb-4" data-simplebar>
		<h2>Queue</h2>
	<div class="mb-3" id="nowPlaying" style="display: none;">
		<h3>Now Playing</h3>
		<div class="music-row" data-activable>
			<div>
				<div class="artwork">
					<img>
				</div>
				<div class="details">
					<div></div>
					<div></div>
				</div>
			</div>
			<div class="total-time"></div>
		</div>
	</div>
	<div class="mb-3" id="nextUp" style="display: none;">
		<div class="d-flex mb-2">
			<h3 class="my-auto">Next up</h3>
			<div id="clearQueueButton" class="btn btn-spotify ml-auto mr-2">Clear</div>
		</div>
		<div id="queueRowsContainer"></div>
	</div>
</div>
<script>
	let $queueRowsContainer = $('#queueRowsContainer');
	let $nowPlaying = $('#nowPlaying');
	let $nextUp = $('#nextUp');
	let $clearQueueButton = $('#clearQueueButton');

	$(async function() {
		$queueRowsContainer.empty();
		updateNowPlaying();
		updateQueueRows();
		initEvents();
	});

	async function updateQueueRows() {
		if (window.location.pathname !== "/queue") {
			return;
		}

		let songIds = Music.sharedInstance.queue();
		let queueRowSongIds = $queueRowsContainer.children().get().map(queueRow => String(queueRow.dataset.songId));

		if (songIds.length > 50) {
			songIds = songIds.slice(0, 49);
		}

		let $queueRows = await Promise.all(
			songIds.filter(x => !queueRowSongIds.includes(String(x))).map(songId => createQueueRow(songId))
		);

		$queueRows.forEach($queueRow => {
			let queueRow = $queueRow.get()[0];
			queueRow.addEventListener('dragstart', handleDragStart, false);
			queueRow.addEventListener('dragend', handleDragEnd, false);
			queueRow.addEventListener('dragover', handleDragOver, false);
			queueRow.addEventListener('dragenter', handleDragEnter, false);
			queueRow.addEventListener('dragleave', handleDragLeave, false);
			queueRow.addEventListener('drop', handleDrop, false);
		});

		if ($queueRows.length) {
			($queueRows[0].data('song-id') == Music.sharedInstance.queue()[0]) ? $queueRowsContainer.prepend($queueRows) : $queueRowsContainer.append($queueRows);
		}

		($queueRowsContainer.children().length) ? $nextUp.show() : $nextUp.hide();

		new LazyLoad({});

		setTimeout(updateQueueRows, 500);
	}

	async function initEvents() {
		Music.sharedInstance.off('songchange.queue').on('songchange.queue', () => updateNowPlaying());
		Music.sharedInstance.off('disable.queue').on('disable.queue', () => updateNowPlaying());
		Music.sharedInstance.off('skip.queue').on('skip.queue', () => {
			$queueRowsContainer.children().first().remove();
			
			if ($queueRowsContainer.children().length === 0) {
				$nextUp.hide();
			}
		});
		$clearQueueButton.click(() => {
			$nextUp.hide();
			Music.sharedInstance.queue([]);
			$queueRowsContainer.empty();
		})
	}

	async function updateNowPlaying() {
		if (!Music.sharedInstance.songId()) {
			$nowPlaying.hide();
			return;
		}

		let res = await $.get(`/api/song/${Music.sharedInstance.songId()}`);
		let $musicRow = $nowPlaying.find('.music-row');

		$musicRow.find('.artwork img').attr('src', res.data.albumArtUrl);
		$musicRow.find('.details').children().eq(0).text(res.data.songName);
		$musicRow.find('.details').children().eq(1).text(res.data.songArtist);
		$musicRow.find('.total-time').text(secondsToTimeString(res.data.songDuration));
		$nowPlaying.show();
	}

	function handleDragStart(e) {
		dragSource = this;

		this.classList.add('active');

		const DRAG_GHOST_ID = 'dragGhost';
		let songName = $(this).find('.details').children().eq(0).text();
		let artistName = $(this).find('.details').children().eq(1).text();

		document.getElementById(DRAG_GHOST_ID)?.remove();

		let dragGhost = $('<div></div>')
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
		$queueRowsContainer.children().each(function() {
			this.classList.remove('over-above', 'over-below')
		});
	}

	function handleDragOver(e) {
		if (e.preventDefault) {
			e.preventDefault();
		}

		return false;
	}

	function handleDragEnter(e) {
		let self = this;
		let thisIndex, dragSourceIndex;

		$queueRowsContainer.children().each(function(i) {
			if (this === self) {
				thisIndex = i;
			} else if (this === dragSource) {
				dragSourceIndex = i;
			}
		});

		(thisIndex > dragSourceIndex) ? this.classList.add('over-below'): this.classList.add('over-above');
	}

	function handleDragLeave(e) {
		this.classList.remove('over-above', 'over-below');
	}

	function handleDrop(e) {
		e.stopPropagation();

		let self = this;
		let queue = Music.sharedInstance.queue();
		let queueRows = $queueRowsContainer.children().get();
		let queueRowsToShift = [];
		let thisIndex, dragSourceIndex, temp;

		if (dragSource !== self) {
			queueRows.forEach((tracklistRow, i) => {
				if (tracklistRow === this) {
					thisIndex = i;
				} else if (tracklistRow === dragSource) {
					dragSourceIndex = i;
				}
			});

			temp = queue[dragSourceIndex];
			queue[dragSourceIndex] = queue[thisIndex];
			queue[thisIndex] = temp;

			if (thisIndex > dragSourceIndex) {
				for (let i = dragSourceIndex + 1; i <= thisIndex; i++) {
					queueRowsToShift.push({
						html: queueRows[i].innerHTML,
						songId: queueRows[i].getAttribute('data-song-id')
					});
				}

				for (let i = dragSourceIndex, j = 0; i < thisIndex; i++, j++) {
					queue[i] = queueRowsToShift[j].songId;
					queueRows[i].innerHTML = queueRowsToShift[j].html;
					queueRows[i].setAttribute('data-song-id', queueRowsToShift[j].songId);
				}
			} else {
				for (let i = thisIndex; i < dragSourceIndex; i++) {
					queueRowsToShift.push({
						html: queueRows[i].innerHTML,
						songId: queueRows[i].getAttribute('data-song-id')
					});
				}

				for (let i = thisIndex + 1, j = 0; i <= dragSourceIndex; i++, j++) {
					queue[i] = queueRowsToShift[j].songId;
					queueRows[i].innerHTML = queueRowsToShift[j].html;
					queueRows[i].setAttribute('data-song-id', queueRowsToShift[j].songId);
				}
			}

			let data = JSON.parse(e.dataTransfer.getData('text/plain'));
			this.setAttribute('data-song-id', data.songId);
			this.innerHTML = data.html;
		}

		return false;
	}

	async function createQueueRow(songId) {
		let res = await $.get(`/api/song/${songId}`);
		let $queueRow = $(`<div class="music-row" draggable="true" data-song-id=${songId} data-album-id=${res.data.albumId} data-context-menu-actions="REMOVE_FROM_QUEUE,GO_TO_ALBUM" data-activable></div>`);
		let $img = $('<img class="lazy">').attr('data-src', res.data.albumArtUrl);
		let $artwork = $('<div class="artwork"></div>').append($img);
		let $totalTime = $('<div class="total-time"></div>').text(secondsToTimeString(res.data.songDuration));
		let $details = $('<div class="details"></div>').append([
			$('<div></div>').text(res.data.songName),
			$('<div></div>').text(res.data.songArtist),
		]);

		$queueRow.append([
			$('<div></div>').append([$artwork, $details]),
			$totalTime
		]);

		return $queueRow;
	}
</script>
