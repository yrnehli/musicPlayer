<?php

namespace App\Controllers;

use App\Helpers\MusicDatabase;
use App\Helpers\DeezerApi;
use App\Helpers\DeezerPrivateApi;
use Flight;

class Mp3Controller extends Controller {
	public function index($songId) {
		if (str_starts_with($songId, DeezerApi::DEEZER_ID_PREFIX)) {
			$songId = str_replace(DeezerApi::DEEZER_ID_PREFIX, "", $songId);
			$filepath = "userData/deezer/mp3/$songId";
			
			if (!file_exists($filepath)) {
				$deezerPrivateApi = new DeezerPrivateApi();
				$song = $deezerPrivateApi->getSong($songId);
	
				file_put_contents(
					$filepath,
					($song !== false) ? $song : ""
				);
			}
		} else {
			$db = new MusicDatabase();
			$conn = $db->getConn();
			$stmt = $conn->prepare("SELECT `filepath` FROM `songs` WHERE `id` = :id");
			$stmt->bindParam(":id", $songId);
			$stmt->execute();
			$filepath = $stmt->fetchColumn();
		}
	
		$filesize = filesize($filepath);
	
		if (isset($_SERVER['HTTP_RANGE'])) {
			$bytes = explode(
				"-",
				str_replace("bytes=", "", $_SERVER['HTTP_RANGE'])
			);
			$startOffset = $bytes[0];
			$endOffset = (!empty($bytes[1])) ? $bytes[1] : $filesize;
		} else {
			$startOffset = 0;
			$endOffset = $filesize;
		}
	
		$file = fopen($filepath, 'r');
		fseek($file, $startOffset);
		$data = fread($file, $endOffset - $startOffset);
		fclose($file);
	
		Flight::response()
			->header('Accept-Ranges', 'bytes')
			->header('Content-Type', 'audio/mpeg')
			->header('Content-Range', "bytes $startOffset-" . ($endOffset - 1) . "/$filesize")
			->status(206)
			->write($data)
			->send()
		;
	}
}

?>