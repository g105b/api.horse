<?php
namespace App\Request;

class BodyParameterEntity {
	public function __construct(
		public string $id,
		public string $key,
		public string $value,
	) {}
}
