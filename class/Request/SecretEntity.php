<?php
namespace App\Request;

class SecretEntity {
	const string CENSOR_CHARACTER = "•";
	const int CENSORED_LENGTH = 8;
	const int CENSORED_CHARACTERS_TO_SHOW = 4;
	public string $censoredValue;

	public function __construct(
		public string $key,
		private readonly string $value,
		bool $showSuffix = true,
	) {
		$censoredCharactersToShow = self::CENSORED_CHARACTERS_TO_SHOW;

		if(strlen($value) <= $censoredCharactersToShow) {
			$censoredCharactersToShow = floor(strlen($value) / 2);
		}

		$this->censoredValue = str_repeat("•", self::CENSORED_LENGTH);
		if($showSuffix) {
			$this->censoredValue .= substr($this->value, -$censoredCharactersToShow);
		}
	}

	public function getSecretValue():string {
		return $this->value;
	}
}
