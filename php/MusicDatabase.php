<?php

class MusicDatabase {
	private $conn;
	
	public function __construct() {
		$servername = env("DB_SERVERNAME");
		$username = env("DB_USERNAME");
		$password = env("DB_PASSWORD");
		$dbname = env("DB_DBNAME");

		$conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}
}

?>