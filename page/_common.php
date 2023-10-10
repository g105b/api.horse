<?php
use Gt\Dom\HTMLDocument;
use Gt\Http\Response;
use Gt\Http\Uri;

function go(Uri $uri, HTMLDocument $document):void {
	foreach($document->querySelectorAll("global-header menu a") as $link) {
		if(str_starts_with($uri->getPath(), $link->href)) {
			$link->parentElement->classList->add("selected");
		}
	}

	foreach($document->querySelectorAll("main menu a") as $menuLink) {
		$dir = substr($menuLink->href, 0, strpos($menuLink->href, "/", 1));
		if(!str_starts_with($uri->getPath(), $dir)) {
			continue;
		}

		if($menuLink->href === $uri->getPath()) {
			$menuLink->parentElement->classList->add("selected");
		}
	}
}
