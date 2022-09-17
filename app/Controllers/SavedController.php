<?php

namespace App\Controllers;

use App\Helpers\MusicDatabase;
use App\Helpers\DeezerApi;
use App\Helpers\Utilities;
use Exception;

class SavedController extends Controller {
	public function index() {
		$deezerApi = new DeezerApi();

		$savedSongs = $this->getSavedSongs();
		
		if (!empty($savedSongs)) {
			$savedSongs = array_map(
				function($savedSong, $i) use ($deezerApi) {
					$songDetails = $deezerApi->getSong(
						DeezerApi::removePrefix($savedSong['songId'])
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
		}

		$this->view('saved', compact('savedSongs'));
	}

	public function clear() {
		$db = new MusicDatabase();
		$db->getConn()->prepare("DELETE FROM `savedSongs`")->execute();
	}

	private function getSavedSongs() {
		$db = new MusicDatabase();
		$conn = $db->getConn();

		$stmt = $conn->prepare("SELECT * FROM `savedSongs` WHERE `active` = 1");
		$stmt->execute();
		$savedSongs = $stmt->fetchAll();

		return $savedSongs;
	}
}
