<?php

namespace App\Controllers;

use App\Helpers\MusicDatabase;
use App\Helpers\MusicManager;
use App\Helpers\DeezerApi;
use Flight;
use PDO;

class ApiController extends Controller {
	private const DEEZER_SEARCH_PREFIX = "e: ";

	public function update() {
		MusicManager::updateDatabase();
		$this->responseHandler(true);
	}

	public function song($songId) {
		$this->responseHandler(
			true,
			"",
			str_starts_with($songId, DeezerApi::DEEZER_ID_PREFIX) ? $this->getDeezerSong($songId) : $this->getLocalSong($songId)
		);
	}

	private function getDeezerSong($songId) {
		$deezerApi = new DeezerApi();
		$db = new MusicDatabase();

		$song = array_merge(
			[
				'isDeezer' => true,
				'isSaved' => $db->isDeezerSongSaved($songId)
			],
			$deezerApi->getSong(
				str_replace(DeezerApi::DEEZER_ID_PREFIX, "", $songId)
			)
		);

		return $song;
	}

	private function getLocalSong($songId) {
		$db = new MusicDatabase();
		$conn = $db->getConn();
		
		$stmt = $conn->prepare(
			"SELECT
				`songs`.`name` AS 'songName',
				`songs`.`artist` AS 'songArtist',
				`songs`.`duration` AS 'songDuration',
				`albums`.`artFilepath` AS 'albumArtUrl',
				`albums`.`name` AS 'albumName',
				`albums`.`id` AS 'albumId'
			FROM `songs`
			INNER JOIN `song-album` ON `songs`.`id` = `song-album`.`songId`
			INNER JOIN `albums` ON `song-album`.`albumId` = `albums`.`id`
			WHERE `songs`.`id` = :id"
		);
		$stmt->bindParam(":id", $songId);
		$stmt->execute();
		$song = $stmt->fetch();

		$song = array_merge(
			[
				'isDeezer' => false,
				'isSaved' => false
			],
			$song
		);

		return $song;
	}

	public function album($albumId) {	
		$this->responseHandler(
			true,
			"",
			str_starts_with($albumId, DeezerApi::DEEZER_ID_PREFIX) ? $this->getDeezerAlbum($albumId) : $this->getLocalAlbum($albumId)
		);
	}

	private function getDeezerAlbum($albumId) {
		$albumId = str_replace(DeezerApi::DEEZER_ID_PREFIX, "", $albumId);

		$deezerApi = new DeezerApi();
		$res = $deezerApi->getAlbum($albumId);

		return ['songIds' => array_column($res['songs'], "id")];
	}

	private function getLocalAlbum($albumId) {
		$db = new MusicDatabase();
		$conn = $db->getConn();
		$stmt = $conn->prepare(
			"SELECT `songs`.`id`
			FROM `songs`
			INNER JOIN `song-album` ON `songs`.`id` = `song-album`.`songId`
			WHERE `song-album`.`albumId` = :albumId
			ORDER BY `songs`.`discNumber`, `songs`.`trackNumber`"
		);
		$stmt->bindParam(":albumId", $albumId);
		$stmt->execute();
		$songIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

		return ['songIds' => $songIds];
	}

	public function search() {
		$term = Flight::request()->query->term;
	
		$this->responseHandler(
			true,
			"",
			str_starts_with($term, self::DEEZER_SEARCH_PREFIX) ? $this->searchDeezer($term) : $this->searchLocal($term)
		);
	}

	private function searchDeezer($term) {	
		$deezerApi = new DeezerApi();
		$res = $deezerApi->search(
			substr($term, strlen(self::DEEZER_SEARCH_PREFIX))
		);

		return $res;
	}

	private function searchLocal($term) {
		$term = "%" . str_replace(
			[" ", "s"],
			["%", '_'],
			strtolower($term)
		) . "%";
		
		$db = new MusicDatabase();
		$conn = $db->getConn();

		$stmt = $conn->prepare(
			"SELECT `id`, `name`, `artist`, `duration`, `albumDetails`.`duration`, `artFilepath`
			FROM `albums`
			INNER JOIN `albumDetails` ON `albums`.`id` = `albumDetails`.`albumId`
			WHERE REGEXP_REPLACE(CONCAT(`name`, `artist`), '[^A-Za-zÀ-ÖØ-öø-ÿ ]', '') LIKE :term
			OR REGEXP_REPLACE(CONCAT(`artist`, `name`), '[^A-Za-zÀ-ÖØ-öø-ÿ0-9 ]', '') LIKE :term
			LIMIT 5"
		);
		$stmt->bindParam(":term", $term);
		$stmt->execute();
		$albums = $stmt->fetchAll();
	
		$stmt = $conn->prepare(
			"SELECT `songs`.`id`, `songs`.`name`, `songs`.`artist`, `songs`.`duration`, `albums`.`artFilepath`, `albums`.`id` AS `albumId`
			FROM `songs`
			INNER JOIN `song-album` ON `songs`.`id` = `song-album`.`songId`
			INNER JOIN `albums` ON `song-album`.`albumId` = `albums`.`id`
			WHERE REGEXP_REPLACE(CONCAT(`songs`.`name`, `songs`.`artist`), '[^A-Za-zÀ-ÖØ-öø-ÿ ]', '') LIKE :term
			OR REGEXP_REPLACE(CONCAT(`songs`.`artist`, `songs`.`name`), '[^A-Za-zÀ-ÖØ-öø-ÿ ]', '') LIKE :term
			LIMIT 5"
		);
		$stmt->bindParam(":term", $term);
		$stmt->execute();
		$songs = $stmt->fetchAll();

		return compact('albums', 'songs');
	}

	public function saved($songId) {
		if (!str_starts_with($songId, DeezerApi::DEEZER_ID_PREFIX)) {
			return;
		}

		$db = new MusicDatabase();
		
		if (Flight::request()->method === "PUT") {
			$db->insertDeezerSavedSong(
				$songId,
				filter_var(Flight::request()->query->flagged, FILTER_VALIDATE_BOOL)
			);
			$this->responseHandler(true, "Added to saved songs");
		} else if (Flight::request()->method === "DELETE") {
			$db->deleteDeezerSavedSong($songId);
			$this->responseHandler(true, "Removed from saved songs");
		}
	} 
}

?>