<?php

namespace App\Controllers;

use App\Helpers\MusicDatabase;
use App\Helpers\DeezerApi;
use App\Helpers\DeezerPrivateApi;
use App\Helpers\LastFmApi;
use App\Helpers\Utilities;
use App\Helpers\GeniusApi;
use Exception;

class RootController extends Controller {
	public function index() {
		$db = new MusicDatabase();
		$conn = $db->getConn();
		
		$stmt = $conn->prepare(
			"SELECT *
			FROM `albums`
			ORDER BY `id` DESC"
		);
		$stmt->execute();
		$albums = $stmt->fetchAll();

		$this->view('home', compact('albums'));
	}

	public function auth() {
		$deezerPrivateApi = new DeezerPrivateApi();
		
		try {
			GeniusApi::authTest();
			$deezerPrivateApi->authTest();
		} catch (Exception $e) {
			$this->responseHandler(false, $e->getMessage());
		}

		$this->responseHandler(true);
	}

	public function queue() {
		$this->view('queue');
	}

	public function lyrics($songId) {
		$deezerApi = new DeezerApi();
		$lyrics = [];

		if (!str_starts_with($songId, DeezerApi::DEEZER_ID_PREFIX)) {
			$api = new ApiController();
			$song = $api->getLocalSong($songId);
			$res = $deezerApi->search("track:\"{$song['songName']}\" artist:\"{$song['songArtist']}\" album:\"{$song['albumName']}\"");
			$songId = (count($res['songs']) > 0) ? $res['songs'][0]['id'] : null;
			$accentColour = Utilities::getAccentColour(
				substr($song['albumArtUrl'], 1)
			);
		} else {
			$songId = DeezerApi::removePrefix($songId);
			$filepath = "public/userData/cache/song/$songId";
			
			if (!file_exists($filepath)) {
				$deezerApi = new DeezerApi();
				$song =	$deezerApi->getSong($songId);
				file_put_contents($filepath, serialize($song));
			} else {
				$song = unserialize(file_get_contents($filepath));
			}
		}

		$deezerPrivateApi = new DeezerPrivateApi();

		if (!empty($songId)) {
			$songId = DeezerApi::removePrefix($songId);
			$filepath = "public/userData/cache/song/$songId-PRIVATE";
			
			if (!file_exists($filepath)) {
				$deezerSong = $deezerPrivateApi->getSong($songId);
				file_put_contents($filepath, serialize($deezerSong));
			} else {
				$deezerSong = unserialize(file_get_contents($filepath));
			}

			if (!isset($accentColour)) {
				$accentColour = Utilities::getAccentColour(
					"https://cdns-images.dzcdn.net/images/cover/{$deezerSong->results->DATA->ALB_PICTURE}/500x500.jpg"
				);
			}
		}

		if (isset($deezerSong) && property_exists($deezerSong->results, 'LYRICS') && property_exists($deezerSong->results->LYRICS, 'LYRICS_SYNC_JSON')) {
			$lyrics = $deezerSong->results->LYRICS->LYRICS_SYNC_JSON;
		} else {
			$lyrics = GeniusApi::getLyrics(implode(" ", [$song['songName'], $song['songArtist']]));
			$deezerSong = $deezerPrivateApi->getSong($songId);
			file_put_contents($filepath, serialize($deezerSong));
		}
			
		$this->view('lyrics', [
			'song' => $song,
			'lyrics' => $lyrics,
			'accentColour' => isset($accentColour) ? $accentColour : false
		]);
	}

	public function wrapped() {
		// https://www.last.fm/user/username/partial/albums?albums_date_preset=LAST_7_DAYS
		// https://www.last.fm/user/username/partial/albums?albums_date_preset=LAST_30_DAYS
		// https://www.last.fm/user/username/partial/albums?albums_date_preset=LAST_90_DAYS
		// https://www.last.fm/user/username/partial/albums?albums_date_preset=LAST_180_DAYS
		// https://www.last.fm/user/username/partial/albums?albums_date_preset=LAST_365_DAYS
		// https://www.last.fm/user/username/partial/albums?albums_date_preset=ALL
		// ["7day", "1month", "3month", "6month", "12month", "overall"]

		$lastFmApi = new LastFmApi();
		$username = $_ENV["LASTFM_USERNAME"];
		$period = "overall"; 

		$topTracks = $lastFmApi->getTopTracks($username, $period);
		$data = [
			"scrobbles" => number_format(
				array_sum(
					array_column(
						$topTracks,
						"playcount"
					)
				)
			),
			"minutes" => number_format(
				array_sum(
					array_map(
						function($topTrack) {
							return $topTrack->duration * $topTrack->playcount;
						},
						$topTracks
					)
				) / 60
			),
			"artists" => array_column(
				$lastFmApi->getTopArtists($username, $period, 5),
				"name"
			),
			"albums" => array_column(
				$lastFmApi->getTopAlbums($username, $period, 5),
				"name"
			)
		];

		preg_match(
			"/https.*avatar300s.*jpg/",
			file_get_contents("https://www.last.fm/user/$username/partial/artists?artists_date_preset=ALL"),
			$matches
		);

		$data['mainAlbumArt'] = $matches[0];
		$data['accentColour'] = Utilities::getAccentColour($data['mainAlbumArt']);

		$this->view('wrapped', $data);
	}
}
