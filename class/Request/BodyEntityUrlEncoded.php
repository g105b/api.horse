<?php
namespace App\Request;

use App\Request\BodyEntity;

class BodyEntityUrlEncoded extends BodyEntityForm {
	const TYPE_STRING = "URL Encoded";
	const VALUE_STRING = "form-url";

	public function __toString():string {
		return new QueryEncodedKVP($this->parameters ?? []);
	}
}
