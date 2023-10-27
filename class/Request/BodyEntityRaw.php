<?php
namespace App\Request;

use App\Request\BodyEntity;

class BodyEntityRaw extends BodyEntity {
	public function addBodyParameter(string $key = "", string $value = ""):void {
		throw new BodyEntityRawCanNotHaveBodyParametersException();
	}
}
