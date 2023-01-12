<?php

namespace App\Controllers;

use App\Helpers\DeezerApi;
use App\Helpers\MusicDatabase;
use App\Helpers\Utilities;
use Flight;

class ArtistController extends Controller {
	public function artist($artist) {
		$deezerApi = new DeezerApi();
		$search = $deezerApi->search("artist:\"$artist\"");
		$artistId = !empty($search['songs']) ? $search['songs'][0]['artistId'] : null;
		$art = $deezerApi->getArtist($artistId)->picture_big;
		$accentColour = Utilities::getAccentColour($art);

		$albums = $this->getArtistAlbums($artist);

		if ($albums === false) {
			Flight::response()->status(404)->send();
			return;
		}

		$this->view(
			'artist',
			compact(
				'artist',
				'albums',
				'accentColour',
				'art'
			)
	);
	}

	private function getDeezerArtistAlbums($albumId) {
		return null;
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
