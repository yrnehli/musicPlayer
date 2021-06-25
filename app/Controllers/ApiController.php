<?php

namespace App\Controllers;

use App\Helpers\MusicDatabase;
use App\Helpers\MusicManager;
use App\Helpers\SpotifyApi;
use App\Helpers\DeezerApi;
use Flight;
use PDO;

class ApiController extends Controller {
	private const DEEZER_SEARCH_PREFIX = "e: ";

	public function update() {
		MusicManager::updateDatabase();
	}

	public function song($songId) {
		$this->responseHandler(
			true,
			"",
			str_starts_with($songId, DeezerApi::DEEZER_ID_PREFIX) ? $this->getDeezerSong($songId) : $this->getLocalSong($songId)
		);
	}

	private function getDeezerSong($songId) {
		$songId = str_replace(DeezerApi::DEEZER_ID_PREFIX, "", $songId);
		$filepath = "public/userData/deezer/metadata/$songId";

		if (!file_exists($filepath)) {
			$deezerApi = new DeezerApi();
			$song = $deezerApi->getSong($songId);
			file_put_contents($filepath, serialize($song));
		} else {
			$song = unserialize(file_get_contents($filepath));
		}

		$spotifyApi = new SpotifyApi();

		$song['isDeezer'] = true;
		$song['isSaved'] = in_array(
			$spotifyApi->getSpotifyId($song['isrc']),
			array_map(
				function($item) {
					return $item->track->id;
				},
				$spotifyApi->getSavedTracks()->items
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

		$song['isDeezer'] = false;
		$song['isSaved'] = false;

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
		$filepath = "public/userData/deezer/album/$albumId";

		if (!file_exists($filepath)) {
			$deezerApi = new DeezerApi();
			$res = $deezerApi->getAlbum($albumId);
			file_put_contents($filepath, serialize($res));
		} else {
			$res = unserialize(file_get_contents($filepath));
		}

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
		$term = str_replace(" ", "%", $term);
		$term = "%$term%";
	
		$db = new MusicDatabase();
		$conn = $db->getConn();

		$stmt = $conn->prepare(
			"SELECT `id`, `name`, `artist`, `duration`, `albumDetails`.`duration`, `artFilepath`
			FROM `albums`
			INNER JOIN `albumDetails` ON `albums`.`id` = `albumDetails`.`albumId`
			WHERE CONCAT(`name`, `artist`) LIKE :term
			OR CONCAT(`artist`, `name`) LIKE :term
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
			WHERE CONCAT(`songs`.`name`, `songs`.`artist`) LIKE :term
			OR CONCAT(`songs`.`artist`, `songs`.`name`) LIKE :term
			LIMIT 5"
		);
		$stmt->bindParam(":term", $term);
		$stmt->execute();
		$songs = $stmt->fetchAll();

		return compact('albums', 'songs');
	}

	public function spotifyTracks($songId) {
		if (!str_starts_with($songId, DeezerApi::DEEZER_ID_PREFIX)) {
			return;
		}
		
		$songId = str_replace(DeezerApi::DEEZER_ID_PREFIX, "", $songId);

		$spotifyApi = new SpotifyApi();
		$deezerApi = new DeezerApi();

		$spotifyId = $spotifyApi->getSpotifyId(
			$deezerApi->getSong($songId)['isrc']
		);

		if (empty($spotifyId)) {
			$this->responseHandler(false, "Could not find track on Spotify.");
		}

		if (Flight::request()->method === "PUT") {
			$spotifyApi->saveTrack($spotifyId);
		} else if (Flight::request()->method === "DELETE") {
			$spotifyApi->unsaveTrack($spotifyId);
		}

		$this->responseHandler(true);
	}
}

?>