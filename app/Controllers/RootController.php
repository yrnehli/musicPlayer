<?php

namespace App\Controllers;

use App\Helpers\MusicDatabase;
use App\Helpers\Utilities;

class RootController extends Controller {
	public function index() {
		$db = new MusicDatabase();
		$conn = $db->getConn();
		
		$stmt = $conn->prepare(
			"SELECT *
			FROM `albums`
			ORDER BY RAND()"
		);
		$stmt->execute();
		$albums = $stmt->fetchAll();

		$this->view('home', compact('albums'));
	}

	public function queue() {
		$this->view('queue');
	}
}

?>