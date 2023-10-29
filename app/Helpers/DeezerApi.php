<?php

namespace App\Helpers;

class DeezerApi {
	public const DEEZER_ID_PREFIX = "DEEZER-";
	private const API_BASE = "https://api.deezer.com";

	public function search($term, $limit = 5) {
		$songs = [];
		$albums = [];
		$artists = [];

		if (empty($term)) {
			return compact('songs', 'albums', 'artists');
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
					"artistId" => self::DEEZER_ID_PREFIX . $song->artist->id,
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
					"artistId" => self::DEEZER_ID_PREFIX . $song->artist->id,
					"artist" => $song->artist->name,
					"duration" => null,
					"artFilepath" => "https://cdns-images.dzcdn.net/images/cover/$song->md5_image/500x500.jpg",
					"explicit" => $song->explicit_lyrics
				];
			}

			if (count($artists) < $limit && !array_key_exists($song->artist->id, $artists)) {
				$artists[$song->artist->id] = [
					"id" => self::DEEZER_ID_PREFIX . $song->artist->id,
					"name" => $song->artist->name,
					"artFilepath" => $song->artist->picture_small
				];
			}
		}

		$albums = array_values($albums);
		$artists = array_values($artists);

		return compact('songs', 'albums', 'artists');
	}

	public function getSong($songId) {
		$songId = DeezerApi::removePrefix($songId);
		$res = json_decode(
			$this->curlRequest(
				"GET",
				self::API_BASE . "/track/$songId"
			)
		);

		$song = [
			'songId' => DeezerApi::DEEZER_ID_PREFIX . $songId,
			'songName' => $res->title,
			'artistId' => DeezerApi::DEEZER_ID_PREFIX . $res->contributors[0]->id,
			'songArtist' => implode(
				", ",
				array_map(
					function($contributor) {
						return $contributor->name;
					},
					$res->contributors
				)
			),
			'mainSongArtist' => $res->contributors[0]->name,
			'songDuration' => $res->duration,
			'albumArtUrl' => $res->album->cover,
			'albumName' => $res->album->title,
			'albumId' => DeezerApi::DEEZER_ID_PREFIX . $res->album->id,
			'isrc' => $res->isrc
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
				"artistId" => self::DEEZER_ID_PREFIX . $res->results->DATA->ARTISTS[0]->ART_ID,
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

	public function getArtist($artistId) {
		$artistId = DeezerApi::removePrefix($artistId);
		$res = json_decode(
			$this->curlRequest(
				"GET",
				self::API_BASE . "/artist/$artistId"
			)
		);

		return $res;
	}

	public function getArtistAlbums($artistId) {
		$artistId = DeezerApi::removePrefix($artistId);
		$res = json_decode(
			$this->curlRequest(
				"GET",
				self::API_BASE . "/artist/$artistId/albums"
			)
		);

		usort($res->data, function($a, $b) {
			return strtotime($a->release_date) < strtotime($b->release_date);
		});

		$albums = [];

		foreach ($res->data as $album) {
			if (!array_key_exists($album->title, $albums)) {
				$albums[$album->title] = $album;
			} else {
				if ($album->explicit_lyrics) {
					$albums[$album->title] = $album;
				}
			}
		}

		return array_values($albums);
	}

	public static function removePrefix($songId) {
		return str_replace(DeezerApi::DEEZER_ID_PREFIX, "", $songId);
	}

	private function curlRequest($requestType, $url, $headers = [], $payload = "") {
		$curl = curl_init();
	
		curl_setopt_array($curl, [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_CUSTOMREQUEST => $requestType,
			CURLOPT_HTTPHEADER => $headers
		]);
	
		if ($requestType === 'POST') {
			curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
		}
	
		$res = curl_exec($curl);
	
		curl_close($curl);
	
		return $res;
	}
}
