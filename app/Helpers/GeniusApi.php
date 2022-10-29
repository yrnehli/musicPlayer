<?php

namespace App\Helpers;

use Exception;
use stdClass;

include_once "lib/simplehtmldom_1_9_1/simple_html_dom.php";

class GeniusApi {
	private const API_BASE = "https://api.genius.com";

	public static function authTest() {
		try {
			self::curlRequest("GET", self::API_BASE . "/search?" . http_build_query([
				'access_token' => $_ENV['GENIUS_TOKEN']
			]));
		} catch (Exception $e) {
			throw new Exception("Genius API: Unauthorised");
		}
	}

	public static function search($q) {
		$res = json_decode(
			self::curlRequest("GET", self::API_BASE . "/search?" . http_build_query([
				'q' => $q, 'access_token' => $_ENV['GENIUS_TOKEN']
			]))
		);
	
		return $res->response->hits;
	}

	public static function getLyrics($q) {
		$res = self::search($q);

		if (count($res) === 0) {
			return [];
		}

		$lyricsElement = file_get_html("https://genius.com" . $res[0]->result->path)->getElementById("lyrics-root");
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

		$lyrics = array_map(function($lyric) {
			$obj = new stdClass();
			$obj->line = !empty(trim($lyric)) ? $lyric : null;
			$obj->milliseconds = null;
			$obj->duration = null;
			return $obj;
		},  array_slice(explode("\n", $lyrics), 1));

		return $lyrics;
	}
	
	private static function curlRequest($requestType, $url, $headers = [], $payload = "") {
		$curl = curl_init();
	
		curl_setopt_array($curl, [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_CUSTOMREQUEST => $requestType,
			CURLOPT_HTTPHEADER => $headers,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_USERAGENT => 'curl/7.39.0'
		]);
	
		if ($requestType === 'POST') {
			curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
		}
	
		$res = curl_exec($curl);
		$info = curl_getinfo($curl);

		curl_close($curl);

		if ($info['http_code'] !== 200) {
			throw new Exception("cURL $url failed with HTTP status " . $info['http_code']);
		}
	
		return $res;
	}
}
