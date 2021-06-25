<?php

use App\Route;

Route::map("GET", "/", "RootController@index");
Route::map("GET", "/queue", "RootController@queue");

Route::map("GET", "/album/@albumId", "AlbumController@album");

Route::map("GET", "/api/update", "ApiController@update");
Route::map("GET", "/api/search", "ApiController@search");
Route::map("GET", "/api/mp3/@songId", "ApiController@mp3");
Route::map("GET", "/api/song/@songId", "ApiController@song");
Route::map("PUT", "/api/spotify/tracks/@songId", "ApiController@spotifyTracks");
Route::map("DELETE", "/api/spotify/tracks/@songId", "ApiController@spotifyTracks");
Route::map("GET", "/api/album/@albumId", "ApiController@album");

?>