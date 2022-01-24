<?php

namespace App\Helpers;

class LastFmApi {
	private const API_BASE = "https://ws.audioscrobbler.com/2.0";
	private $apiKey;

	public function __construct() {
		$this->apiKey = $_ENV['LASTFM_API_KEY'];
	}

	public function getTopTracks($username, $period, $limit = null) {
		return $this->getTop("tracks", $username, $period, $limit);
	}

	public function getTopArtists($username, $period, $limit = null) {
		return $this->getTop("artists", $username, $period, $limit);
	}

	public function getTopAlbums($username, $period, $limit = null) {
		return $this->getTop("albums", $username, $period, $limit);
	}

	private function getTop($resource, $username, $period, $limit = null) {
		$top = [];
		$page = 1;
		$topResource = "top$resource";

		do {
			$res = json_decode(
				$this->curlRequest(
					"GET",
					self::API_BASE . "/?" . http_build_query([
						'method' => "user.get$topResource",
						'user' => $username,
						'period' => $period,
						'api_key' => $this->apiKey,
						'format' => 'json',
						'page' => $page,
						'limit' => $limit
					])
				)
			);

			$top = array_merge($top, $res->{$topResource}->{rtrim($resource, "s")});
			$page++;
		} while (empty($limit) && $page <= $res->{$topResource}->{'@attr'}->totalPages);

		return $top;
	}
	
	private function curlRequest($requestType, $url, $headers = [], $payload = "") {
		$curl = curl_init();
	
		curl_setopt_array($curl, [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_CUSTOMREQUEST => $requestType,
			CURLOPT_HTTPHEADER => $headers,
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1
		]);
	
		if ($requestType === 'POST') {
			curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
		}
	
		$res = curl_exec($curl);
	
		curl_close($curl);
	
		return $res;
	}
}
