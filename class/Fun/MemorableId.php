<?php
namespace App\Fun;

use Stringable;

class MemorableId implements Stringable {
	public string $modifier;
	public string $description;
	public string $animal;

	public function __construct(
		string $modifier = null,
		string $description = null,
		string $animal = null,
	) {
		$this->modifier = $modifier ?? $this->randomModifier();
		$this->description = $description ?? $this->randomDescription();
		$this->animal = $animal ?? $this->randomAnimal();
	}

	public function __toString():string {
		return implode("-", [
			$this->modifier,
			$this->description,
			$this->animal,
		]);
	}

	private function randomModifier():string {
		return $this->randomWord(WordModifier::class);
	}

	private function randomDescription():string {
		return $this->randomWord(WordDescription::class);
	}

	private function randomAnimal():string {
		return $this->randomWord(WordAnimal::class);
	}

	/** @param class-string<WordModifier|WordDescription|WordAnimal> $enum */
	private function randomWord(string $enum):string {
		$wordValues = $enum::cases();
		$randomWord = $wordValues[array_rand($wordValues)];
		return $randomWord->name;
	}
}
