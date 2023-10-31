<?php
namespace App\Request;

use Stringable;

class HeaderEntity implements Stringable {
	public string $key;
	public string $value;

	public function __construct(
		public readonly string $id,
		string...$keyValueList,
	) {
		$key = array_shift($keyValueList);
		$this->key = $key;
		$fullValue = array_shift($keyValueList);

		while(count($keyValueList) >= 2) {
			$key = array_shift($keyValueList);
			$value = array_shift($keyValueList);

			$fullValue .= "; ";
			$fullValue .= urlencode($key);
			$fullValue .= "=";
			$fullValue .= '"';
			$fullValue .= urlencode($value);
			$fullValue .= '"';
		}

		$this->value = $fullValue;
	}

	public function __toString():string {
		return implode(": ", [
			$this->key,
			$this->value,
		]);
	}
}
