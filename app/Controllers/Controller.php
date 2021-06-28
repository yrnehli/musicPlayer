<?php

namespace App\Controllers;

use App\Helpers\MusicDatabase;
use Flight;
use PDO;

class Controller {
	protected function view($name, $data = []) {
		Flight::render('searchResults', [], 'searchResults');

		if (filter_var(Flight::request()->query->partial, FILTER_VALIDATE_BOOLEAN)) {
			Flight::render($name, $data);
			return;
		}

		Flight::render($name, $data, 'partial');
		Flight::render('control', ['songIds' => $this->getSongIds()], 'control');
		Flight::render('windowControlsOverlay', [], 'windowControlsOverlay');
		Flight::render('shell');
	}

	private function getSongIds() {
		$db = new MusicDatabase();
		$conn = $db->getConn();
		
		$stmt = $conn->prepare("SELECT `id` FROM `songs`");
		$stmt->execute();
		$songIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

		return $songIds;
	}

	protected function responseHandler($success, $message = "", $data = []) {
		Flight::json(
			compact('success', 'message', 'data')
		);
		die();
	}
}

?>