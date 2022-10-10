<?php

namespace App\Helpers;

use stdClass;

include_once "lib/simplehtmldom_1_9_1/simple_html_dom.php";

class GeniusApi {
	public static function getLyrics() {
		$lyricsElement = file_get_html('https://genius.com/A-ap-rocky-a-ap-forever-lyrics')->getElementById("lyrics-root");
		$lyricsElement->lastChild()->remove();

		$lyrics = html_entity_decode(
			preg_replace_callback('/&#[xX]([0-9a-fA-F]+);/', function($match) {
				list(, $hex) = $match;
				$decimal = hexdec($hex);
				return "&#$decimal;";
			}, $lyricsElement->plaintext),
			ENT_QUOTES
		);

		$lyrics = preg_replace(
			["/(\[.*?\])*|You might also like/", "/\r\n\r\n|\r\r|\n\n/"],
			["", "\n"],
			$lyrics
		);

		// var_dump(array_slice(explode("\n", $lyrics), 1)); die();

		$lyrics = array_map(function($lyric) {
			$obj = new stdClass();
			$obj->line = !empty(trim($lyric)) ? $lyric : null;
			$obj->milliseconds = null;
			$obj->duration = null;
			return $obj;
		},  array_slice(explode("\n", $lyrics), 1));

		return $lyrics;
	}
}
