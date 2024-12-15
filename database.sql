PRAGMA foreign_keys=ON;
BEGIN TRANSACTION;
CREATE TABLE `albums` (
  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `name` varchar(255) NOT NULL
,  `artist` varchar(255) NOT NULL
,  `genre` varchar(255) NOT NULL
,  `year` integer DEFAULT NULL
,  `artFilepath` varchar(255) NOT NULL
,  UNIQUE (`artist`,`name`)
);
CREATE TABLE `savedSongs` (
  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `songId` varchar(255) NOT NULL
,  `active` integer NOT NULL DEFAULT 1
,  `flagged` integer NOT NULL DEFAULT 0
,  UNIQUE (`songId`)
);
CREATE TABLE `scrobbles` (
  `id` integer  NOT NULL PRIMARY KEY AUTOINCREMENT
,  `artist` varchar(255) NOT NULL DEFAULT ''
,  `track` varchar(255) NOT NULL DEFAULT ''
,  `album` varchar(255) NOT NULL DEFAULT ''
,  `duration` integer NOT NULL
,  `timestamp` datetime NOT NULL DEFAULT current_timestamp
,  `success` integer NOT NULL
);
CREATE TABLE `song_album` (
  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `songId` integer NOT NULL
,  `albumId` integer NOT NULL
,  UNIQUE (`songId`)
,  CONSTRAINT `song_album_albums_id_fk` FOREIGN KEY (`albumId`) REFERENCES `albums` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
,  CONSTRAINT `song_album_songs_id_fk` FOREIGN KEY (`songId`) REFERENCES `songs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
);
CREATE TABLE `songs` (
  `id` integer NOT NULL PRIMARY KEY AUTOINCREMENT
,  `name` varchar(255) NOT NULL
,  `artist` varchar(255) NOT NULL
,  `trackNumber` integer DEFAULT NULL
,  `discNumber` integer DEFAULT NULL
,  `duration` integer NOT NULL
,  `filepath` varchar(255) NOT NULL
,  UNIQUE (`filepath`)
);
DELETE FROM sqlite_sequence;
CREATE VIEW albumDetails AS
SELECT
   song_album.albumId AS albumId,
   COUNT(*) AS length,
   SUM(songs.duration) AS duration
FROM songs
JOIN song_album ON songs.id = song_album.songId
GROUP BY song_album.albumId;
CREATE INDEX "idx_song_album_song_album_songs_id_fk" ON "song_album" (`songId`);
CREATE INDEX "idx_song_album_song_album_albums_id_fk" ON "song_album" (`albumId`);
COMMIT;
