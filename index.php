<?php

require_once 'vendor/autoload.php';
require_once 'php/MusicManager.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

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

Flight::route("GET /", function() {
	Flight::renderView('home', [], "Home");
});

Flight::route("GET /test", function() {
	Flight::renderView('test', [], "Test");
});

Flight::route("GET /api/update", function() {
	MusicManager::updateDatabase();
});

Flight::route("GET /mp3/@filename", function($filename) {
	readfile($_ENV['MUSIC_DIRECTORY'] . "/$filename");
});

Flight::start();

?>