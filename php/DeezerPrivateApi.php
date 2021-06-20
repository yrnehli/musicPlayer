<?php

class DeezerPrivateApi {
	private const API_BASE = "http://www.deezer.com/ajax/gw-light.php";
	private $arl;
	private $sid;
	
	public function __construct() {
		$this->arl = $_ENV['DEEZER_ARL'];
	}

	public function getSong($songId) {
		try {
			$songData = $this->getSongData($songId);
			$encryptedSong = @file_get_contents(
				$this->getSongUrl(
					$songId,
					$songData['md5'],
					$songData['mediaVersion']
				)
			);

			if ($encryptedSong === false) {
				return false;
			}

			return $this->decryptSong($songId, $encryptedSong);
		} catch (Exception $e) {
			return false;
		}
	}

	public function getSongData($songId) {
		$regex = "/\(?feat\.? .*|\(?ft\.? .*|\(with .*|\(Deluxe.*|- Bonus.*/i";
		$res = json_decode($this->request("deezer.pageTrack", json_encode(['sng_id' => $songId])));
		$data = $res->results->DATA;

		return [
			'md5' => $data->MD5_ORIGIN,
			'mediaVersion' => $data->MEDIA_VERSION,
			'songName' => trim(preg_replace($regex, '', $data->SNG_TITLE)),
			'songArtist' => implode(
				", ",
				array_map(
					function($artist) {
						return $artist['ART_NAME'];
					},
					json_decode(json_encode($data->ARTISTS), true)
				)
			),
			'albumArtUrl' => "https://cdns-images.dzcdn.net/images/cover/$data->ALB_PICTURE/500x500.jpg",
			'albumName' => trim(preg_replace($regex, '', $data->ALB_TITLE)),
			'albumId' => DeezerApi::DEEZER_ID_PREFIX . $data->ALB_ID
		];
	}

	private function getSongUrl($songId, $md5, $mediaVersion) {
		$format = 3; // 320 kbps
		$blockSize = 16;
		$key = "jo6aey6haid2Teih";
	
		$urlPart = implode(
			"a4",
			[
				$this->str2hex($md5),
				$this->str2hex($format),
				$this->str2hex($songId),
				$this->str2hex($mediaVersion),
			]
		);
		$urlPartMd5 = md5(hex2bin($urlPart));
		$urlPart = $this->str2hex($urlPartMd5) . "a4" . $urlPart . "a4";
		$padLength = ceil(strlen($urlPart) / 2 / $blockSize) * $blockSize * 2;
		$urlPart = bin2hex(
			openssl_encrypt(
				hex2bin(
					str_pad(
						$urlPart,
						$padLength,
						str_pad(
							dechex(($padLength - strlen($urlPart)) / 2),
							2,
							"0",
							STR_PAD_LEFT
						)
					)
				),
				"AES-128-ECB",
				$key,
				OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING
			)
		);
	
		return "https://e-cdns-proxy-" . $md5[0] . ".dzcdn.net/mobile/1/$urlPart";
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
		$secret = "g4el58wc0zvf9na1";
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
		$apiToken = ($method === "deezer.getUserData") ? "null" : $this->getApiToken();
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

		return $res;
	}

	private function buildCookies() {
		$cookie = "";
		$cookies = ["arl" => $this->arl];

		if ($this->sid) {
			$cookies['sid'] = $this->sid;
		}
		
		foreach ($cookies as $k => $v) {			
			$cookie .= "$k=$v;";
		}

		return $cookie;
	}

	private function getApiToken() {
		$res = json_decode($this->request("deezer.getUserData"));
		
		if ($res->results->USER->USER_ID === 0) {
			throw new Exception();
		}
		
		return $res->results->checkForm;
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
		if (preg_match("/Set-Cookie: sid=(.*?);/", $header, $matches)) {
			$this->sid = $matches[1];
		}

		return strlen($header);
	}

	private function str2hex($string) {	
		return implode(
			unpack("H*", strval($string))
		);
	}
}

?>