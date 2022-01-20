<?php

use App\Route;

Route::map("GET", "/", "RootController@index");
Route::map("GET", "/auth", "RootController@auth");
Route::map("GET", "/queue", "QueueController@index");
Route::map("GET", "/saved", "SavedController@index");
Route::map("GET", "/saved/export", "SavedController@export");
Route::map("GET", "/saved/clear", "SavedController@clear");
Route::map("GET", "/album/@albumId", "AlbumController@album");
Route::map("GET", "/mp3/@songId", "Mp3Controller@song");
Route::map("GET", "/api/update", "ApiController@update");
Route::map("GET", "/api/search", "ApiController@search");
Route::map("GET", "/api/song/@songId", "ApiController@song");
Route::map("GET", "/api/album/@albumId", "ApiController@album");
Route::map("PUT", "/api/saved/@songId", "ApiController@saved");
Route::map("DELETE", "/api/saved/@songId", "ApiController@saved");
Route::map("GET", "/api/scrobble/@songId", "ApiController@scrobble");

?>
