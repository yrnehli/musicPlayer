<?php

require_once 'vendor/autoload.php';
require_once 'php/global.php';
require_once 'php/MusicManager.php';
require_once 'php/MusicDatabase.php';
require_once 'php/DeezerPrivateApi.php';

use ColorThief\ColorThief;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$db = new MusicDatabase();
$conn = $db->getConn();

foreach (['userData', 'userData/albumArt', 'userData/deezer'] as $directory) {
	if (!file_exists($directory)) {
		mkdir($directory);
	}
}

Flight::map('renderView', function($viewName, $viewData = []) use ($conn) {
	if (filter_var(Flight::request()->query->partial, FILTER_VALIDATE_BOOLEAN)) {
		Flight::render($viewName, $viewData);
		return;
	}

	$stmt = $conn->prepare("SELECT `id` FROM `songs`");
	$stmt->execute();
	$songIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
	
	Flight::render($viewName, $viewData, 'partial');
	Flight::render('musicControl', compact('songIds'), 'musicControl');
	Flight::render('shell');
});

Flight::route("GET /", function() use ($conn) {
	$stmt = $conn->prepare(
		"SELECT *
		FROM `albums`
		ORDER BY RAND()"
	);
	$stmt->execute();
	$albums = $stmt->fetchAll();

	Flight::renderView('home', compact('albums'));
});

Flight::route("GET /search", function() {
	Flight::renderView('search');
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
		return;
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

	$rgb = ColorThief::getColor(
		file_get_contents(__DIR__ . $album['artFilepath'])
	);

	$darken = false;
	$darknessFactor = 1;

	foreach ($rgb as $x) {
		if ($x > 60) {
			$darken = true;
			if ($darknessFactor > 60 / $x) {
				$darknessFactor = 60 / $x;
			}
		}
	}

	$rgb = implode(
		", ",
		array_map(
			function($x) use ($darken, $darknessFactor) {
				return round(
					($darken) ? $x * $darknessFactor : $x
				);
			},
			$rgb
		)
	);

	Flight::renderView('album', compact('album', 'songs', 'rgb'));
});

Flight::route("GET /mp3/@songId", function($songId) use ($conn) {
	if (str_contains($songId, "DEEZER")) {
		$songId = str_replace("DEEZER-", "", $songId);
		$filepath = "userData/deezer/$songId";
		
		if (!file_exists($filepath)) {
			$deezerPrivateApi = new DeezerPrivateApi();
			$song = $deezerPrivateApi->getSong($songId);

			file_put_contents(
				$filepath,
				($song !== false) ? $song : ""
			);
		}
	} else {
		$stmt = $conn->prepare("SELECT `filepath` FROM `songs` WHERE `id` = :id");
		$stmt->bindParam(":id", $songId);
		$stmt->execute();
		$filepath = $stmt->fetchColumn();
	}

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

Flight::route("GET /api/song/@songId", function($songId) use ($conn) {
	if (str_contains($songId, "DEEZER")) {
		$songId = str_replace("DEEZER-", "", $songId);
		$deezerPrivateApi = new DeezerPrivateApi();
		$res = $deezerPrivateApi->getSongData($songId);
	} else {
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
	}

	Flight::json($res);
});

Flight::route("GET /api/album/@albumId", function($albumId) use ($conn) {
	$stmt = $conn->prepare(
		"SELECT `songs`.`id`
		FROM `songs`
		INNER JOIN `song-album` ON `songs`.`id` = `song-album`.`songId`
		WHERE `song-album`.`albumId` = :albumId
		ORDER BY `songs`.`discNumber`, `songs`.`trackNumber`"
	);
	$stmt->bindParam(":albumId", $albumId);
	$stmt->execute();
	$songIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

	Flight::json(compact('songIds'));
});

Flight::route("GET /api/search", function() use ($conn) {
	$searchTerm = Flight::request()->query->term;
	$searchTerm = str_replace(" ", "%", $searchTerm);
	$searchTerm = "%$searchTerm%";

	$stmt = $conn->prepare(
		"SELECT `id`, `name`, `artist`, `duration`, `albumDetails`.`duration`, `artFilepath`
		FROM `albums`
		INNER JOIN `albumDetails` ON `albums`.`id` = `albumDetails`.`albumId`
		WHERE CONCAT(`name`, `artist`) LIKE :searchTerm
		OR CONCAT(`artist`, `name`) LIKE :searchTerm
		LIMIT 5"
	);
	$stmt->bindParam(":searchTerm", $searchTerm);
	$stmt->execute();
	$albums = $stmt->fetchAll();

	$stmt = $conn->prepare(
		"SELECT `songs`.`id`, `songs`.`name`, `songs`.`artist`,`songs`.`duration`, `albums`.`artFilepath`
		FROM `songs`
		INNER JOIN `song-album` ON `songs`.`id` = `song-album`.`songId`
		INNER JOIN `albums` ON `song-album`.`albumId` = `albums`.`id`
		WHERE CONCAT(`songs`.`name`, `songs`.`artist`) LIKE :searchTerm
		OR CONCAT(`songs`.`artist`, `songs`.`name`) LIKE :searchTerm
		LIMIT 5"
	);
	$stmt->bindParam(":searchTerm", $searchTerm);
	$stmt->execute();
	$songs = $stmt->fetchAll();

	Flight::json(compact('albums', 'songs'));
});

Flight::route("GET /api/update", function() {
	MusicManager::updateDatabase();
});

Flight::start();

?>