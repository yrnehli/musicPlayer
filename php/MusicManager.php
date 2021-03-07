<?php

require_once 'Song.php';
require_once 'MusicDatabase.php';

class MusicManager {
	public static function updateDatabase() {
		$getId3 = new getID3;
		$mp3s = glob($_ENV["MUSIC_DIRECTORY"] . "/*.mp3");

		$songs = array_map(
			function($mp3) use ($getId3) {
				$mp3Info = $getId3->analyze($mp3);

				$tags = array_merge(
					array_key_exists("id3v1", $mp3Info['tags']) ? $mp3Info['tags']['id3v1'] : [],
					array_key_exists("id3v2", $mp3Info['tags']) ? $mp3Info['tags']['id3v2'] : []
				);

				$song = new Song();
				$song->songName = $tags['title'][0];
				$song->songArtist = $tags['artist'][0];
				$song->albumName = $tags['album'][0];
				$song->albumArtist = $tags['band'][0];
				$song->trackNumber = (array_key_exists('track_number', $tags)) ? $tags['track_number'][0] : null;
				$song->year = $tags['year'][0];
				$song->genre = implode("/", $tags['genre']);
				$song->duration = $mp3Info['playtime_seconds'];
				$song->filepath = $mp3Info['filepath'];

				return $song;
			},
			$mp3s
		);

		$musicDatabase = new MusicDatabase();
		$musicDatabase->resetDatabase();
		$musicDatabase->insertSongs($songs);
	}
}

?>