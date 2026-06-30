<?php
namespace App\Test\Response;

use App\Request\SecretEntity;
use App\Response\ResponseEntity;
use PHPUnit\Framework\TestCase;

class ResponseEntityTest extends TestCase {
	public function testWithRedactedSecretsRedactsHeadersAndBodyWithSuffix():void {
		$sut = new ResponseEntity();
		$sut->setStatus(200, "OK");
		$sut->addHeader("Content-type", "application/json");
		$sut->addHeader("X-Token", "Bearer secret-value");
		$sut->setBody('{"token":"secret-value"}');

		$redacted = $sut->withRedactedSecrets([
			new SecretEntity("TOKEN", "secret-value"),
		]);

		self::assertNotSame($sut, $redacted);
		self::assertSame("Bearer ••••••••alue", $redacted->headers[1]->value);
		self::assertSame('{"token":"••••••••alue"}', $redacted->body);
		self::assertSame("Bearer secret-value", $sut->headers[1]->value);
		self::assertSame('{"token":"secret-value"}', $sut->body);
	}

	public function testWithRedactedSecretsRedactsHeadersAndBodyWithoutSuffix():void {
		$sut = new ResponseEntity();
		$sut->setStatus(200, "OK");
		$sut->addHeader("Content-type", "text/plain");
		$sut->addHeader("X-Token", "Bearer secret-value");
		$sut->setBody("The token is secret-value.");

		$redacted = $sut->withRedactedSecrets([
			new SecretEntity("TOKEN", "secret-value", false),
		]);

		self::assertSame("Bearer ••••••••", $redacted->headers[1]->value);
		self::assertSame("The token is ••••••••.", $redacted->body);
	}
}
