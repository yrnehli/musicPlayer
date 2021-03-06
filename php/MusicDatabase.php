<?php

class MusicDatabase extends PDO {
	public function __construct() {
		$servername = $_ENV["DB_SERVERNAME"];
		$username = $_ENV["DB_USERNAME"];
		$password = $_ENV["DB_PASSWORD"];
		$dbname = $_ENV["DB_DBNAME"];
		$conn = parent::__construct("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
		$this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
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
	}
}

?>