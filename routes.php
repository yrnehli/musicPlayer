<?php

use App\Route;

Route::map("GET", "/", "RootController@index");
Route::map("GET", "/queue", "RootController@queue");
Route::map("GET", "/saved", "RootController@saved");

Route::map("GET", "/album/@albumId", "AlbumController@album");

Route::map("GET", "/mp3/@songId", "Mp3Controller@song");

Route::map("GET", "/api/update", "ApiController@update");
Route::map("GET", "/api/search", "ApiController@search");
Route::map("GET", "/api/song/@songId", "ApiController@song");
Route::map("GET", "/api/album/@albumId", "ApiController@album");
Route::map("PUT", "/api/deezerSavedSongs/@songId", "ApiController@deezerSavedSongs");
Route::map("DELETE", "/api/deezerSavedSongs/@songId", "ApiController@deezerSavedSongs");

?>