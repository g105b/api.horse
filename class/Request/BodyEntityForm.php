<?php
namespace App\Request;

use Gt\Ulid\Ulid;

abstract class BodyEntityForm extends BodyEntity {
	public function addBodyParameter(string $key = "", string $value = ""):void {
		if(is_null($this->parameters)) {
			$this->parameters = [];
		}

		array_push(
			$this->parameters,
			new BodyParameterEntity(
				new Ulid("bodyparam"),
				$key,
				$value,
			)
		);
	}
}
