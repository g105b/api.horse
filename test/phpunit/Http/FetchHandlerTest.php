<?php
namespace App\Test\Http;

use App\Http\FetchHandler;
use App\Request\BodyEntityRaw;
use App\Request\RequestEntity;
use App\Response\ResponseEntity;
use Gt\Fetch\Http;
use Gt\Http\Header\ResponseHeaders;
use Gt\Http\Response;
use PHPUnit\Framework\TestCase;

class FetchHandlerTest extends TestCase {
	public function testFetchResponse_passesRequestInitAndMapsTextResponse():void {
		$requestEntity = new RequestEntity("request-1");
		$requestEntity->method = "POST";
		$requestEntity->endpoint = "https://example.com/items";
		$requestEntity->addHeader("Accept", "application/json");
		$requestEntity->setBody(new BodyEntityRaw("body-1", "json"));
		$requestEntity->body->content = '{"hello":"horse"}';

		$response = new Response(
			201,
			new ResponseHeaders([
				"Content-Type" => "application/json; charset=utf-8",
				"X-Test" => "yes",
			]),
		);
		$response->getBody()->write('{"ok":true}');

		$http = self::createMock(Http::class);
		$http->expects(self::once())
			->method("awaitFetch")
			->with(
				self::callback(fn($uri) => (string)$uri === "https://example.com/items"),
				[
					"method" => "POST",
					"headers" => [
						"Accept" => "application/json",
					],
					"body" => '{"hello":"horse"}',
				],
			)
			->willReturn($response);

		$sut = new FetchHandler();
		$responseEntity = $sut->fetchResponse($requestEntity, $http);

		self::assertInstanceOf(ResponseEntity::class, $responseEntity);
		self::assertSame(201, $responseEntity->status);
		self::assertSame("Created", $responseEntity->statusText);
		self::assertSame('{"ok":true}', $responseEntity->getBody());
		self::assertSame("application/json", $responseEntity->getContentType());
		self::assertSame("Content-Type: application/json; charset=utf-8; X-Test: yes", $responseEntity->getHeaderSummary());
	}

	public function testFetchResponse_readsImageResponseAsArrayBuffer():void {
		$requestEntity = new RequestEntity("request-1");
		$requestEntity->method = "GET";
		$requestEntity->endpoint = "https://example.com/image.png";

		$imageBytes = "\x89PNG\r\n";
		$response = new Response(
			200,
			new ResponseHeaders([
				"Content-Type" => "image/png",
			]),
		);
		$response->getBody()->write($imageBytes);

		$http = self::createMock(Http::class);
		$http->expects(self::once())
			->method("awaitFetch")
			->willReturn($response);

		$responseEntity = (new FetchHandler())->fetchResponse($requestEntity, $http);

		self::assertSame($imageBytes, $responseEntity->getBody());
		self::assertTrue($responseEntity->isImage());
		self::assertSame(
			"data:image/png;base64," . base64_encode($imageBytes),
			$responseEntity->getBodyDataUri(),
		);
	}

	public function testFetchResponseRewritesConfiguredBehatHostsToFakeServer():void {
		$previousFakeServerUrl = getenv("BEHAT_FAKE_SERVER_URL");
		$previousFakeServerHosts = getenv("BEHAT_FAKE_SERVER_HOSTS");
		putenv("BEHAT_FAKE_SERVER_URL=http://127.0.0.1:9876");
		putenv("BEHAT_FAKE_SERVER_HOSTS=example.com,api.example.test");

		try {
			$requestEntity = new RequestEntity("request-1");
			$requestEntity->method = "GET";
			$requestEntity->endpoint = "https://example.com/path/to-resource?request=1";

			$response = new Response(
				200,
				new ResponseHeaders(["Content-Type" => "text/plain"]),
			);
			$response->getBody()->write("fake response");

			$http = self::createMock(Http::class);
			$http->expects(self::once())
				->method("awaitFetch")
				->with(
					self::callback(fn($uri) => (string)$uri === "http://127.0.0.1:9876/path/to-resource?request=1"),
					["method" => "GET"],
				)
				->willReturn($response);

			$responseEntity = (new FetchHandler())->fetchResponse($requestEntity, $http);

			self::assertSame("fake response", $responseEntity->getBody());
		}
		finally {
			self::restoreEnv("BEHAT_FAKE_SERVER_URL", $previousFakeServerUrl);
			self::restoreEnv("BEHAT_FAKE_SERVER_HOSTS", $previousFakeServerHosts);
		}
	}

	private static function restoreEnv(string $name, string|false $value):void {
		if($value === false) {
			putenv($name);
			return;
		}

		putenv("$name=$value");
	}
}
