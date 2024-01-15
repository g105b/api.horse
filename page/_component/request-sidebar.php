<?php
use App\Collection\CollectionEntity;
use App\Request\RequestRepository;
use App\ShareId;
use Gt\Dom\Element;
use Gt\Dom\HTMLDocument;
use Gt\DomTemplate\Binder;
use Gt\Http\Uri;

function go(
	ShareId $shareId,
	CollectionEntity $collectionEntity,
	Binder $binder,
	Element $element,
	RequestRepository $requestRepository,
	Uri $uri,
):void {
	$binder->bindKeyValue("shareId", $shareId);
	$binder->bindKeyValue("collectionId", $collectionEntity->id);
	$binder->bindList($requestRepository->retrieveAll());

	$uriPath = trim($uri->getPath(), "/");

	foreach($element->querySelectorAll("menu a") as $menuLink) {
		$linkFile = pathinfo($menuLink->href, PATHINFO_FILENAME);
		if(!str_ends_with($uriPath, "/$linkFile")) {
			continue;
		}

		$menuLink->parentElement->classList->add("selected");
	}
}
