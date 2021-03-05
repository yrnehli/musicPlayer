<?php

require_once 'vendor/autoload.php';
require_once 'php/config.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

Flight::route('/', function() {
	Flight::render('home', [], 'content');
	Flight::render('layout', ['title' => 'Home']);
});

Flight::start();

?>