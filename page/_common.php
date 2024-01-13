<?php
use App\ShareId;
use Gt\Dom\HTMLDocument;
use Gt\DomTemplate\Binder;
use Gt\Http\Response;
use Gt\Http\Uri;

function go(
	Uri $uri,
	HTMLDocument $document,
):void {
	$uriPath = trim($uri->getPath(), "/");

	foreach($document->querySelectorAll("global-header menu a") as $link) {
		$uriPathFirstSlash = substr($uriPath, 0, strpos($uriPath, "/"));
		if(str_starts_with($link->href, $uriPathFirstSlash)) {
			$link->parentElement->classList->add("selected");
		}
	}

	foreach($document->querySelectorAll("main menu a") as $menuLink) {
		$linkFile = pathinfo($menuLink->href, PATHINFO_FILENAME);
		if(!str_ends_with($uriPath, "/$linkFile")) {
			continue;
		}

		$menuLink->parentElement->classList->add("selected");
	}
}
