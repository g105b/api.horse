<?php
namespace App\Test\Request;

use App\Request\SecretEntity;
use PHPUnit\Framework\TestCase;

class SecretEntityTest extends TestCase {
	public function testCensoredValue():void {
		$sut = new SecretEntity(
			"EXAMPLE_KEY",
			"example value",
		);
		$expectedCensor = str_repeat(SecretEntity::CENSOR_CHARACTER, SecretEntity::CENSORED_LENGTH);
		$expectedCensor .= "alue";
		self::assertSame($expectedCensor, $sut->censoredValue);
	}

	public function testCensoredValue_short4():void {
		$sut = new SecretEntity(
			"EXAMPLE_KEY",
			"test",
		);
		$expectedCensor = str_repeat(SecretEntity::CENSOR_CHARACTER, SecretEntity::CENSORED_LENGTH);
		$expectedCensor .= "st";
		self::assertSame($expectedCensor, $sut->censoredValue);
	}

	public function testCensoredValue_short3():void {
		$sut = new SecretEntity(
			"EXAMPLE_KEY",
			"php",
		);
		$expectedCensor = str_repeat(SecretEntity::CENSOR_CHARACTER, SecretEntity::CENSORED_LENGTH);
		$expectedCensor .= "p";
		self::assertSame($expectedCensor, $sut->censoredValue);
	}
}
