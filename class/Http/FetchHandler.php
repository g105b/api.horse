<?php
namespace App\Http;

use App\Request\RequestEntity;
use App\Response\ResponseEntity;
use Gt\Http\ArrayBuffer;
use Gt\Fetch\Http;
use Gt\Http\Response;
use Gt\Http\Uri;
use Psr\Http\Message\UriInterface;

class FetchHandler {
	public const int CONNECT_TIMEOUT_SECONDS = 2;
	public const int REQUEST_TIMEOUT_SECONDS = 5;
	public const int LOW_SPEED_LIMIT_BYTES = 1_024;
	public const int LOW_SPEED_TIME_SECONDS = 3;
	public const int MAX_REDIRECTS = 5;
	public const int MAX_REQUEST_BODY_BYTES = 5 * 1_024 * 1_024;
	public const int MAX_RESPONSE_HEADER_BYTES = 64 * 1_024;
	public const int MAX_RESPONSE_BODY_BYTES = 10 * 1_024 * 1_024;

	public function fetchResponse(
		RequestEntity $requestEntity,
		?Http $http = null,
	):ResponseEntity {
		$this->assertRequestBodySize($requestEntity);

		if(!$http) {
			return $this->fetchResponseWithCurl($requestEntity);
		}

		$responseEntity = new ResponseEntity();

		$uri = $this->getFetchUri($requestEntity->getFetchableUri());
		$init = [
			"method" => $requestEntity->getMethod(),
		];
		if($headers = $requestEntity->getFetchableHeaders()) {
			$init["headers"] = $headers;
		}
		if($requestEntity->body) {
			$init["body"] = $requestEntity->getFetchableBody();
		}

		$response = $http->awaitFetch($uri, $init);
		$responseEntity->setStatus($response->status, $response->statusText);

		$responseHeaderBytes = 0;
		foreach($response->headers as $header) {
			$responseHeaderBytes += strlen($header->getName())
				+ strlen($header->getValuesCommaSeparated())
				+ 4;
			if($responseHeaderBytes > self::MAX_RESPONSE_HEADER_BYTES) {
				throw new \RuntimeException("Response headers exceed the maximum size of " . self::MAX_RESPONSE_HEADER_BYTES . " bytes.");
			}
			$responseEntity->addHeader(
				$header->getName(),
				$header->getValuesCommaSeparated(),
			);
		}

		if(str_starts_with(strtolower($response->type), "image/")) {
			$body = $this->arrayBufferToString(
				$response->awaitArrayBuffer(),
			);
		}
		else {
			$body = $response->awaitText();
		}
		$this->assertResponseBodySize($body);
		$responseEntity->setBody($body);

		return $responseEntity;
	}

