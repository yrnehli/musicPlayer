<?php

namespace App\Controllers;

use App\Helpers\MusicDatabase;
use App\Helpers\MusicManager;
use App\Helpers\DeezerApi;
use LastFmApi\Api\TrackApi;
use LastFmApi\Api\AuthApi;
use Exception;
use Flight;
use PDO;

class ApiController extends Controller {
	private const DEEZER_SEARCH_PREFIX = "e: ";

	public function reset() {
		MusicManager::resetDatabase();
		$this->responseHandler(true);
	}

	public function update() {
		MusicManager::updateDatabase();
		$this->responseHandler(true);
	}

	public function song($songId) {
		$this->responseHandler(
			true,
			"",
			str_starts_with($songId, DeezerApi::DEEZER_ID_PREFIX)
				? $this->getDeezerSong($songId)
				: $this->getLocalSong($songId)
		);
	}

	private function getDeezerSong($songId) {
		$db = new MusicDatabase();
		$filepath = "public/userData/cache/song/$songId";
		
		if (!file_exists($filepath)) {
			$deezerApi = new DeezerApi();
			$song =	$deezerApi->getSong($songId);
			file_put_contents($filepath, serialize($song));
		} else {
			$song = unserialize(file_get_contents($filepath));
		}

		$song = array_merge(
			[
				'songId' => $songId,
				'isDeezer' => true,
				'isSaved' => $db->isSongSaved($songId)
			],
			$song
		);

		return $song;
	}

	public function getLocalSong($songId) {
		$db = new MusicDatabase();
		$conn = $db->getConn();
		
		$stmt = $conn->prepare(
			"SELECT
				`songs`.`id` AS 'songId',
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
		$albumId = DeezerApi::removePrefix($albumId);
		$filepath = "public/userData/cache/album/$albumId";
		
		if (!file_exists($filepath)) {
			$deezerApi = new DeezerApi();
			$album = $deezerApi->getAlbum($albumId);
			file_put_contents($filepath, serialize($album));
		} else {
			$album = unserialize(file_get_contents($filepath));
		}

		return ['songIds' => array_column($album['songs'], "id")];
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
		$term = "%" . preg_replace(
			["/[^\w\s]/", "/ /", "/s/",],
			['', "%", '_'],
			$term
		) . "%";
		
		$db = new MusicDatabase();
		$conn = $db->getConn();

		$ignoreRegex = "[^A-Za-zÀ-ÖØ-öø-ÿ0-9 ]";

		$stmt = $conn->prepare(
			"SELECT `id`, `name`, `artist`, `duration`, `albumDetails`.`duration`, `artFilepath`, 0 AS `explicit`
			FROM `albums`
			INNER JOIN `albumDetails` ON `albums`.`id` = `albumDetails`.`albumId`
			WHERE REGEXP_REPLACE(CONCAT(`name`, `artist`), '$ignoreRegex', '') LIKE :term
			OR REGEXP_REPLACE(CONCAT(`artist`, `name`), '$ignoreRegex', '') LIKE :term
			ORDER BY CHAR_LENGTH(`name`)
			LIMIT 15"
		);
		$stmt->bindParam(":term", $term);
		$stmt->execute();
		$albums = $stmt->fetchAll();
	
		$stmt = $conn->prepare(
			"SELECT `songs`.`id`, `songs`.`name`, `songs`.`artist`, `songs`.`duration`, `albums`.`artFilepath`, `albums`.`id` AS `albumId`, 0 AS `explicit`
			FROM `songs`
			INNER JOIN `song-album` ON `songs`.`id` = `song-album`.`songId`
			INNER JOIN `albums` ON `song-album`.`albumId` = `albums`.`id`
			WHERE REGEXP_REPLACE(CONCAT(`songs`.`name`, `songs`.`artist`), '$ignoreRegex', '') LIKE :term
			OR REGEXP_REPLACE(CONCAT(`songs`.`artist`, `songs`.`name`), '$ignoreRegex', '') LIKE :term
			ORDER BY CHAR_LENGTH(`songs`.`name`)
			LIMIT 15"
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
			$db->insertSavedSong(
				$songId,
				filter_var(Flight::request()->query->flagged, FILTER_VALIDATE_BOOL)
			);
			$this->responseHandler(true, "Added to Saved Songs");
		} else if (Flight::request()->method === "DELETE") {
			$db->deleteSavedSong($songId);
			$this->responseHandler(true, "Removed from Saved Songs");
		}
	}

	public function nowPlaying($songId) {
		$isDeezerSong = str_starts_with($songId, DeezerApi::DEEZER_ID_PREFIX);
		$song = $isDeezerSong ? $this->getDeezerSong($songId) : $this->getLocalSong($songId);

		try {
			$trackApi = new TrackApi(
				new AuthApi(
					'setsession',
					[
						'apiKey' => $_ENV['LASTFM_API_KEY'],
						'apiSecret' => $_ENV['LASTFM_API_SECRET'],
						'sessionKey' => $_ENV['LASTFM_SESSION_KEY'],
						'username' => $_ENV['LASTFM_USERNAME'],
						'subscriber' => 0
					]
				)
			);

			$success = $trackApi->updateNowPlaying([
				'artist' => $isDeezerSong ? $song['mainSongArtist'] : $song['songArtist'],
				'track' => $song['songName'],
				'album' => $song['albumName'],
				'duration' => intval($song['songDuration'])
			]);
		} catch (Exception $e) {
			$success = false;
		}

		$this->responseHandler($success);
	}

	public function scrobble($songId) {
		$isDeezerSong = str_starts_with($songId, DeezerApi::DEEZER_ID_PREFIX);
		$song = $isDeezerSong ? $this->getDeezerSong($songId) : $this->getLocalSong($songId);
		$scrobble = [
			'artist' => $isDeezerSong ? $song['mainSongArtist'] : $song['songArtist'],
			'track' => $song['songName'],
			'album' => $song['albumName'],
			'duration' => intval($song['songDuration']),
			'timestamp' => time() - $song['songDuration']
		];

		try {
			$trackApi = new TrackApi(
				new AuthApi(
					'setsession',
					[
						'apiKey' => $_ENV['LASTFM_API_KEY'],
						'apiSecret' => $_ENV['LASTFM_API_SECRET'],
						'sessionKey' => $_ENV['LASTFM_SESSION_KEY'],
						'username' => $_ENV['LASTFM_USERNAME'],
						'subscriber' => 0
					]
				)
			);

			$success = $trackApi->scrobble($scrobble);
		} catch (Exception $e) {
			$success = false;
		}

		$db = new MusicDatabase();
		$db->insertScrobble(
			$scrobble['artist'],
			$scrobble['track'],
			$scrobble['album'],
			$scrobble['duration'],
			$scrobble['timestamp'],
			$success
		);

		$this->responseHandler($success);
	}
}
