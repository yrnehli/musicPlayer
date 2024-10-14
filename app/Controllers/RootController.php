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
			"SELECT `albums`.*
			FROM `albums`
			INNER JOIN `song_album` ON `albums`.`id` = `song_album`.`albumId`
			ORDER BY `song_album`.`id` DESC"
		);
		$stmt->execute();
		$albums = array_unique($stmt->fetchAll(), SORT_REGULAR);
		$songIds = $this->getSongIds();

		$this->view('home', compact('albums', 'songIds'));
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
		$filepath = "public/userData/cache/song/$songId-PRIVATE";

		if (!empty($songId)) {
			$songId = DeezerApi::removePrefix($songId);
			
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
}
