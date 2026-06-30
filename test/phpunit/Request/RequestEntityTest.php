<?php
namespace App\Test\Request;

use App\Http\HeaderEntity;
use App\Request\BodyEntityMultipart;
use App\Request\BodyEntityRaw;
use App\Request\BodyEntityUrlEncoded;
use App\Request\BodyParameterEntity;
use App\Request\QueryStringEntity;
use App\Request\RequestEntity;
use App\Request\SecretEntity;
use PHPUnit\Framework\TestCase;

class RequestEntityTest extends TestCase {
	public function testGetRawMessageReturnsIncompleteUntilMethodAndEndpointAreSet():void {
		self::assertSame(
			"INCOMPLETE",
			(new RequestEntity("request-1"))->getRawMessage(),
		);
	}

	public function testGetRawMessageBuildsRequestLineHeadersAndBody():void {
		$sut = new RequestEntity("request-1");
		$sut->method = "post";
		$sut->endpoint = "https://api.example.com/items";
		$sut->queryStringParameters = [
			new QueryStringEntity("query-1", "search", "Api Horse"),
			new QueryStringEntity("query-2", "", "ignored"),
		];
		$sut->headers = [
			new HeaderEntity("header-1", "Accept", "application/json"),
			new HeaderEntity("header-2", "", "ignored"),
		];
		$sut->body = new BodyEntityRaw("body-1", "json");
		$sut->body->content = '{"name":"Api Horse"}';

		self::assertSame(
			implode("\n", [
				"POST /items?search=Api+Horse HTTP/1.1",
				"Host: api.example.com",
				"Accept: application/json",
				"",
				'{"name":"Api Horse"}',
			]),
			$sut->getRawMessage(),
		);
	}

	public function testGetRawMessageAddsMultipartContentTypeBoundary():void {
		$sut = new RequestEntity("request-1");
		$sut->method = "POST";
		$sut->endpoint = "https://api.example.com/upload";
		$sut->body = new BodyEntityMultipart("body-1");
		$sut->body->boundary = "boundary-1";
		$sut->body->parameters = [
			new BodyParameterEntity("param-1", "file", "content"),
		];

		self::assertStringContainsString(
			"Content-type: multipart/form-data; boundary=\"--boundary-1\"",
			$sut->getRawMessage(),
		);
		self::assertStringContainsString(
			"--boundary-1\nContent-disposition: form-data; name=\"file\"\n\ncontent\n--boundary-1--\n",
			$sut->getRawMessage(),
		);
	}

	public function testFetchableValuesAreDerivedFromRequestState():void {
		$sut = new RequestEntity("request-1");
		$sut->method = "PATCH";
		$sut->endpoint = "https://api.example.com/items?existing=keep";
		$sut->queryStringParameters = [
			new QueryStringEntity("query-1", "page", "2"),
			new QueryStringEntity("query-2", "empty", null),
		];
		$sut->headers = [
			new HeaderEntity("header-1", "Authorization", "Bearer token"),
		];
		$sut->body = new BodyEntityUrlEncoded("body-1");
		$sut->body->parameters = [
			new BodyParameterEntity("param-1", "name", "Api Horse"),
		];

		self::assertSame(
			"https://api.example.com/items?existing=keep&page=2&empty",
			(string)$sut->getFetchableUri(),
		);
		self::assertSame(
			["Authorization" => "Bearer token"],
			$sut->getFetchableHeaders(),
		);
		self::assertSame("name=Api+Horse", $sut->getFetchableBody());
	}

	public function testInferContentTypeReplacesExistingContentTypeHeader():void {
		$sut = new RequestEntity("request-1");
		$sut->headers = [
			new HeaderEntity("header-1", "Accept", "application/json"),
			new HeaderEntity("header-2", "Content-Type", "text/plain"),
		];

		$sut->inferContentType("application/json");

		self::assertTrue($sut->inferredContentType);
		self::assertSame(
			[
				"Accept" => "application/json",
				"Content-type" => "application/json",
			],
			$sut->getFetchableHeaders(),
		);
	}

	public function testWithInjectedSecretsReturnsSameInstanceWhenNoSecretsAreProvided():void {
		$sut = new RequestEntity("request-1");

		self::assertSame($sut, $sut->withInjectedSecrets([]));
	}

	public function testWithInjectedSecretsInjectsRequestFieldsWithoutMutatingOriginal():void {
		$sut = new RequestEntity("request-1");
		$sut->endpoint = "https://{{HOST}}/items";
		$sut->queryStringParameters = [
			new QueryStringEntity("query-1", "api_key", "{{API_KEY}}"),
		];
		$sut->headers = [
			new HeaderEntity("header-1", "Authorization", "Bearer {{API_KEY}}"),
		];
		$sut->body = new BodyEntityRaw("body-1", "json");
		$sut->body->content = '{"token":"{{API_KEY}}"}';

		$clone = $sut->withInjectedSecrets([
			new SecretEntity("HOST", "api.example.com"),
			new SecretEntity("API_KEY", "secret-token"),
		]);

		self::assertNotSame($sut, $clone);
		self::assertSame("https://{{HOST}}/items", $sut->endpoint);
		self::assertSame("https://api.example.com/items", $clone->endpoint);
		self::assertSame("secret-token", $clone->queryStringParameters[0]->value);
		self::assertSame("Bearer secret-token", $clone->headers[0]->value);
		self::assertSame('{"token":"secret-token"}', $clone->body->content);
	}

	public function testWithInjectedSecretsInjectsFormBodyParameters():void {
		$sut = new RequestEntity("request-1");
		$sut->endpoint = "https://example.com";
		$sut->body = new BodyEntityUrlEncoded("body-1");
		$sut->body->parameters = [
			new BodyParameterEntity("param-1", "token", "{{API_KEY}}"),
		];

		$clone = $sut->withInjectedSecrets([
			new SecretEntity("API_KEY", "secret-token"),
		]);

		self::assertSame("secret-token", $clone->body->parameters[0]->value);
	}
}
