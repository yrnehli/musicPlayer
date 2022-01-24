<?php

function dd($var) {
	print "<pre>";
	var_dump($var);
	print "</pre>";
	die();
}