	private function fetchResponseWithCurl(RequestEntity $requestEntity):ResponseEntity {
		$responseEntity = new ResponseEntity();
		$uri = $this->getFetchUri($requestEntity->getFetchableUri());
		$responseHeaderList = [];
		$responseStatusText = "";
		$responseHeaderBytes = 0;
		$responseHeadersTooLarge = false;
		$responseBodyTooLarge = false;
		$body = "";

		$curlHandle = curl_init((string)$uri);
		curl_setopt_array($curlHandle, [
			CURLOPT_CUSTOMREQUEST => $requestEntity->getMethod(),
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HEADER => false,
			CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT_SECONDS,
			CURLOPT_HEADERFUNCTION => function(
				$curlHandle,
				string $rawHeader,
			)use(
				&$responseHeaderList,
				&$responseStatusText,
				&$responseHeaderBytes,
				&$responseHeadersTooLarge,
			):int {
				$responseHeaderBytes += strlen($rawHeader);
				if($responseHeaderBytes > self::MAX_RESPONSE_HEADER_BYTES) {
					$responseHeadersTooLarge = true;
					return 0;
				}

				$headerLine = trim($rawHeader);

				if(preg_match("/^HTTP\\/\\S+\\s+(\\d{3})(?:\\s+(.*))?$/", $headerLine, $match)) {
					$responseHeaderList = [];
					$responseStatusText = $match[2] ?? "";
					return strlen($rawHeader);
				}

				if($headerLine !== "" && str_contains($headerLine, ":")) {
					[$key, $value] = explode(":", $headerLine, 2);
					$responseHeaderList []= [trim($key), trim($value)];
				}

				return strlen($rawHeader);
			},
			CURLOPT_LOW_SPEED_LIMIT => self::LOW_SPEED_LIMIT_BYTES,
			CURLOPT_LOW_SPEED_TIME => self::LOW_SPEED_TIME_SECONDS,
			CURLOPT_MAXREDIRS => self::MAX_REDIRECTS,
			CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
			CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
			CURLOPT_TIMEOUT => self::REQUEST_TIMEOUT_SECONDS,
			CURLOPT_USERAGENT => Http::USER_AGENT,
			CURLOPT_WRITEFUNCTION => function(
				$curlHandle,
				string $content,
			)use(&$body, &$responseBodyTooLarge):int {
				if(strlen($body) + strlen($content) > self::MAX_RESPONSE_BODY_BYTES) {
					$responseBodyTooLarge = true;
					return 0;
				}

				$body .= $content;
				return strlen($content);
			},
		]);

		if($headers = $requestEntity->getFetchableHeaders()) {
			$headerLineList = [];
			foreach($headers as $key => $value) {
				if($key === "") {
					continue;
				}
				$headerLineList []= "$key: $value";
			}

			curl_setopt($curlHandle, CURLOPT_HTTPHEADER, $headerLineList);
		}

		if(!is_null($requestEntity->body)) {
			curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $requestEntity->getFetchableBody());
		}

		if(curl_exec($curlHandle) === false) {
			$error = curl_error($curlHandle);
			curl_close($curlHandle);
			if($responseHeadersTooLarge) {
				throw new \RuntimeException("Response headers exceed the maximum size of " . self::MAX_RESPONSE_HEADER_BYTES . " bytes.");
			}
			if($responseBodyTooLarge) {
				throw new \RuntimeException("Response body exceeds the maximum size of " . self::MAX_RESPONSE_BODY_BYTES . " bytes.");
			}
			throw new \RuntimeException("Unable to fetch response: $error");
		}

		$status = curl_getinfo($curlHandle, CURLINFO_RESPONSE_CODE);
		curl_close($curlHandle);

		if($responseStatusText === "") {
			$responseStatusText = (new Response($status))->statusText;
		}

		$responseEntity->setStatus($status, $responseStatusText);
		foreach($responseHeaderList as [$key, $value]) {
			$responseEntity->addHeader($key, $value);
		}
		$responseEntity->setBody($body);

		return $responseEntity;
	}

	private function assertRequestBodySize(RequestEntity $requestEntity):void {
		$body = $requestEntity->getFetchableBody();
		if(!is_null($body) && strlen($body) > self::MAX_REQUEST_BODY_BYTES) {
			throw new \RuntimeException("Request body exceeds the maximum size of " . self::MAX_REQUEST_BODY_BYTES . " bytes.");
		}
	}

	private function assertResponseBodySize(string $body):void {
		if(strlen($body) > self::MAX_RESPONSE_BODY_BYTES) {
			throw new \RuntimeException("Response body exceeds the maximum size of " . self::MAX_RESPONSE_BODY_BYTES . " bytes.");
		}
	}

	private function getFetchUri(UriInterface $requestUri):UriInterface {
		$fakeServerUrl = getenv("BEHAT_FAKE_SERVER_URL") ?: null;
		$fakeServerHosts = getenv("BEHAT_FAKE_SERVER_HOSTS") ?: null;
		if(!$fakeServerUrl || !$fakeServerHosts) {
			return $requestUri;
		}

		$hostList = array_map(
			trim(...),
			explode(",", $fakeServerHosts),
		);
		if(!in_array($requestUri->getHost(), $hostList, true)) {
			return $requestUri;
		}

		$fakeServerUri = new Uri($fakeServerUrl);
		return $fakeServerUri
			->withPath($requestUri->getPath())
			->withQuery($requestUri->getQuery());
	}

	private function arrayBufferToString(ArrayBuffer $arrayBuffer):string {
		$body = "";
		foreach($arrayBuffer as $byte) {
			$body .= chr($byte);
		}

		return $body;
	}
}
