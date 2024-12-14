<!DOCTYPE html>
<html>
	<head>
		<meta name="theme-color" content="#121212">
		<link rel="icon" type="image/x-icon" href="public/img/music-mac.png">
		<link rel="stylesheet" href="/public/css/bootstrap.min.css" crossorigin="anonymous">
		<script src="/public/js/jquery-3.4.1.js" crossorigin="anonymous"></script>
		<script src="/public/js/popper.min.js" crossorigin="anonymous"></script>
		<script src="/public/js/bootstrap.min.js" crossorigin="anonymous"></script>
		<script src="/public/js/jquery-ui.js" crossorigin="anonymous"></script>
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
		<link rel="stylesheet" href="/public/css/fa.css">
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
