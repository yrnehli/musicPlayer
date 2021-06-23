<?php

ini_set('memory_limit' ,'-1');

require_once 'vendor/autoload.php';

$directories = [
	'public/userData',
	'public/userData/albumArt',
	'public/userData/deezer',
	'public/userData/deezer/mp3',
	'public/userData/deezer/metadata',
	'public/userData/deezer/album'
];

foreach ($directories as $directory) {
	if (!file_exists($directory)) {
		mkdir($directory);
	}
}

spl_autoload_register(function($className) {
	$filepath = realpath('.') . "/" . str_replace("\\", "/", lcfirst($className)) . ".php";

	if (file_exists($filepath)) {
		require_once $filepath;
	} else {
		throw New Exception("Could not find class $className at $filepath!");
	}
});

$objects = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator(realpath('app')),
	RecursiveIteratorIterator::SELF_FIRST
);

foreach ($objects as $object) {
	if (str_ends_with($object->getFilename(), ".php")) {
		require_once $object->getPathname();
	}
}

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

include_once "routes.php";

Flight::start();

?>