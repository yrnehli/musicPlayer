<?php

namespace App\Controllers;

use App\Helpers\DeezerApi;
use App\Helpers\MusicDatabase;
use App\Helpers\DeezerPrivateApi;
use App\Helpers\Utilities;
use Exception;

class RootController extends Controller {
	public function index() {
		$this->authTest();

		$db = new MusicDatabase();
		$conn = $db->getConn();
		
		$stmt = $conn->prepare(
			"SELECT *
			FROM `albums`
			ORDER BY RAND()"
		);
		$stmt->execute();
		$albums = $stmt->fetchAll();

		$this->view('home', compact('albums'));
	}

	private function authTest() {
		$deezerPrivateApi = new DeezerPrivateApi();
		
		try {
			$deezerPrivateApi->authTest();
		} catch (Exception $e) {
			die($e->getMessage());
		}
	}

	public function queue() {
		$this->view('queue');
	}

	public function saved() {
		$deezerApi = new DeezerApi();
		$db = new MusicDatabase();
		$conn = $db->getConn();

		$stmt = $conn->prepare("SELECT * FROM `deezerSavedSongs`");
		$stmt->execute();
		$savedSongs = $stmt->fetchAll();

		$savedSongs = array_map(
			function($savedSong, $i) use ($deezerApi) {
				$songDetails = $deezerApi->getSong(
					str_replace(DeezerApi::DEEZER_ID_PREFIX, "", $savedSong['songId'])
				);

				$savedSong = array_merge(
					[
						'id' => $savedSong['songId'],
						'isFlagged' => ($savedSong['flagged'] === "1"),
						'trackNumber' => $i,
						'time' => Utilities::secondsToTimeString($songDetails['songDuration'])
					],
					$songDetails
				);

				return $savedSong;
			},
			$savedSongs,
			range(1, count($savedSongs))
		);

		// print json_encode($savedSongs); die();	

		$this->view('saved', compact('savedSongs'));
	}
}

?>