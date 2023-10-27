<?php
namespace App;

use Gt\Ulid\Ulid;
use Stringable;

class ShareId implements Stringable {
	private string $random;

	public function __construct() {
		$this->random = new Ulid();
	}

	public function __toString():string {
		return $this->random;
	}
}
