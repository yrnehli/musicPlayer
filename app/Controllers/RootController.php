<?php

namespace App\Controllers;

use App\Helpers\MusicDatabase;
use App\Helpers\DeezerPrivateApi;
use App\Helpers\SpotifyApi;
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

	public function wrapped() {
		// ["7day", "1month", "3month", "6month", "12month", "overall"]
		$period = "overall";
		$username = $_ENV["LASTFM_USERNAME"];
		
		$res = json_decode(
			file_get_contents("https://ws.audioscrobbler.com/2.0/?method=user.gettoptracks&user=$username&period=$period&limit=1000&api_key=b1ec88afd67a475d63b76ca0749e469e&format=json")
		);
		$minutes = number_format(
			array_sum(
				array_column(
					$res->toptracks->track,
					"duration"
				)
			) / 60
		);

		$res = json_decode(
			file_get_contents("https://ws.audioscrobbler.com/2.0/?method=user.gettopartists&user=$username&period=$period&limit=5&api_key=b1ec88afd67a475d63b76ca0749e469e&format=json")
		);
		$artists = array_column(
			$res->topartists->artist,
			"name"
		);

		$res = json_decode(
			file_get_contents("https://ws.audioscrobbler.com/2.0/?method=user.gettopalbums&user=$username&period=$period&limit=5&api_key=b1ec88afd67a475d63b76ca0749e469e&format=json")
		);
		$albums = array_column(
			$res->topalbums->album,
			"name"
		);

		$res = json_decode(
			file_get_contents("https://ws.audioscrobbler.com/2.0/?method=user.getinfo&user=$username&api_key=b1ec88afd67a475d63b76ca0749e469e&format=json")
		);
		$scrobbles = number_format($res->user->playcount);

		$res = file_get_contents("https://www.last.fm/user/$username/partial/albums?albums_date_preset=ALL");
		preg_match("/https.*avatar300s.*jpg/", $res, $matches);
		$mainAlbumArt = str_replace("avatar300s", "avatar1000s", $matches[0]);

		$accentColour = Utilities::getAccentColour($mainAlbumArt);

		$this->view('wrapped', compact('minutes', 'artists', 'albums', 'scrobbles', 'mainAlbumArt', 'accentColour'));
	}
}
