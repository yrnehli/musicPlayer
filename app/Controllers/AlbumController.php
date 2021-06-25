<?php

namespace App\Controllers;

use App\Helpers\DeezerApi;
use App\Helpers\MusicDatabase;
use App\Helpers\SpotifyApi;
use App\Helpers\Utilities;
use Flight;

class AlbumController extends Controller {
	public function album($albumId) {
		$data = str_starts_with($albumId, DeezerApi::DEEZER_ID_PREFIX) ? $this->getDeezerAlbum($albumId) : $this->getLocalAlbum($albumId);

		if ($data === false) {
			Flight::response()->status(404)->send();
			return;
		}

		$spotifyApi = new SpotifyApi();

		$deezerSavedSongIds = array_map(
			function($res) {
				return DeezerApi::DEEZER_ID_PREFIX . json_decode($res)->id;
			},
			$this->curlMulti(
				array_map(
					function($item) {
						return "https://api.deezer.com/2.0/track/isrc:" . $item->track->external_ids->isrc;
					},
					$spotifyApi->getSavedTracks()->items
				)
			)
		);

		foreach ($data['songs'] as &$song) {
			$song['isDeezer'] = str_starts_with($song['id'], DeezerApi::DEEZER_ID_PREFIX);
			$song['isSaved'] = in_array($song['id'], $deezerSavedSongIds);
			$song['time'] = Utilities::secondsToTimeString($song['duration']);
		}

		$data['album']['englishTime'] = Utilities::secondsToEnglishTime($data['album']['duration']);
		$data['accentColour'] = Utilities::getAccentColour($data['art']);
	
		$this->view('album', $data);
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

		return [
			'album' => $res['album'],
			'songs' => $res['songs'],
			'art' => file_get_contents($res['album']['artUrl'])
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

	private function curlMulti($urls) {
		$mh = curl_multi_init();
		$res = [];

		foreach($urls as $i => $url) {
			$ch[$i] = curl_init($url);
			curl_setopt($ch[$i], CURLOPT_RETURNTRANSFER, 1);
			curl_multi_add_handle($mh, $ch[$i]);
		}

		do {
			$execReturnValue = curl_multi_exec($mh, $runningHandles);
		} while ($execReturnValue == CURLM_CALL_MULTI_PERFORM);

		while ($runningHandles && $execReturnValue == CURLM_OK) {
			$numberReady = curl_multi_select($mh);

			if ($numberReady != -1) {
				do {
					$execReturnValue = curl_multi_exec($mh, $runningHandles);
				} while ($execReturnValue == CURLM_CALL_MULTI_PERFORM);
			}
		}

		foreach($urls as $i => $url) {
			$res[$i] = curl_multi_getcontent($ch[$i]);

			curl_multi_remove_handle($mh, $ch[$i]);
			curl_close($ch[$i]);
		}
		
		curl_multi_close($mh);
		
		return $res;
	}
}

?>