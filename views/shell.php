<!DOCTYPE html>
<html>
	<head>
		<meta name="theme-color" content="#121212">
		<link rel="stylesheet" href="/public/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
		<script src="/public/js/jquery-3.4.1.js" integrity="sha256-WpOohJOqMqqyKL9FccASB9O0KwACQJpFTUBLTYOVvVU=" crossorigin="anonymous"></script>
		<script src="/public/js/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
		<script src="/public/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
		<script src="/public/js/jquery-ui.js" integrity="sha256-T0Vest3yCU7pafRw9r+settMBX6JkKN06dqBnpQ8d30=" crossorigin="anonymous"></script>
		<script src="/public/js/simplebar.min.js"></script>
		<script src="/public/js/jquery.waitforimages.min.js"></script>
		<script src="/public/js/lazyload.min.js"></script>
		<script src="/public/js/EventEmitter.js"></script>
		<script src="/public/js/Music.js"></script>
		<script src="/public/js/MusicControl.js"></script>
		<script src="/public/js/PartialManager.js"></script>
		<script src="/public/js/CustomContextMenu.js"></script>
		<script src="/public/js/SearchHandler.js"></script>
		<script src="/public/js/FontAwesome.js"></script>
		<script src="/public/js/app.js"></script>
		<link rel="stylesheet" href="/public/css/jquery-ui-git.css">
		<link rel="stylesheet" href="/public/css/global.css">
		<link rel="stylesheet" href="/public/css/simplebar.css">
		<link rel="manifest" href="/manifest.webmanifest">
		<link rel="icon" href="data:,">
		<title>Music</title>
	</head>
	<body>
		<?= $windowControlsOverlay ?>
		<div id="searchResults" style="display: none; left: <?= ($os['os_family'] === 'macintosh') ? '6rem' : '1rem' ?>"></div>
		<div id="partial">
			<?= $partial ?>
		</div>
		<?= $control ?>
		<ul id="contextMenu"></ul>
		<div id="toastNotification"></div>
	</body>
</html>
