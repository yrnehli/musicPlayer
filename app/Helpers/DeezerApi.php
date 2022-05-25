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
					"artFilepath" => "https://cdns-images.dzcdn.net/images/cover/$song->md5_image/500x500.jpg",
					"explicit" => $song->explicit_lyrics
				];
			}

			if (count($albums) < $limit && !array_key_exists($song->album->id, $albums)) {
				$albums[$song->album->id] = [
					"id" => self::DEEZER_ID_PREFIX . $song->album->id,
					"name" => $song->album->title,
					"artist" => $song->artist->name,
					"duration" => null,
					"artFilepath" => "https://cdns-images.dzcdn.net/images/cover/$song->md5_image/500x500.jpg",
					"explicit" => $song->explicit_lyrics
				];
			}
		}

		$albums = array_values($albums);

		return compact('songs', 'albums');
	}

	public function getSong($songId) {
		$deezerPrivateApi = new DeezerPrivateApi();
		$res = $deezerPrivateApi->getSong($songId);

		$song = [
			'songName' => $res->results->DATA->SNG_TITLE,
			'songArtist' => implode(
				", ",
				array_map(
					function($artist) {
						return $artist->ART_NAME;
					},
					$res->results->DATA->ARTISTS
				)
			),
			'mainSongArtist' => $res->results->DATA->ARTISTS[0]->ART_NAME,
			'songDuration' => $res->results->DATA->DURATION,
			'albumArtUrl' => "https://cdns-images.dzcdn.net/images/cover/{$res->results->DATA->ALB_PICTURE}/500x500.jpg",
			'albumName' => $res->results->DATA->ALB_TITLE,
			'albumId' => DeezerApi::DEEZER_ID_PREFIX . $res->results->DATA->ALB_ID,
			'isrc' => $res->results->DATA->ISRC,
			'lyrics' => property_exists($res->results, 'LYRICS') ? $res->results->LYRICS->LYRICS_SYNC_JSON : []
		];

		return $song;
	}
	
	public function getAlbum($albumId) {
		$deezerPrivateApi = new DeezerPrivateApi();
		$res = $deezerPrivateApi->getAlbum($albumId);

		$res = [
			"album" => [
				"artUrl" => "https://cdns-images.dzcdn.net/images/cover/" . $res->results->DATA->ALB_PICTURE . "/500x500.jpg",
				"name" => $res->results->DATA->ALB_TITLE,
				"artist" => implode(
					", ",
					array_map(
						function($artist) {
							return $artist->ART_NAME;
						},
						$res->results->DATA->ARTISTS
					)
				),
				"year" => substr($res->results->DATA->DIGITAL_RELEASE_DATE, 0, 4),
				"length" => count($res->results->SONGS->data),
				"duration" => array_sum(
					array_map(
						function($song) {
							return $song->DURATION;
						},
						$res->results->SONGS->data
					)
				),
				"explicit" => in_array($res->results->DATA->EXPLICIT_ALBUM_CONTENT->EXPLICIT_LYRICS_STATUS, [1, 4])
			],
			"songs" => array_map(
				function($song) {
					return [
						"id" => self::DEEZER_ID_PREFIX . $song->SNG_ID,
						"trackNumber" => $song->TRACK_NUMBER,
						"name" => empty($song->VERSION) ? $song->SNG_TITLE : "$song->SNG_TITLE $song->VERSION",
						"artist" => implode(
							", ",
							array_map(
								function($artist) {
									return $artist->ART_NAME;
								},
								$song->ARTISTS
							)
						),
						"duration" => $song->DURATION
					];
				},
				$res->results->SONGS->data
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
