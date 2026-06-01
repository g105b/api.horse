<?php
namespace App\Http;

use App\Request\RequestEntity;
use App\Response\ResponseEntity;
use Gt\Fetch\Http;
use Gt\Http\Response;

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

		$uri = $requestEntity->getFetchableUri();
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
			$responseEntity->setBody((string)$response->awaitArrayBuffer());
		}
		else {
			$responseEntity->setBody($response->awaitText());
		}

		return $responseEntity;
	}
}
