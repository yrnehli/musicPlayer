<?php

require_once 'vendor/autoload.php';
require_once 'php/MusicManager.php';
require_once 'php/MusicDatabase.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$db = new MusicDatabase();
$conn = $db->getConn();

foreach (['userData', 'userData/albumArt'] as $directory) {
	if (!file_exists($directory))
		mkdir($directory);
}

Flight::map('renderView', function($viewName, $viewData, $title) {
	if (filter_var(Flight::request()->query->partial, FILTER_VALIDATE_BOOLEAN)) {
		Flight::render($viewName, $viewData);
		return;
	}
	
	Flight::render($viewName, $viewData, 'partial');
	Flight::render('musicControl', [], 'musicControl');
	Flight::render('shell', ['title' => $title]);
});

Flight::route("GET /", function() use ($conn) {
	$stmt = $conn->prepare("SELECT * FROM `albums`");
	$stmt->execute();
	$albums = $stmt->fetchAll();
	Flight::renderView('home', compact('albums'), "Home");
});

Flight::route("GET /album/@albumId", function($albumId) use ($conn) {
	$stmt = $conn->prepare(
		"SELECT `songs`.*
		FROM `songs`
		INNER JOIN `song-album` ON `songs`.`id` = `song-album`.`songId`
		WHERE `song-album`.`albumId` = :albumId"
	);
	$stmt->bindParam(":albumId", $albumId);
	$stmt->execute();
	$songs = $stmt->fetchAll();
	Flight::renderView('album', compact('songs'), "Album");
});

Flight::route("GET /mp3/@songId", function($songId) use ($conn) {
	$stmt = $conn->prepare("SELECT `filepath` FROM `songs` WHERE `id` = :id");
	$stmt->bindParam(":id", $songId);
	$stmt->execute();
	$filepath = $stmt->fetchColumn();
	
	$filesize = filesize($filepath);

	$offset = 0;
	$length = $filesize;

	if ( isset($_SERVER['HTTP_RANGE']) ) {
		// if the HTTP_RANGE header is set we're dealing with partial content

		$partialContent = true;

		// find the requested range
		// this might be too simplistic, apparently the client can request
		// multiple ranges, which can become pretty complex, so ignore it for now
		preg_match('/bytes=(\d+)-(\d+)?/', $_SERVER['HTTP_RANGE'], $matches);

		$offset = intval($matches[1]);
		$length = (count($matches) == 3) ? (intval($matches[2]) - $offset) : $filesize;
	} else {
		$partialContent = false;
	}

	$file = fopen($filepath, 'r');

	// seek to the requested offset, this is 0 if it's not a partial content request
	fseek($file, $offset);

	$data = fread($file, $length);

	fclose($file);

	if ( $partialContent ) {
		// output the right headers for partial content

		header('HTTP/1.1 206 Partial Content');

		header('Content-Range: bytes ' . $offset . '-' . ($offset + $length) . '/' . $filesize);
	}

	// output the regular HTTP headers
	header('Content-Type: audio/mpeg');
	header('Content-Length: ' . $filesize);
	header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
	header('Accept-Ranges: bytes');

	// don't forget to send the data too
	print($data);
});

Flight::route("GET /api/musicPlayer/@songId", function($songId) use ($conn) {
	$stmt = $conn->prepare(
		"SELECT `songs`.`songName`, `songs`.`songArtist`, `albums`.`albumArtFilepath`
		FROM `songs`
		INNER JOIN `song-album` ON `songs`.`id` = `song-album`.`songId`
		INNER JOIN `albums` ON `song-album`.`albumId` = `albums`.`id`
		WHERE `songs`.`id` = :id"
	);
	$stmt->bindParam(":id", $songId);
	$stmt->execute();
	$res = $stmt->fetch();
	$res['albumArtFilepath'] = str_replace(__DIR__, "", $res['albumArtFilepath']);
	Flight::json($res);
});

Flight::route("GET /api/update", function() {
	MusicManager::updateDatabase();
});

Flight::start();

?>