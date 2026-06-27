<?php
namespace App\Test\Request;

use App\Request\BodyEntityMultipart;
use App\Request\BodyEntityRaw;
use App\Request\BodyEntityRawCanNotHaveBodyParametersException;
use App\Request\BodyEntityUrlEncoded;
use App\Request\BodyParameterEntity;
use App\Request\QueryEncodedKVP;
use App\Request\QueryStringEntity;
use PHPUnit\Framework\TestCase;

class BodyEntityTest extends TestCase {
	public function testUrlEncodedBodySerialisesParameters():void {
		$sut = new BodyEntityUrlEncoded("body-1");
		$sut->parameters = [
			new BodyParameterEntity("param-1", "first name", "Api"),
			new BodyParameterEntity("param-2", "token", "abc+123"),
			new BodyParameterEntity("param-3", "", "ignored"),
		];

		self::assertSame(
			"first+name=Api&token=abc%2B123",
			(string)$sut,
		);
	}

	public function testMultipartBodySerialisesParametersWithBoundary():void {
		$sut = new BodyEntityMultipart("body-1");
		$sut->boundary = "boundary-1";
		$sut->parameters = [
			new BodyParameterEntity("param-1", "name", "Api Horse"),
			new BodyParameterEntity("param-2", "token", "abc123"),
		];

		self::assertSame(
			implode("\n", [
				"--boundary-1",
				"Content-disposition: form-data; name=\"name\"",
				"",
				"Api Horse",
				"--boundary-1",
				"Content-disposition: form-data; name=\"token\"",
				"",
				"abc123",
				"--boundary-1--",
				"",
			]),
			(string)$sut,
		);
	}

	public function testRawBodyCannotHaveParameters():void {
		$this->expectException(BodyEntityRawCanNotHaveBodyParametersException::class);

		(new BodyEntityRaw("body-1", "json"))->addBodyParameter("name", "value");
	}

	public function testQueryEncodedKvpIgnoresBlankKeys():void {
		$sut = new QueryEncodedKVP([
			new QueryStringEntity("query-1", "search", "Api Horse"),
			new QueryStringEntity("query-2", "", "ignored"),
		]);

		self::assertSame("search=Api+Horse", (string)$sut);
	}
}
