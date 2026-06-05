<?php
use Gt\Dom\Element;
use Gt\Http\Uri;
use GT\Input\Input;

function go(
	Element $element,
	Input $input,
	Uri $uri,
):void {
	$uriPath = $uri->getPath();

	foreach($element->querySelectorAll("menu a") as $link) {
		$uriPathFirstSlash = substr($uriPath, 0, strpos($uriPath, "/", 1));
		if(str_starts_with($link->href, $uriPathFirstSlash)) {
			$link->parentElement->classList->add("selected");
		}
	}

	if($input->contains("unauthorised")) {
		$element->querySelector("#sharedReadOnlyDialog")->open = true;
	}
}
