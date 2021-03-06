<?php

require_once 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

Flight::map('renderView', function($viewName, $title) {
	if (filter_var(Flight::request()->query->partial, FILTER_VALIDATE_BOOLEAN)) {
		Flight::render($viewName);
	} else {
		Flight::render($viewName, [], 'partial');
		Flight::render('shell', ['title' => $title]);
	}
});

Flight::route("GET /", function() {
	Flight::renderView('home', "Home");
});

Flight::route("GET /test", function() {
	Flight::renderView('test', "Test");
});

Flight::start();

?>