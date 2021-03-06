<?php

require_once 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

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
	
});

Flight::start();

?>