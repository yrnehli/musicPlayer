<?php

function dd($var, $json = false) {
	if ($json) {
		print json_encode($var, JSON_PRETTY_PRINT);
		die();
	}

	print "<pre>";
	var_dump($var);
	print "</pre>";
	
	die();
}
