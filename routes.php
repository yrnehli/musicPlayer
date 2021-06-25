<?php

use App\Route;

Route::map("GET", "/", "RootController@index");
Route::map("GET", "/queue", "RootController@queue");

Route::map("GET", "/mp3/@songId", "Mp3Controller@song");

Route::map("GET", "/album/@albumId", "AlbumController@album");

Route::map("GET", "/api/update", "ApiController@update");
Route::map("GET", "/api/search", "ApiController@search");
Route::map("GET", "/api/song/@songId", "ApiController@song");
Route::map("GET", "/api/album/@albumId", "ApiController@album");
Route::map("PUT", "/api/spotify/tracks/@songId", "ApiController@spotifyTracks");
Route::map("DELETE", "/api/spotify/tracks/@songId", "ApiController@spotifyTracks");

?>