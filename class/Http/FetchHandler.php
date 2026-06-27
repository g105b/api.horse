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
	public function fetchResponse(
		RequestEntity $requestEntity,
		?Http $http = null,
	):ResponseEntity {
		$responseEntity = new ResponseEntity();

		$curlOptions = [
			CURLOPT_TIMEOUT => 5,
		];
		/** @var Http $http */
		if(!$http) {
			$http = new Http($curlOptions);
		}

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

		foreach($response->headers as $header) {
			$responseEntity->addHeader(
				$header->getName(),
				$header->getValuesCommaSeparated(),
			);
		}

		if(str_starts_with(strtolower($response->type), "image/")) {
			$responseEntity->setBody($this->arrayBufferToString(
				$response->awaitArrayBuffer(),
			));
		}
		else {
			$responseEntity->setBody($response->awaitText());
		}

		return $responseEntity;
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
