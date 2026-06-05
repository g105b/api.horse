<?php
use App\Http\UnauthorisedRedirect;
use App\Request\Collection\CollectionEntity;
use App\Request\PrivateRequestRepository;
use App\Request\RequestRepository;
use App\ShareId;
use App\UnauthorisedUri;
use Gt\Input\Input;
use Gt\Dom\Element;
use Gt\DomTemplate\Binder;
use Gt\Http\Response;
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
	if(!$requestRepository instanceof PrivateRequestRepository) {
		if($newRequestLink = $element->querySelector("menu a[href$='/_new/']")) {
			$newRequestLink->href .= "?unauthorised=_new";
		}
	}

	$uriPath = trim($uri->getPath(), "/");

	foreach($element->querySelectorAll("menu a") as $menuLink) {
		$linkFile = pathinfo($menuLink->href, PATHINFO_FILENAME);
		if(!str_ends_with($uriPath, "/$linkFile")) {
			continue;
		}

		$menuLink->parentElement->classList->add("selected");
	}
}

function do_order(
	Input $input,
	Response $response,
	RequestRepository $requestRepository,
	Uri $uri,
):void {
	if(!$requestRepository instanceof PrivateRequestRepository) {
		$response->redirect(new UnauthorisedUri($uri, __FUNCTION__));
	}
	/** @var PrivateRequestRepository $requestRepository */

	$requestId = $input->getString("id");
	$order = $input->getInt("order");
	$idMap = $requestRepository->reorder($requestId, $order);

	$pathParts = explode("/", trim($uri->getPath(), "/"));
	$currentRequestId = $pathParts[3] ?? null;
	if($currentRequestId && isset($idMap[$currentRequestId])) {
		$pathParts[3] = $idMap[$currentRequestId];
		$redirectPath = "/" . implode("/", $pathParts) . "/";
		$query = $uri->getQuery();
		if($query) {
			$redirectPath .= "?$query";
		}

		$response->redirect($redirectPath);
	}

	$response->reload();
}
