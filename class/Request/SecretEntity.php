<?php
namespace App\Request;

class SecretEntity {
	public string $censoredValue;

	public function __construct(
		public string $key,
		private readonly string $value,
	) {
		$charactersToShow = 4;
		while(strlen($value) <= $charactersToShow + 3) {
			$charactersToShow -= 2;
		}

		$this->censoredValue =
			str_repeat("•", min(8, strlen($value) - $charactersToShow)) .
			substr($this->value, -$charactersToShow);
	}
}
