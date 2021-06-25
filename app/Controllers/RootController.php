<?php

namespace App\Controllers;

use App\Helpers\MusicDatabase;
use App\Helpers\DeezerPrivateApi;
use Exception;

class RootController extends Controller {
	public function index() {
		$this->authTest();

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

	private function authTest() {
		$deezerPrivateApi = new DeezerPrivateApi();
		
		try {
			$deezerPrivateApi->authTest();
		} catch (Exception $e) {
			die($e->getMessage());
		}
	}

	public function queue() {
		$this->view('queue');
	}
}

?>