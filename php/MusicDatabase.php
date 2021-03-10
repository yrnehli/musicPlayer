<?php

class MusicDatabase {
	private $conn;

	public function __construct() {
		$this->conn = new PDO("mysql:host={$_ENV['DB_SERVERNAME']};dbname={$_ENV['DB_DBNAME']};charset=utf8mb4", $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);
		$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
	}

	public function insertSong($song) {
		$stmt = $this->conn->prepare(
			"INSERT INTO `songs` (`songName`, `songArtist`, `albumName`, `albumArtist`, `trackNumber`, `year`, `genre`, `duration`, `filepath`)
			VALUES (:songName, :songArtist, :albumName, :albumArtist, :trackNumber, :year, :genre, :duration, :filepath)"
		);
		$stmt->bindParam(":songName", $song->songName);
		$stmt->bindParam(":songArtist", $song->songArtist);
		$stmt->bindParam(":albumName", $song->albumName);
		$stmt->bindParam(":albumArtist", $song->albumArtist);
		$stmt->bindParam(":trackNumber", $song->trackNumber);
		$stmt->bindParam(":year", $song->year);
		$stmt->bindParam(":genre", $song->genre);
		$stmt->bindParam(":duration", $song->duration);
		$stmt->bindParam(":filepath", $song->filepath);
		$stmt->execute();
		return $this->conn->lastInsertId();
	}

	public function insertAlbum($album) {
		$stmt = $this->conn->prepare(
			"INSERT INTO `albums` (`albumName`, `albumArtist`, `albumArtFilepath`)
			VALUES (:albumName, :albumArtist, :albumArtFilepath)"
		);
		$stmt->bindParam(":albumName", $album->albumName);
		$stmt->bindParam(":albumArtist", $album->albumArtist);
		$stmt->bindParam(":albumArtFilepath", $album->albumArtFilepath);
		$stmt->execute();
		return $this->conn->lastInsertId();
	}

	public function insertSongAlbumMapping($songId, $albumId) {
		$stmt = $this->conn->prepare(
			"INSERT INTO `song-album` (`songId`, `albumId`)
			VALUES (:songId, :albumId)"
		);
		$stmt->bindParam(":songId", $songId);
		$stmt->bindParam(":albumId", $albumId);
		$stmt->execute();
	}

	public function getAlbums() {
		$stmt = $this->conn->prepare("SELECT * FROM `albums`");
		$stmt->execute();
		return $stmt->fetchAll();
	}

	public function getSong($songId) {
		$stmt = $this->conn->prepare(
			"SELECT *
			FROM `songs`
			WHERE `id` = :id"
		);
		$stmt->bindParam(":id", $songId);
		$stmt->execute();
		return $stmt->fetch();
	}

	public function getSongs($albumId) {
		$stmt = $this->conn->prepare(
			"SELECT `songs`.*
			FROM `songs`
			INNER JOIN `song-album` ON `songs`.`id` = `song-album`.`songId`
			WHERE `song-album`.`albumId` = :albumId"
		);
		$stmt->bindParam(":albumId", $albumId);
		$stmt->execute();
		return $stmt->fetchAll();
	}

	public function resetDatabase() {
		$this->conn->prepare("DELETE FROM `songs`")->execute();
		$this->conn->prepare("DELETE FROM `albums`")->execute();
	}
}

?>