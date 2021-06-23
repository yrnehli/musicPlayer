<?php

namespace App\Helpers;

use ColorThief\ColorThief;

class Utilities {
	public static function secondsToTimeString($duration) {
		$minutes = floor($duration / 60);
		$seconds = str_pad($duration - ($minutes * 60), 2, "0", STR_PAD_LEFT);
	
		return "$minutes:$seconds";
	}

	public static function secondsToEnglishTime($duration) {
		$hours = floor($duration / 60 / 60);
		$minutes = floor(($duration / 60) - ($hours * 60));

		return ($hours > 0) ? "$hours hr $minutes min" : "$minutes min";
	}

	public static function getAccentColour($image) {
		$rgb = ColorThief::getColor($image);
	
		$darken = false;
		$darknessFactor = 1;
	
		foreach ($rgb as $x) {
			if ($x > 60) {
				$darken = true;
				if ($darknessFactor > 60 / $x) {
					$darknessFactor = 60 / $x;
				}
			}
		}
	
		$accentColour = "#" . implode(
			"",
			array_map(
				function($x) use ($darken, $darknessFactor) {
					return str_pad(
						dechex(
							round(
								($darken) ? $x * $darknessFactor : $x
							)
						),
						2,
						"0",
						STR_PAD_LEFT
					);
				},
				$rgb
			)
		);

		return $accentColour;
	}
}

?>