<?php
use App\SyntaxHighlighter\JsonSyntaxHighlighter;
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
	$bindCount = $binder->bindList($responseRepository->getAll($requestEntity));

	$detailsList = $element->querySelectorAll("ul>li>details");
	if($lastDetailsElement = $detailsList[$detailsList?->count() - 1]) {
		$lastDetailsElement->open = true;
	}

	if($bindCount === 0) {
		$element->querySelector("button[name=do][value=clear]")->remove();
	}

	foreach($element->querySelectorAll("http-message") as $httpMessageElement) {
		$contentType = $httpMessageElement->dataset->get("content-type");
		$formatter = null;
		if($contentType === "application/json") {
			$formatter = new JsonSyntaxHighlighter();
		}

		$formatter?->format($httpMessageElement);
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
