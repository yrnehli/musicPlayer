<?php

namespace App\Controllers;

use App\Helpers\MusicDatabase;
use App\Helpers\DeezerPrivateApi;
use App\Helpers\SpotifyApi;
use Exception;

class RootController extends Controller {
	public function index() {
		$db = new MusicDatabase();
		$conn = $db->getConn();
		
		$stmt = $conn->prepare(
			"SELECT *
			FROM `albums`
			ORDER BY `artist`"
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
}

?>