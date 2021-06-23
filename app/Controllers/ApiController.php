<?php

namespace App\Controllers;

use App\Helpers\MusicDatabase;
use App\Helpers\MusicManager;
use App\Helpers\DeezerApi;
use App\Helpers\DeezerPrivateApi;
use Flight;
use PDO;

class ApiController extends Controller {
	public function song($songId) {
		if (str_starts_with($songId, DeezerApi::DEEZER_ID_PREFIX)) {
			$songId = str_replace(DeezerApi::DEEZER_ID_PREFIX, "", $songId);
			$filepath = "userData/deezer/metadata/$songId";
	
			if (!file_exists($filepath)) {
				$deezerPrivateApi = new DeezerPrivateApi();
				$res = $deezerPrivateApi->getSongData($songId);
				
				file_put_contents($filepath, serialize($res));
			} else {
				$res = unserialize(file_get_contents($filepath));
			}
		} else {
			$db = new MusicDatabase();
			$conn = $db->getConn();
			$stmt = $conn->prepare(
				"SELECT
					`songs`.`name` AS 'songName',
					`songs`.`artist` AS 'songArtist',
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
			$res = $stmt->fetch();	
		}
	
		Flight::json($res);
	}

	public function album($albumId) {
		if (str_starts_with($albumId, DeezerApi::DEEZER_ID_PREFIX)) {
			$albumId = str_replace(DeezerApi::DEEZER_ID_PREFIX, "", $albumId);
			$filepath = "userData/deezer/album/$albumId";
	
			if (!file_exists($filepath)) {
				$deezerApi = new DeezerApi();
				$res = $deezerApi->getAlbum($albumId);
				
				file_put_contents($filepath, serialize($res));
			} else {
				$res = unserialize(file_get_contents($filepath));
			}
	
			$songIds = array_column($res['songs'], "id");
		} else {
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
		}
	
		Flight::json(compact('songIds'));
	}

	public function search() {
		$searchTerm = Flight::request()->query->term;

		if (str_starts_with($searchTerm, "e: ")) {
			$searchTerm = substr($searchTerm, strlen("e: "));
	
			$deezerApi = new DeezerApi();
			$res = $deezerApi->search($searchTerm);
	
			$albums = $res['albums'];
			$songs = $res['songs'];
		} else {
			$searchTerm = str_replace(" ", "%", $searchTerm);
			$searchTerm = "%$searchTerm%";
		
			$db = new MusicDatabase();
			$conn = $db->getConn();
			$stmt = $conn->prepare(
				"SELECT `id`, `name`, `artist`, `duration`, `albumDetails`.`duration`, `artFilepath`
				FROM `albums`
				INNER JOIN `albumDetails` ON `albums`.`id` = `albumDetails`.`albumId`
				WHERE CONCAT(`name`, `artist`) LIKE :searchTerm
				OR CONCAT(`artist`, `name`) LIKE :searchTerm
				LIMIT 5"
			);
			$stmt->bindParam(":searchTerm", $searchTerm);
			$stmt->execute();
			$albums = $stmt->fetchAll();
		
			$stmt = $conn->prepare(
				"SELECT `songs`.`id`, `songs`.`name`, `songs`.`artist`, `songs`.`duration`, `albums`.`artFilepath`, `albums`.`id` AS `albumId`
				FROM `songs`
				INNER JOIN `song-album` ON `songs`.`id` = `song-album`.`songId`
				INNER JOIN `albums` ON `song-album`.`albumId` = `albums`.`id`
				WHERE CONCAT(`songs`.`name`, `songs`.`artist`) LIKE :searchTerm
				OR CONCAT(`songs`.`artist`, `songs`.`name`) LIKE :searchTerm
				LIMIT 5"
			);
			$stmt->bindParam(":searchTerm", $searchTerm);
			$stmt->execute();
			$songs = $stmt->fetchAll();
		}
	
		Flight::json(compact('albums', 'songs'));
	}

	public function update() {
		MusicManager::updateDatabase();
	}

	public function mp3($songId) {
		if (str_starts_with($songId, DeezerApi::DEEZER_ID_PREFIX)) {
			$songId = str_replace(DeezerApi::DEEZER_ID_PREFIX, "", $songId);
			$filepath = "userData/deezer/mp3/$songId";
			
			if (!file_exists($filepath)) {
				$deezerPrivateApi = new DeezerPrivateApi();
				$song = $deezerPrivateApi->getSong($songId);
	
				file_put_contents(
					$filepath,
					($song !== false) ? $song : ""
				);
			}
		} else {
			$db = new MusicDatabase();
			$conn = $db->getConn();
			$stmt = $conn->prepare("SELECT `filepath` FROM `songs` WHERE `id` = :id");
			$stmt->bindParam(":id", $songId);
			$stmt->execute();
			$filepath = $stmt->fetchColumn();
		}
	
		$filesize = filesize($filepath);
	
		if (isset($_SERVER['HTTP_RANGE'])) {
			$bytes = explode(
				"-",
				str_replace("bytes=", "", $_SERVER['HTTP_RANGE'])
			);
			$startOffset = $bytes[0];
			$endOffset = (!empty($bytes[1])) ? $bytes[1] : $filesize;
		} else {
			$startOffset = 0;
			$endOffset = $filesize;
		}
	
		$file = fopen($filepath, 'r');
		fseek($file, $startOffset);
		$data = fread($file, $endOffset - $startOffset);
		fclose($file);
	
		Flight::response()
			->header('Accept-Ranges', 'bytes')
			->header('Content-Type', 'audio/mpeg')
			->header('Content-Range', "bytes $startOffset-" . ($endOffset - 1) . "/$filesize")
			->status(206)
			->write($data)
			->send()
		;
	}
}

?>