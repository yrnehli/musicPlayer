<?php

class MusicDatabase {
	private $conn;

	public function __construct() {
		$this->conn = new PDO("mysql:host={$_ENV['DB_SERVERNAME']};dbname={$_ENV['DB_DBNAME']};charset=utf8mb4", $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);
		$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
	}

	public function insertSong($name, $artist, $trackNumber, $discNumber, $duration, $filepath) {
		$stmt = $this->conn->prepare(
			"INSERT INTO `songs` (`name`, `artist`, `trackNumber`, `discNumber`, `duration`, `filepath`)
			VALUES (:name, :artist, :trackNumber, :discNumber, :duration, :filepath)"
		);
		$stmt->bindParam(":name", $name);
		$stmt->bindParam(":artist", $artist);
		$stmt->bindParam(":trackNumber", $trackNumber);
		$stmt->bindParam(":discNumber", $discNumber);
		$stmt->bindParam(":duration", $duration);
		$stmt->bindParam(":filepath", $filepath);
		$stmt->execute();
		
		return $this->conn->lastInsertId();
	}

	public function insertAlbum($name, $artist, $genre, $year, $artFilepath) {
		$stmt = $this->conn->prepare(
			"INSERT INTO `albums` (`name`, `artist`, `genre`, `year`, `artFilepath`)
			VALUES (:name, :artist, :genre, :year, :artFilepath)"
		);
		$stmt->bindParam(":name", $name);
		$stmt->bindParam(":artist", $artist);
		$stmt->bindParam(":genre", $genre);
		$stmt->bindParam(":year", $year);
		$stmt->bindParam(":artFilepath", $artFilepath);
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

	public function resetDatabase() {
		$this->conn->prepare("DELETE FROM `songs`")->execute();
		$this->conn->prepare("DELETE FROM `albums`")->execute();
	}

	public function getConn() {
		return $this->conn;
	}
}

?>