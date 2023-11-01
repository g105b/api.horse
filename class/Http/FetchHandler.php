<?php
namespace App\Http;

use App\Request\RequestEntity;
use App\Response\ResponseEntity;
use Gt\Fetch\Http;
use Gt\Http\Response;

class FetchHandler {
	public function fetchResponse(RequestEntity $requestEntity):ResponseEntity {
		$responseEntity = new ResponseEntity($requestEntity);

		$http = new Http();

		$uri = $requestEntity->getFetchableUri();
		$init = [
			"method" => $requestEntity->method,
			"headers" => $requestEntity->getFetchableHeaders(),
		];
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
