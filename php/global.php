<?php

function secondsToTimeString($duration) {
	$minutes = floor($duration / 60);
	$seconds = str_pad($duration - ($minutes * 60), 2, "0", STR_PAD_LEFT);

	return "$minutes:$seconds";
}

?>