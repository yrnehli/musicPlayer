<?php

namespace App\Helpers;

use Exception;

class DeezerPrivateApi {
	private const API_BASE = "http://www.deezer.com/ajax/gw-light.php";
	private $cookies = [];
	private $arl;
	
	public function __construct() {
		$this->arl = $_ENV['DEEZER_ARL'];
	}

	public function authTest() {
		$res = $this->getUser();

		if (!$res->USER->OPTIONS->web_hq) {
			throw new Exception("Deezer Private API: Audio will be limited to 128kbps");
		}
	}

	public function getSongMp3($songId) {
		$songId = DeezerApi::removePrefix($songId);
		$song = $this->getSong($songId);
		$encryptedSong = file_get_contents(
			$this->getSongUrl($song->results->DATA->TRACK_TOKEN)
		);

		return $this->decryptSong($songId, $encryptedSong);
	}

	public function getSong($songId) {
		return $this->request("deezer.pageTrack", json_encode(['sng_id' => DeezerApi::removePrefix($songId)]));
	}

	public function getAlbum($albumId) {
		return $this->request("deezer.pageAlbum", json_encode(['alb_id' => $albumId, 'LANG' => 'en']));
	}

	private function getSongUrl($songToken) {
		$formats = ['MP3_320', 'MP3_128'];

		do {
			$res = json_decode(
				$this->curlRequest(
					"POST",
					"https://media.deezer.com/v1/get_url",
					[],
					json_encode([
						'license_token' => $this->getUser()->USER->OPTIONS->license_token,
						'media' => [[
							'type' => 'FULL',
							'formats' => [
								['cipher' => 'BF_CBC_STRIPE', 'format' => array_shift($formats)]
							]
						]],
						'track_tokens' => [$songToken]
					])
				)
			);
		} while (property_exists($res, 'errors'));

		return $res->data[0]->media[0]->sources[0]->url;
	}

	private function decryptSong($songId, $data) {
		$decryptedData = "";
		$key = $this->getBlowfishKey($songId);
		$iv = hex2bin("0001020304050607");
		$temp = fopen('php://temp', 'r+');
	
		fwrite($temp, $data);
		rewind($temp);
		
		for ($i = 0; $i >= 0; $i++) {
			$chunk = fread($temp, 2048);
		
			if (!$chunk) {
				break;
			}
		
			if ($i % 3 == 0 && strlen($chunk) == 2048) {
				$chunk = openssl_decrypt(
					$chunk,
					'BF-CBC',
					$key,
					OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
					$iv
				);
			}
		
			$decryptedData .= $chunk;
		}
		
		fclose($temp);
	
		return $decryptedData;
	}

	private function getBlowfishKey($songId) {
		$songId = DeezerApi::removePrefix($songId);
		$secret = base64_decode("ZzRlbDU4d2MwenZmOW5hMQ==");
		$songIdMd5 = md5($songId);
		$blowfishKey = "";
	
		for ($i = 0; $i < 16; $i++) {
			$blowfishKey .= chr(
				ord($songIdMd5[$i]) ^ ord($songIdMd5[$i + 16]) ^ ord($secret[$i])
			);
		}
	
		return $blowfishKey;
	}

	private function request($method, $payload = "") {
		$apiToken = ($method === "deezer.getUserData") ? "null" : $this->getUser()->checkForm;
		$res = $this->curlRequest(
			"POST",
			self::API_BASE . "?api_version=1.0&api_token=$apiToken&input=3&method=$method",
			[
				"Cookie: " . $this->buildCookies(),
				"Content-Length: " . strlen($payload),
				"Content-Type: application/json"
			],
			$payload
		);

		return json_decode($res);
	}

	private function buildCookies() {
		$cookie = "";
		$cookies = ["arl" => $this->arl];

		foreach ($this->cookies as $k => $v) {
			$cookies[$k] = $v;
		}
		
		foreach ($cookies as $k => $v) {			
			$cookie .= "$k=$v;";
		}

		return $cookie;
	}

	private function getUser() {
		$res = $this->request("deezer.getUserData");

		if ($res->results->USER->USER_ID === 0) {
			throw new Exception("Deezer Private API: Unauthorised");
		}
		
		return $res->results;
	}

	private function curlRequest($requestType, $url, $headers = [], $payload = "") {
		$curl = curl_init();
	
		curl_setopt_array($curl, [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_CUSTOMREQUEST => $requestType,
			CURLOPT_HTTPHEADER => $headers,
			CURLOPT_HEADERFUNCTION => [$this, "processHeader"],
			CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1
		]);
	
		if ($requestType === 'POST') {
			curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
		}
	
		$res = curl_exec($curl);
	
		curl_close($curl);
	
		return $res;
	}

	private function processHeader($curl, $header) {
		if (preg_match("/Set-Cookie: (.*?)=(.*?);/", $header, $matches)) {
			$this->cookies[$matches[1]] = $matches[2];
		}

		return strlen($header);
	}
}
