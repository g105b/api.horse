<?php
namespace App;

use Stringable;

class Slug implements Stringable {
	public function __construct(private readonly string $name) {}

	public function __toString():string {
		$slug = $this->name;
		$slug = preg_replace("/[\s_]/i", "-", $slug);
		$slug = preg_replace("/[^\w-]/", "", $slug);
		$slug = urlencode($slug);

		return strtolower($slug);
	}
}
