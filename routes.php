<?php

use App\Route;

Route::map("GET", "/", [RootController::class, 'index']);
Route::map("GET", "/queue", [RootController::class, 'queue']);

Route::map("GET", "/album/@albumId", [AlbumController::class, 'index']);

Route::map("GET", "/api/mp3/@songId", [ApiController::class, 'mp3']);
Route::map("GET", "/api/update", [ApiController::class, 'update']);
Route::map("GET", "/api/search", [ApiController::class, 'search']);
Route::map("GET", "/api/song/@songId", [ApiController::class, 'song']);
Route::map("GET", "/api/album/@albumId", [ApiController::class, 'album']);

?>