<?php

namespace App;

use Flight;

class Route {
	public static function map($requestType, $uri, $callback) {
		Flight::route("$requestType $uri", function(...$params) use ($callback) {
			$class = "App\\Controllers\\" . $callback[0];
			$method = $callback[1];

			$controller = new $class();
			$controller->$method(...$params);
		});
	}
}

?>