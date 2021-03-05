<?php

class MusicDatabase extends PDO {
	public function __construct() {
		$servername = env("DB_SERVERNAME");
		$username = env("DB_USERNAME");
		$password = env("DB_PASSWORD");
		$dbname = env("DB_DBNAME");
		$conn = parent::__construct("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
		$this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}
}

?>