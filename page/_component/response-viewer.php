<?php
use App\SyntaxHighlighter\JsonSyntaxHighlighter;
use App\Request\RequestEntity;
use App\Response\ResponseRepository;
use App\SyntaxHighlighter\SyntaxHighlighter;
use Gt\Dom\Element;
use Gt\DomTemplate\Binder;
use Gt\Http\Response;

function go(
	Element $element,
	Binder $binder,
	?RequestEntity $requestEntity,
	ResponseRepository $responseRepository,
):void {
	$responseEntityList = $responseRepository->getAll($requestEntity);
	$bindCount = $binder->bindList($responseEntityList);

	$detailsList = $element->querySelectorAll("ul>li>details");
	if($lastDetailsElement = $detailsList[$detailsList?->count() - 1]) {
		$lastDetailsElement->open = true;
	}

	if($bindCount === 0) {
		$element->querySelector("button[name=do][value=clear]")->remove();
	}

	foreach($element->querySelectorAll("http-message") as $i => $httpMessageElement) {
		$responseEntity = $responseEntityList[$i] ?? null;
		if(!$responseEntity || is_null($responseEntity->body)) {
			continue;
		}

		$contentType = $httpMessageElement->dataset->get("content-type");
		/** @var ?SyntaxHighlighter $formatter */
		$formatter = null;
		if(array_key_exists($contentType, SyntaxHighlighter::CONTENT_TYPE_CLASS_MAP)) {
			$formatterClassName = SyntaxHighlighter::CONTENT_TYPE_CLASS_MAP[$contentType];
			$formatter = new $formatterClassName();
		}

		$formatter?->format($httpMessageElement, $responseEntity->body);
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
