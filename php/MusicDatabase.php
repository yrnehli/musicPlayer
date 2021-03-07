<?php

class MusicDatabase {
	private $conn;

	public function __construct() {
		$this->conn = new PDO("mysql:host={$_ENV['DB_SERVERNAME']};dbname={$_ENV['DB_DBNAME']};charset=utf8mb4", $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);
		$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}

	public function insertSongs($songs) {
		foreach ($songs as $song) {
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
		}
	}

	public function resetDatabase() {
		$stmt = $this->conn->prepare("DELETE FROM `songs`")->execute();
	}
}

?>