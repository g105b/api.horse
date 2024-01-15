<?php
use Gt\Dom\Element;
use Gt\Dom\HTMLDocument;
use Gt\Http\Uri;

function go(Uri $uri, Element $element):void {
	$uriPath = $uri->getPath();

	foreach($element->querySelectorAll("menu a") as $link) {
		$uriPathFirstSlash = substr($uriPath, 0, strpos($uriPath, "/", 1));
		if(str_starts_with($link->href, $uriPathFirstSlash)) {
			$link->parentElement->classList->add("selected");
		}
	}
}
