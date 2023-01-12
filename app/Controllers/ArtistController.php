<?php

namespace App\Controllers;

use App\Helpers\DeezerApi;
use App\Helpers\MusicDatabase;
use App\Helpers\Utilities;
use Flight;

class ArtistController extends Controller {
	public function artist($artist) {
		$deezerApi = new DeezerApi();
		$isDeezerArtist = str_starts_with($artist, DeezerApi::DEEZER_ID_PREFIX);

		if (!$isDeezerArtist) {
			$albums = $this->getArtistAlbums($artist);
			$search = $deezerApi->search("artist:\"$artist\" album:\"{$albums[0]['name']}\"");
			$deezerArtistId = !empty($search['songs']) ? $search['songs'][0]['artistId'] : null;
		} else {
			$deezerArtistId = $artist;
		}

		if ($deezerArtistId) {
			$data = $deezerApi->getArtist($deezerArtistId);
			$artistName = ($isDeezerArtist) ? $data->name : $artist;
			$art = $data->picture_big;
			$accentColour = Utilities::getAccentColour($art);
		} else {
			$accentColour = '#121212';
		}

		if ($isDeezerArtist) {
			$albums = array_map(function($album) use ($artistName) {
				return [
					'id' => DeezerApi::DEEZER_ID_PREFIX . $album->id,
					'artFilepath' => $album->cover_big,
					'name' => $album->title,
					'artist' => $artistName
				];
			}, $deezerApi->getArtistAlbums($deezerArtistId));
		}

		if ($albums === false) {
			Flight::response()->status(404)->send();
			return;
		}

		$this->view(
			'artist',
			compact(
				'artistName',
				'albums',
				'accentColour',
				'art'
			)
		);
	}

	private function getArtistAlbums($artist) {
		$db = new MusicDatabase();
		$conn = $db->getConn();

		$stmt = $conn->prepare(
			"SELECT *
			FROM `albums`
			WHERE `artist` = :artist
			ORDER BY `year` DESC"
		);
		$stmt->bindParam(":artist", $artist);
		$stmt->execute();
		$albums = $stmt->fetchAll();

		return $albums;
	}
}
