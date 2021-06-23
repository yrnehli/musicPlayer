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
		<script src="/assets/js/MusicPlayer.js"></script>
		<script src="/assets/js/PartialManager.js"></script>
		<script src="/assets/js/CustomContextMenu.js"></script>
		<script src="/assets/js/SearchHandler.js"></script>
		<script src="/assets/js/global.js"></script>
		<script src="/assets/js/FontAwesome.js"></script>
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
			<i id="searchIcon" class="fal fa-search"></i>
			<input type="text" id="searchBar" placeholder="Search" spellcheck="false">
			<i id="clearSearchBar" class="fal fa-times" style="display: none;"></i>
		</div>
		<div id="partial">
			<?= $partial ?>
		</div>
		<?= $musicControl ?>
		<ul id="contextMenu"></ul>
		<div id="toastNotification"></div>
	</body>
</html>