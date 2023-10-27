<?php
namespace App\Request;

class QueryStringEntity {
	public function __construct(
		public string $id,
		public string $key,
		public ?string $value
	) {}
}
