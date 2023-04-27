<?php

namespace App\Controllers;

use App\Helpers\DeezerApi;
use App\Helpers\MusicDatabase;
use App\Helpers\Utilities;
use Flight;

class AlbumController extends Controller {
	public function album($albumId) {
		$data = str_starts_with($albumId, DeezerApi::DEEZER_ID_PREFIX) ? $this->getDeezerAlbum($albumId) : $this->getLocalAlbum($albumId);

		if ($data === false) {
			Flight::response()->status(404)->send();
			return;
		}

		$db = new MusicDatabase();

		foreach ($data['songs'] as &$song) {
			$song['time'] = Utilities::secondsToTimeString($song['duration']);
			$song['isDeezer'] = str_starts_with($song['id'], DeezerApi::DEEZER_ID_PREFIX);
			$song['isFlagged'] = ($song['isDeezer']) ? $db->isSongFlagged($song['id']) : false;
			$song['isSaved'] = ($song['isDeezer']) ? $db->isSongSaved($song['id']) : false;
		}

		$data['album']['englishTime'] = Utilities::secondsToEnglishTime($data['album']['duration']);
		$data['accentColour'] = Utilities::getAccentColour($data['art']);
	
		$this->view('album', $data);
	}

	private function getDeezerAlbum($albumId) {
		$albumId = DeezerApi::removePrefix($albumId);		
		$deezerApi = new DeezerApi();
		$album = $deezerApi->getAlbum($albumId);

		return [
			'album' => $album['album'],
			'songs' => $album['songs'],
			'art' => file_get_contents($album['album']['artUrl'])
		];
	}

	private function getLocalAlbum($albumId) {
		$db = new MusicDatabase();
		$conn = $db->getConn();

		$stmt = $conn->prepare(
			"SELECT `albums`.*, `albums`.`artFilePath` AS 'artUrl', `albumDetails`.*
			FROM `albums`
			INNER JOIN `albumDetails` ON `albums`.`id` = `albumDetails`.`albumId`
			WHERE `id` = :id"
		);
		$stmt->bindParam(":id", $albumId);
		$stmt->execute();
		$album = $stmt->fetch();
	
		if ($album === false) {
			return false;
		}
		
		$album['explicit'] = false;
	
		$stmt = $conn->prepare(
			"SELECT `songs`.*
			FROM `songs`
			INNER JOIN `song-album` ON `songs`.`id` = `song-album`.`songId`
			WHERE `song-album`.`albumId` = :albumId
			ORDER BY `songs`.`discNumber`, `songs`.`trackNumber`"
		);
		$stmt->bindParam(":albumId", $albumId);
		$stmt->execute();
		$songs = $stmt->fetchAll();

		$art = file_get_contents(substr($album['artFilepath'], 1));

		return compact('album', 'songs', 'art');
	}
}
