<?php
namespace App\Request;
use App\Request\BodyEntity;

class BodyEntityMultipart extends BodyEntityForm {
	const TYPE_STRING = "Multipart";
	const VALUE_STRING = "form-multipart";

	public string $boundary;

	public function __toString():string {
		$bodyString = "--$this->boundary";

		foreach($this->parameters as $parameter) {
			$bodyString .= "\n";
			$bodyString .= "Content-disposition: form-data; name=\"$parameter->key\"\n\n";
			$bodyString .= $parameter->value;
			$bodyString .= "\n";
			$bodyString .= "--$this->boundary";
		}

		$bodyString .= "--\n";
		return $bodyString;
	}
}
