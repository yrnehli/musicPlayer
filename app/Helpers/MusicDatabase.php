<?php

namespace App\Helpers;

use PDO;

class MusicDatabase {
	private $conn;

	public function __construct() {
		$this->conn = new PDO("mysql:host={$_ENV['DB_SERVERNAME']};dbname={$_ENV['DB_DBNAME']};charset=utf8mb4", $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);
		$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
	}

	public function insertScrobble($artist, $track, $album, $duration, $timestamp, $success) {
		$stmt = $this->conn->prepare(
			"INSERT INTO `scrobbles` (`artist`, `track`, `album`, `duration`, `timestamp`, `success`)
			VALUES (:artist, :track, :album, :duration, :timestamp, :success)"
		);
		$stmt->bindParam(":artist", $artist);
		$stmt->bindParam(":track", $track);
		$stmt->bindParam(":album", $album);
		$stmt->bindParam(":duration", $duration);
		$stmt->bindParam(":timestamp", $timestamp);
		$stmt->bindParam(":success", $success, PDO::PARAM_INT);
		$stmt->execute();
	}

	public function insertSong($name, $artist, $trackNumber, $discNumber, $duration, $filepath) {
		$stmt = $this->conn->prepare(
			"INSERT INTO `songs` (`name`, `artist`, `trackNumber`, `discNumber`, `duration`, `filepath`)
			VALUES (:name, :artist, :trackNumber, :discNumber, :duration, :filepath)
			ON DUPLICATE KEY UPDATE
				`name` = :name,
				`artist` = :artist,
				`trackNumber` = :trackNumber,
				`discNumber` = :discNumber,
				`duration` = :duration,
				`filepath` = :filepath,
				`id` = LAST_INSERT_ID(`id`)
			"
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
			VALUES (:name, :artist, :genre, :year, :artFilepath)
			ON DUPLICATE KEY UPDATE
				`name` = :name,
				`artist` = :artist,
				`genre` = :genre,
				`year` = :year,
				`artFilepath` = :artFilepath,
				`id` = LAST_INSERT_ID(`id`)
			"
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
			VALUES (:songId, :albumId)
			ON DUPLICATE KEY UPDATE `albumId` = :albumId"
		);
		$stmt->bindParam(":songId", $songId);
		$stmt->bindParam(":albumId", $albumId);
		$stmt->execute();
	}

	public function resetDatabase() {
		$this->conn->prepare("DELETE FROM `songs`")->execute();
		$this->conn->prepare("DELETE FROM `albums`")->execute();
	}

	public function insertSavedSong($songId, $flagged = false) {
		$stmt = $this->conn->prepare(
			"INSERT INTO `savedSongs` (`songId`, `flagged`)
			VALUES (:songId, :flagged)
			ON DUPLICATE KEY UPDATE `flagged` = :flagged"
		);
		$stmt->bindParam(":songId", $songId);
		$stmt->bindParam(":flagged", $flagged, PDO::PARAM_INT);
		$stmt->execute();
	}

	public function deleteSavedSong($songId) {
		$stmt = $this->conn->prepare(
			"DELETE FROM `savedSongs`
			WHERE `songId` = :songId"
		);
		$stmt->bindParam(":songId", $songId);
		$stmt->execute();
	}

	public function isSongSaved($songId) {
		$stmt = $this->conn->prepare(
			"SELECT *
			FROM `savedSongs`
			WHERE `songId` = :songId
			AND `active` = 1"
		);
		$stmt->bindParam(":songId", $songId);
		$stmt->execute();
		$res = $stmt->fetch();

		return ($res !== false);
	}

	public function isSongFlagged($songId) {
		$stmt = $this->conn->prepare(
			"SELECT `flagged`
			FROM `savedSongs`
			WHERE `songId` = :songId"
		);
		$stmt->bindParam(":songId", $songId);
		$stmt->execute();
		$res = $stmt->fetch(PDO::FETCH_COLUMN);

		return ($res === "1");
	}

	public function getSongs() {
		$stmt = $this->conn->prepare("SELECT * FROM `songs`");
		$stmt->execute();
		$res = $stmt->fetchAll();

		return $res;
	}

	public function deleteSong($songId) {
		$stmt = $this->conn->prepare(
			"DELETE FROM `songs`
			WHERE `id` = :id"
		);
		$stmt->bindParam(":id", $songId);
		$stmt->execute();
	}

	public function getConn() {
		return $this->conn;
	}
}
