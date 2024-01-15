<?php
namespace App\Request\Collection;

use Gt\Http\Uri;

/** TODO: This is obsolete now the Collection is a RequestCollection. */
enum CollectionMode {
	case request;
	case webhook;

	public static function fromUri(Uri $uri):self {
		$path = trim($uri->getPath(), "/");
		$pathParts = explode("/", $path);
		$modeString = $pathParts[0];

		foreach(self::cases() as $case) {
			if($case->name === $modeString) {
				return $case;
			}
		}

		return self::request;
	}
}
