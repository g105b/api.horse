<?php
namespace App;

use Gt\Ulid\Ulid;
use Stringable;

class ShareId implements Stringable {
	private string $random;

	public function __construct(?string $prefix = null) {
// TODO: Shorter length for collections - less entropy needed.
		$this->random = new Ulid($prefix);
	}

	public function __toString():string {
		return $this->random;
	}
}
