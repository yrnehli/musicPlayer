<?php

namespace App\Helpers;

use App\Helpers\MusicDatabase;
use getID3;

class MusicManager {
	public static function updateDatabase() {
		$getId3 = new getID3;
		$musicDatabase = new MusicDatabase();
		$musicDatabase->resetDatabase();

		$mp3s = glob($_ENV["MUSIC_DIRECTORY"] . "/*.mp3");
		$albumRelations = [];

		foreach ($mp3s as $mp3) {
			$mp3Info = $getId3->analyze($mp3);

			$tags = array_merge(
				array_key_exists("id3v1", $mp3Info['tags']) ? $mp3Info['tags']['id3v1'] : [],
				array_key_exists("id3v2", $mp3Info['tags']) ? $mp3Info['tags']['id3v2'] : []
			);

			$song['songName'] = $tags['title'][0];
			$song['songArtist'] = $tags['artist'][0];
			$song['albumName'] = $tags['album'][0];
			$song['albumArtist'] = $tags['band'][0];
			$song['trackNumber'] = (array_key_exists('track_number', $tags)) ? $tags['track_number'][0] : null;
			$song['discNumber'] = (array_key_exists('part_of_a_set', $tags)) ? intval($tags['part_of_a_set'][0]) : 1;
			$song['year'] = $tags['year'][0];
			$song['genre'] = implode("/", $tags['genre']);
			$song['duration'] = $mp3Info['playtime_seconds'];
			$song['filepath'] = $mp3Info['filenamepath'];

			$songId = $musicDatabase->insertSong(
				$song['songName'],
				$song['songArtist'],
				$song['trackNumber'],
				$song['discNumber'],
				$song['duration'],
				$song['filepath']
			);

			$albumKey = $song['albumArtist'] . " - " . $song['albumName'];

			if (array_key_exists($albumKey, $albumRelations)) {
				$albumRelations[$albumKey]['songIds'][] = $songId;
				continue;
			}

			$albumArt = $mp3Info['comments']['picture'][0]['data'];
			$albumArtFilepath = realpath("public/userData/albumArt") . "/" . md5($albumArt) . ".jpg";

			file_put_contents($albumArtFilepath, $albumArt);

			$albumId = $musicDatabase->insertAlbum(
				$song['albumName'],
				$song['albumArtist'],
				$song['genre'],
				$song['year'],
				str_replace(realpath("."), "", $albumArtFilepath)
			);

			$albumRelations[$albumKey] = [
				'albumId' => $albumId,
				'songIds' => [$songId]
			];
		}

		foreach ($albumRelations as $albumRelation) {
			foreach ($albumRelation['songIds'] as $songId) {
				$musicDatabase->insertSongAlbumMapping($songId, $albumRelation['albumId']);
			}
		}
	}
}

?>