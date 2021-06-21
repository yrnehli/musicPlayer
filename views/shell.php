<!DOCTYPE html>
<html>
	<head>
		<meta name="theme-color" content="#121212">
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
		<script src="https://code.jquery.com/jquery-3.4.1.js" integrity="sha256-WpOohJOqMqqyKL9FccASB9O0KwACQJpFTUBLTYOVvVU=" crossorigin="anonymous"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
		<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/howler/2.2.1/howler.min.js"></script>
		<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js" integrity="sha256-T0Vest3yCU7pafRw9r+settMBX6JkKN06dqBnpQ8d30=" crossorigin="anonymous"></script>
		<script src="/assets/js/global.js"></script>
		<script src="/assets/js/MusicPlayer.js"></script>
		<script src="/assets/js/PartialManager.js"></script>
		<script src="/assets/js/CustomContextMenu.js"></script>
		<script src="/assets/js/fontAwesome.js"></script>
		<link rel="stylesheet" href="https://code.jquery.com/git/ui/jquery-ui-git.css">
		<link rel="stylesheet" href="/assets/css/global.css">
		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simplebar@5.3.0/dist/simplebar.css">
		<script src="https://cdn.jsdelivr.net/npm/simplebar@5.3.0/dist/simplebar.min.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.waitforimages/1.5.0/jquery.waitforimages.min.js"></script>
		<link rel="manifest" href="/manifest.webmanifest">
		<link rel="icon" href="data:,">
		<title>Music</title>
	</head>
	<body>
		<div id="windowControlsOverlay">
			<input type="text" id="searchBar" placeholder="Search" spellcheck="false">
			<i id="clearSearchBar" class="fal fa-times" style="display: none;"></i>
		</div>
		<div id="partial">
			<?= $partial ?>
		</div>
		<?= $musicControl ?>
		<ul id="contextMenu"></ul>
		<div id="toastNotification"></div>
		<script>
			(function() {
				var $searchBar = $('#searchBar');
				var $searchResults = $('#searchResults');
				var $clearSearchBar = $('#clearSearchBar');
				var $songs = $('#songs');
				var $albums = $('#albums');
				var $songsContainer = $('#songsContainer');
				var $albumsContainer = $('#albumsContainer');
				var timeout;
	
				$(function() {
					initEvents();
				});
	
				function initEvents() {
					$(window).keydown(e => assignKeydown(e));
					$(window).keyup(e => assignKeyup(e));
					$(window).mousedown(function(e) {
						$('.result-row.active').removeClass('active');
	
						var $resultRow = $(e.target).is('.result-row') ? $(e.target) : $(e.target).parents('.result-row').first();
	
						if ($resultRow) {
							$resultRow.addClass('active');
						}
					});
					$searchBar.keyup(() => search());
					$clearSearchBar.click(e => {
						$searchBar.val(null);
						search();
					});
					$('.album-container').click(function() {
						PartialManager.sharedInstance.loadPartial(`/album/${$(this).data('album-id')}`)
					});
				}
	
				function search() {
					clearTimeout(timeout);
	
					timeout = setTimeout(
						async() => {
							var term = $searchBar.val();
				
							$searchBar.attr('value', term);
				
							if (term.trim() === "") {
								$clearSearchBar.hide();
								$searchResults.hide();
								$songs.hide();
								$albums.hide();
								return;
							}
				
							$clearSearchBar.show();
				
							var res = await $.get('/api/search', { term: term });
				
							$songsContainer.empty().append(res.songs.map(song => createResultRow('song', song.id, song.albumId, song.name, song.artist, song.duration, song.artFilepath)));
							$albumsContainer.empty().append(res.albums.map(album => createResultRow('album', album.id, album.id, album.name, album.artist, album.duration, album.artFilepath)));
				
							(res.songs.length > 0 || res.albums.length > 0) ? $searchResults.show() : $searchResults.hide();
							(res.songs.length > 0) ? $songs.show(): $songs.hide();
							(res.albums.length > 0) ? $albums.show(): $albums.hide();
	
							PartialManager.sharedInstance.updateCurrentState();
						},
						100
					);
				}
	
				function assignKeydown(e) {
					// Ctrl + F
					if (e.ctrlKey && e.keyCode === 70) {
						e.preventDefault();
					}
				}
	
				function assignKeyup(e) {
					// Ctrl + F
					if (e.ctrlKey && e.keyCode === 70) {
						e.preventDefault();
						$searchBar.focus();
					}
				}
	
				function createResultRow(type, id, albumId, name, artist, duration, artFilepath) {
					var $resultRow = $(`<div class="result-row" data-${type}-id=${id} data-album-id=${albumId} data-context-menu-actions="QUEUE,GO_TO_ALBUM"></div>`);
					var $img = $('<img>').prop('src', artFilepath);
					var $artwork = $('<div class="artwork"></div>').append($img);
					var $details = $('<div class="details"></div>').append([
						$('<div></div>').text(name),
						$('<div></div>').text(artist),
					]);
					var $totalTime = (duration) ? $('<div class="total-time"></div>').text(secondsToTimeString(duration)) : "";
	
					$resultRow.dblclick(() => (type === 'song') ? playSong($resultRow) : playAlbum($resultRow));
					$resultRow.append([
						$('<div></div>').append([$artwork, $details]),
						$totalTime
					]);
	
					return $resultRow;
				}
	
				function playSong($resultRow) {
					MusicPlayer.sharedInstance.changeSong($resultRow.data('song-id'), true);
				}
	
				async function playAlbum($resultRow) {
					var res = await $.get(`/api/album/${$resultRow.data('album-id')}`);
					MusicPlayer.sharedInstance.playNextUp({
						list: res.songIds,
						i: 0
					});
				}
			})();
		</script>
	</body>
</html>