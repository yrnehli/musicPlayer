<?php

namespace App\Controllers;

use ColorThief\ColorThief;
use App\Helpers\DeezerApi;
use App\Helpers\MusicDatabase;
use Flight;

class AlbumController extends Controller {
	public function index($albumId) {
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
	
			$album = $res['album'];
			$songs = $res['songs'];
	
			$albumArt = file_get_contents($album['artUrl']);
		} else {
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
				Flight::response()->status(404)->send();
				return;
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
	
			$albumArt = file_get_contents(substr($album['artFilepath'], 1));
		}
		
		$rgb = ColorThief::getColor($albumArt);
	
		$darken = false;
		$darknessFactor = 1;
	
		foreach ($rgb as $x) {
			if ($x > 60) {
				$darken = true;
				if ($darknessFactor > 60 / $x) {
					$darknessFactor = 60 / $x;
				}
			}
		}
	
		$colour = "#" . implode(
			"",
			array_map(
				function($x) use ($darken, $darknessFactor) {
					return str_pad(
						dechex(
							round(
								($darken) ? $x * $darknessFactor : $x
							)
						),
						2,
						"0",
						STR_PAD_LEFT
					);
				},
				$rgb
			)
		);
	
		$this->view('album', compact('album', 'songs', 'colour'));
	}
}

?>