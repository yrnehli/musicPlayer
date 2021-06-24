<?php

namespace App;

use Flight;

class Route {
	public static function map($requestType, $uri, $callback) {
		Flight::route("$requestType $uri", function(...$params) use ($callback) {
			list($class, $method) = explode('@', $callback);
			$class = "App\\Controllers\\$class";
			$controller = new $class();
			$controller->$method(...$params);
		});
	}
}

?>