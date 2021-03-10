<?php

require_once 'vendor/autoload.php';
require_once 'php/MusicManager.php';
require_once 'php/MusicDatabase.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$musicDatabase = new MusicDatabase();

foreach (['userData', 'userData/albumArt'] as $directory) {
	if (!file_exists($directory))
		mkdir($directory);
}

Flight::map('renderView', function($viewName, $viewData, $title) {
	if (!filter_var(Flight::request()->query->partial, FILTER_VALIDATE_BOOLEAN)) {
		Flight::render($viewName, $viewData, 'partial');
		Flight::render('shell', ['title' => $title]);
	} else {
		Flight::render($viewName, $viewData);
	}
});

Flight::route("GET /", function() use ($musicDatabase) {
	$albums = $musicDatabase->getAlbums();
	Flight::renderView('home', compact('albums'), "Home");
});

Flight::route("GET /album/@albumId", function($albumId) use ($musicDatabase) {
	$songs = $musicDatabase->getSongs($albumId);
	Flight::renderView('album', compact('songs'), "Album");
});

Flight::route("GET /mp3/@songId", function($songId) use ($musicDatabase) {
	readfile(
		$musicDatabase->getSong($songId)['filepath']
	);
});

Flight::route("GET /api/update", function() {
	MusicManager::updateDatabase();
});

Flight::start();

?>