<?php
namespace App;

use Gt\Http\Uri;
use Stringable;

readonly class UnauthorisedUri implements Stringable {
	public function __construct(
		private Uri $uri,
		private string $function,
	) {}

	public function __toString():string {
		$function = substr(
			$this->function,
			strrpos($this->function, "\\") + 1,
		);
		return $this->uri->withQueryValue("unauthorised", $function);
	}
}
