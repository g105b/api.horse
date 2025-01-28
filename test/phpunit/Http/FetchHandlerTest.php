<?php
namespace App\Test\Http;

use App\Http\FetchHandler;
use App\Request\RequestEntity;
use App\Response\ResponseEntity;
use Gt\Fetch\Http;
use Gt\Http\Response;
use Gt\Http\Uri;
use Gt\Promise\Promise;
use PHPUnit\Framework\TestCase;

class FetchHandlerTest extends TestCase {
	/**
	 * Fake an HTTP request/response by emulating the correct Promise-based
	 * flow to test the construction of the expected body text.
	 */
	public function testFetchResponse():void {
		$exampleUri = self::createMock(Uri::class);
		$expectedBody = "Hello Horse!";

		$http = self::createMock(Http::class);
		$requestEntity = self::createMock(RequestEntity::class);
		$requestEntity->expects(self::once())
			->method("getFetchableUri")
			->willReturn($exampleUri);

		$requestEntity->expects(self::once())
			->method("getMethod")
			->willReturn("GET");

		$responseTextPromise = self::createMock(Promise::class);
		$responseTextPromise->method("then")
			->willReturnCallback(function(\Closure $closure)use($expectedBody) {
				$refFunc = new \ReflectionFunction($closure);
				$refParams = $refFunc->getParameters();
				$paramType = $refParams[0]?->getType()->getName();
				if($paramType === "string") {
					$closure($expectedBody);
				}
				return self::createMock(Promise::class);
			});
		$responsePromise = self::createMock(Promise::class);
		$responsePromise->method("then")
			->willReturnCallback(function()use($responseTextPromise) {
				return $responseTextPromise;
			});

		$http->expects(self::once())
			->method("fetch")
			->willReturn($responsePromise);

		$sut = new FetchHandler();
		$responseEntity = $sut->fetchResponse($requestEntity, $http);

		self::assertEquals($expectedBody, $responseEntity->getBody());
	}
}
