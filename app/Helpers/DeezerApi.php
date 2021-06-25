<?php

namespace App\Helpers;

class DeezerApi {
	public const DEEZER_ID_PREFIX = "DEEZER-";
	private const API_BASE = "https://api.deezer.com";

	public function search($term, $limit = 5) {
		$songs = [];
		$albums = [];

		if (empty($term)) {
			return compact('songs', 'albums');
		}

		$res = json_decode(
			$this->curlRequest(
				"GET",
				self::API_BASE . "/search?" . http_build_query(['q' => $term])
			)
		);

		foreach ($res->data as $song) {
			if (count($songs) < $limit) {
				$songs[] = [
					"id" => self::DEEZER_ID_PREFIX . $song->id,
					"albumId" => self::DEEZER_ID_PREFIX . $song->album->id,
					"name" => $song->title,
					"artist" => $song->artist->name,
					"duration" => $song->duration,
					"artFilepath" => "https://cdns-images.dzcdn.net/images/cover/$song->md5_image/500x500.jpg"
				];
			}

			if (count($albums) < $limit && !array_key_exists($song->album->id, $albums)) {
				$albums[$song->album->id] = [
					"id" => self::DEEZER_ID_PREFIX . $song->album->id,
					"name" => $song->album->title,
					"artist" => $song->artist->name,
					"duration" => null,
					"artFilepath" => "https://cdns-images.dzcdn.net/images/cover/$song->md5_image/500x500.jpg"
				];
			}
		}

		$albums = array_values($albums);

		return compact('songs', 'albums');
	}

	public function getSong($id) {
		$res = json_decode(
			$this->curlRequest(
				"GET",
				self::API_BASE . "/track/$id"
			)
		);

		$song = [
			'songName' => $res->title,
			'songArtist' => $res->artist->name,
			'songDuration' => $res->duration,
			'albumArtUrl' => $res->album->cover,
			'albumName' => $res->album->title,
			'albumId' => DeezerApi::DEEZER_ID_PREFIX . $res->album->id,
			'isrc' => $res->isrc
		];

		return $song;
	}

	public function getAlbum($id) {
		$res = json_decode(
			$this->curlRequest(
				"GET",
				self::API_BASE . "/album/$id"
			)
		);

		$res = [
			"album" => [
				"artUrl" => $res->cover_big,
				"name" => $res->title,
				"artist" => $res->artist->name,
				"year" => substr($res->release_date, 0, 4),
				"length" => count($res->tracks->data),
				"duration" => $res->duration
			],
			"songs" => array_map(
				function($song, $i) {
					return [
						"id" => self::DEEZER_ID_PREFIX . $song->id,
						"trackNumber" => $i + 1,
						"name" => $song->title,
						"artist" => $song->artist->name,
						"duration" => $song->duration
					];
				},
				$res->tracks->data,
				array_keys($res->tracks->data)
			)
		];

		return $res;
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

?>