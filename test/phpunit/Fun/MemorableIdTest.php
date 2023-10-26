<?php
namespace App\Test\Fun;

use App\Fun\MemorableId;
use App\Fun\WordAnimal;
use App\Fun\WordDescription;
use App\Fun\WordModifier;
use PHPUnit\Framework\TestCase;

class MemorableIdTest extends TestCase {
	public function testToString():void {
		$sut = new MemorableId("one", "two", "three");
		self::assertSame("one-two-three", (string)$sut);
	}

	public function testToString_generatesRandomInCorrectOrder():void {
		$sut = new MemorableId();
		$string = (string)$sut;
		$stringParts = explode("-", $string);

		foreach($stringParts as $i => $part) {
			if($i === 0) {
				$cases = WordModifier::cases();
			}
			elseif($i === 1) {
				$cases = WordDescription::cases();
			}
			else {
				$cases = WordAnimal::cases();
			}

			$cases = array_map(fn($case) => $case->name, $cases);
			self::assertContains($part, $cases);
		}
	}
}
