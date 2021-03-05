<?php

require_once 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

Flight::route('/', function() {
	Flight::render('home', [], 'content');
	Flight::render('layout', ['title' => 'Home']);
});

Flight::start();

?>