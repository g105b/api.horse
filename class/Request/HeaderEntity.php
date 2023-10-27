<?php
namespace App\Request;

class HeaderEntity {
	public function __construct(
		public readonly string $id,
		public string $key,
		public string $value,
	) {}
}
