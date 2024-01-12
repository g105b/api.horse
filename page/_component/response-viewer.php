<?php
use App\Request\RequestEntity;
use App\Response\ResponseRepository;
use Gt\Dom\Element;
use Gt\DomTemplate\Binder;
use Gt\Http\Response;

function go(
	Element $element,
	Binder $binder,
	?RequestEntity $requestEntity,
	ResponseRepository $responseRepository,
):void {
	$binder->bindList($responseRepository->getAll($requestEntity));

	if($firstResponseDetails = $element->querySelector("ul>li>details")) {
		$firstResponseDetails->open = true;
	}
}

function do_clear(
	RequestEntity $requestEntity,
	ResponseRepository $responseRepository,
	Response $response,
):void {
	$responseRepository->deleteAll($requestEntity);
	$response->reload();
}
