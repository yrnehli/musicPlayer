<?php

require_once 'vendor/autoload.php';
require_once 'php/MusicManager.php';
require_once 'php/MusicDatabase.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$db = new MusicDatabase();
$conn = $db->getConn();

foreach (['userData', 'userData/albumArt'] as $directory) {
	if (!file_exists($directory)) {
		mkdir($directory);
	}
}

Flight::map('renderView', function($viewName, $viewData) {
	if (filter_var(Flight::request()->query->partial, FILTER_VALIDATE_BOOLEAN)) {
		Flight::render($viewName, $viewData);
		return;
	}
	
	Flight::render($viewName, $viewData, 'partial');
	Flight::render('musicControl', [], 'musicControl');
	Flight::render('shell');
});

Flight::route("GET /", function() use ($conn) {
	$stmt = $conn->prepare("SELECT * FROM `albums`");
	$stmt->execute();
	$albums = $stmt->fetchAll();
	Flight::renderView('home', compact('albums'));
});

Flight::route("GET /album/@albumId", function($albumId) use ($conn) {
	$stmt = $conn->prepare(
		"SELECT `albums`.*, `albumDetails`.*
		FROM `albums`
		INNER JOIN `albumDetails` ON `albums`.`id` = `albumDetails`.`albumId`
		WHERE `id` = :id"
	);
	$stmt->bindParam(":id", $albumId);
	$stmt->execute();
	$album = $stmt->fetch();

	if ($album === false) {
		Flight::response()
			->header('Location', '/')
			->status(404)
			->send()
		;
	}

	$stmt = $conn->prepare(
		"SELECT `songs`.*
		FROM `songs`
		INNER JOIN `song-album` ON `songs`.`id` = `song-album`.`songId`
		WHERE `song-album`.`albumId` = :albumId
		ORDER BY `songs`.`discNumber`, `songs`.`trackNumber`"
	);
	$stmt->bindParam(":albumId", $albumId);
	$stmt->execute();
	$songs = $stmt->fetchAll();

	Flight::renderView('album', compact('songs', 'album'));
});

Flight::route("GET /mp3/@songId", function($songId) use ($conn) {
	$stmt = $conn->prepare("SELECT `filepath` FROM `songs` WHERE `id` = :id");
	$stmt->bindParam(":id", $songId);
	$stmt->execute();
	$filepath = $stmt->fetchColumn();
	$filesize = filesize($filepath);

	if (isset($_SERVER['HTTP_RANGE'])) {
		$bytes = explode(
			"-",
			str_replace("bytes=", "", $_SERVER['HTTP_RANGE'])
		);
		$startOffset = $bytes[0];
		$endOffset = (!empty($bytes[1])) ? $bytes[1] : $filesize;
	} else {
		$startOffset = 0;
		$endOffset = $filesize;
	}

	$file = fopen($filepath, 'r');
	fseek($file, $startOffset);
	$data = fread($file, $endOffset - $startOffset);
	fclose($file);

	Flight::response()
		->header('Accept-Ranges', 'bytes')
		->header('Content-Type', 'audio/mpeg')
		->header('Content-Range', "bytes $startOffset-" . ($endOffset - 1) . "/$filesize")
		->status(206)
		->write($data)
		->send()
	;
});

Flight::route("GET /api/musicPlayer/@songId", function($songId) use ($conn) {
	$stmt = $conn->prepare(
		"SELECT
			`songs`.`name` AS 'songName',
			`songs`.`artist` AS 'songArtist',
			`albums`.`artFilepath` AS 'albumArtFilepath',
			`albums`.`name` AS 'albumName',
			`albums`.`id` AS 'albumId'
		FROM `songs`
		INNER JOIN `song-album` ON `songs`.`id` = `song-album`.`songId`
		INNER JOIN `albums` ON `song-album`.`albumId` = `albums`.`id`
		WHERE `songs`.`id` = :id"
	);
	$stmt->bindParam(":id", $songId);
	$stmt->execute();
	$res = $stmt->fetch();
	Flight::json($res);
});

Flight::route("GET /api/update", function() {
	MusicManager::updateDatabase();
});

Flight::start();

?>