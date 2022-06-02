<?php

namespace App\Controllers;

use App\Helpers\MusicDatabase;
use App\Helpers\DeezerApi;
use App\Helpers\DeezerPrivateApi;
use App\Helpers\SpotifyApi;
use App\Helpers\LastFmApi;
use App\Helpers\Utilities;
use Exception;

class RootController extends Controller {
	public function index() {
		$db = new MusicDatabase();
		$conn = $db->getConn();
		
		$stmt = $conn->prepare(
			"SELECT *
			FROM `albums`
			ORDER BY `artist`, `year`"
		);
		$stmt->execute();
		$albums = $stmt->fetchAll();

		$this->view('home', compact('albums'));
	}

	public function auth() {
		$deezerPrivateApi = new DeezerPrivateApi();
		$spotifyApi = new SpotifyApi();
		
		try {
			$deezerPrivateApi->authTest();
			$spotifyApi->authTest();
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
			$song = $deezerApi->getSong($songId);
		}

		if (!empty($songId)) {
			$deezerPrivateApi = new DeezerPrivateApi();
			$res = $deezerPrivateApi->getSong($songId);
			$lyrics = (property_exists($res->results, 'LYRICS') && property_exists($res->results->LYRICS, 'LYRICS_SYNC_JSON')) ? $res->results->LYRICS->LYRICS_SYNC_JSON : [];
			if (!isset($accentColour)) {
				$accentColour = Utilities::getAccentColour(
					"https://cdns-images.dzcdn.net/images/cover/{$res->results->DATA->ALB_PICTURE}/500x500.jpg"
				);
			}
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
