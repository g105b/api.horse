<?php
namespace App;

use Stringable;

class ShareId implements Stringable {
	private string $random;

	public function __construct() {
		$this->random = uniqid("blah");
	}

	public function __toString():string {
		return $this->random;
	}
}
