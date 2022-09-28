<?php

ini_set('memory_limit' ,'-1');

require_once 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$directories = [
	'public/userData/albumArt',
	'public/userData/cache/mp3',
	'public/userData/cache/album',
	'public/userData/cache/song'
];

foreach ($directories as $directory) {
	if (!file_exists($directory)) {
		mkdir($directory, 0655, true);
	}
}

spl_autoload_register(function($className) {
	$filepath = realpath('.') . "/" . str_replace("\\", "/", lcfirst($className)) . ".php";

	if (file_exists($filepath)) {
		require_once $filepath;
	} else {
		throw new Exception("Autoload failure: could not find class `$className` at `$filepath`!");
	}
});

$files = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator(realpath('app')),
	RecursiveIteratorIterator::SELF_FIRST
);

foreach ($files as $file) {
	if (str_ends_with($file->getFilename(), ".php")) {
		require_once $file->getPathname();
	}
}

include_once "global.php";
include_once "routes.php";

Flight::start();
