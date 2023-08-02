<?php

namespace App\Helpers;

use Exception;
use App\Helpers\Blowfish;

class DeezerPrivateApi {
	private const API_BASE = "https://www.deezer.com/ajax/gw-light.php";
	private const COOKIE_JAR = "DeezerPrivateApi.cookiejar";
	private $arl;
	
	public function __construct() {
		$this->arl = $_ENV['DEEZER_ARL'];
	}

	public function authTest() {
		unlink(DeezerPrivateApi::COOKIE_JAR);
		
		$res = $this->getUser();

		if (!$res->USER->OPTIONS->web_hq) {
			throw new Exception("Deezer Private API: Audio will be limited to 128kbps");
		}
	}

	public function getSongMp3($songId) {
		$songId = DeezerApi::removePrefix($songId);
		$encryptedSong = file_get_contents(
			$this->getSongUrl($songId)
		);

		return $this->decryptSong($songId, $encryptedSong);
	}

	public function getSong($songId) {
		return $this->request("deezer.pageTrack", json_encode(['sng_id' => DeezerApi::removePrefix($songId)]));
	}

	public function getAlbum($albumId) {
		return $this->request("deezer.pageAlbum", json_encode(['alb_id' => $albumId, 'LANG' => 'en']));
	}

	private function getSongUrl($songId) {
		$songId = DeezerApi::removePrefix($songId);
		$song = $this->getSong($songId);

		foreach (['MP3_320', 'MP3_128'] as $format) {
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
								['cipher' => 'BF_CBC_STRIPE', 'format' => $format]
							]
						]],
						'track_tokens' => [$song->results->DATA->TRACK_TOKEN]
					])
				)
			);

			if ($format === "MP3_320" && !property_exists($res->data[0], "errors")) {
				break;
			}

			throw new Exception(
				var_export($res->data[0]->errors, true)
			);
		}

		return $res->data[0]->media[0]->sources[0]->url;
	}

	private function decryptSong($songId, $data) {
		$decryptedData = "";
		$key = $this->getBlowfishKey($songId);
		$iv = hex2bin("0001020304050607");
		$temp = fopen('php://temp', 'r+');
		$bf = new Blowfish($key, Blowfish::BLOWFISH_MODE_CBC, Blowfish::BLOWFISH_PADDING_ZERO, $iv);
	
		fwrite($temp, $data);
		rewind($temp);
		
		for ($i = 0; $i >= 0; $i++) {
			$chunk = fread($temp, 2048);
		
			if (!$chunk) {
				break;
			}
		
			if ($i % 3 === 0 && strlen($chunk) === 2048) {
				$chunk = $bf->decrypt(
					$chunk,
					$key,
					Blowfish::BLOWFISH_MODE_CBC,
					Blowfish::BLOWFISH_PADDING_ZERO,
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
		$apiToken = (in_array($method, ["deezer.getUserData", "deezer.ping"])) ? "" : $this->getUser()->checkForm;
		$requestType = (in_array($method, ["deezer.getUserData", "deezer.ping"])) ? "GET" : "POST";
		$res = $this->curlRequest(
			$requestType,
			self::API_BASE . "?api_version=1.0&api_token=$apiToken&input=3&method=$method",
			["Cookie: " . $this->buildCookies()],
			$payload
		);

		return json_decode($res);
	}

	private function buildCookies() {
		$cookie = "";
		$cookies = ["arl" => $this->arl];

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
			CURLOPT_ENCODING => '',
			CURLOPT_CUSTOMREQUEST => $requestType,
			CURLOPT_HTTPHEADER => $headers,
			CURLOPT_COOKIEJAR => self::COOKIE_JAR,
			CURLOPT_COOKIEFILE => self::COOKIE_JAR
		]);

		if ($requestType === 'POST') {
			curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
		}
	
		$res = curl_exec($curl);
	
		curl_close($curl);
	
		return $res;
	}
}
