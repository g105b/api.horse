<?php
namespace App\Http;

use App\Request\RequestEntity;
use App\Response\ResponseEntity;
use Gt\Fetch\Http;
use Gt\Http\Response;

class FetchHandler {
	public function fetchResponse(RequestEntity $requestEntity):ResponseEntity {
		$responseEntity = new ResponseEntity($requestEntity);

		$curlOptions = [
			CURLOPT_TIMEOUT => 5,
		];
		$http = new Http($curlOptions);

		$uri = $requestEntity->getFetchableUri();
		$init = [
			"method" => $requestEntity->method,
		];
		if($headers = $requestEntity->getFetchableHeaders()) {
			$init["headers"] = $headers;
		}
		if($requestEntity->body) {
			$init["body"] = $requestEntity->getFetchableBody();
		}

		$http->fetch($uri, $init)
		->then(function(Response $response)use($responseEntity) {
			$responseEntity->setStatus($response->status, $response->statusText);

			foreach($response->headers as $header) {
				$responseEntity->addHeader(
					$header->getName(),
					$header->getValuesCommaSeparated(),
				);
			}

			return $response->text();
		})->then(function(string $responseText)use($responseEntity) {
			$responseEntity->setBody($responseText);
		});

		$http->wait();
		return $responseEntity;
	}
}
