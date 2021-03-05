<?php

class MusicDatabase extends PDO {
	public function __construct() {
		$servername = $_ENV["DB_SERVERNAME"];
		$username = $_ENV["DB_USERNAME"];
		$password = $_ENV["DB_PASSWORD"];
		$dbname = $_ENV["DB_DBNAME"];
		$conn = parent::__construct("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
		$this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}
}

?>