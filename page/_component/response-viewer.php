<?php
use App\Http\UnauthorisedRedirect;
use App\SyntaxHighlighter\JsonSyntaxHighlighter;
use App\Request\PrivateRequestRepository;
use App\Request\RequestEntity;
use App\Request\RequestRepository;
use App\Response\ResponseRepository;
use App\SyntaxHighlighter\SyntaxHighlighter;
use Gt\Dom\Element;
use Gt\DomTemplate\Binder;
use Gt\Http\Response;
use Gt\Http\Uri;

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

	if(count($responseEntityList) <= 1) {
		$element->querySelector("button[name=do][value=clear]")->hidden = true;
	}

	foreach($element->querySelectorAll("http-message") as $i => $httpMessageElement) {
		$responseEntity = $responseEntityList[$i] ?? null;
		if(!$responseEntity || is_null($responseEntity->body)) {
			continue;
		}

		if($responseEntity->isImage()) {
			$responseBodyElement = $httpMessageElement->querySelector(".response-body");
			$responseBodyElement->hidden = true;
			$responseBodyElement->textContent = "";
			$imageContainer = $httpMessageElement->querySelector(".response-image");
			$imageContainer->hidden = false;
			$imageContainer->querySelector("img")->src = $responseEntity->getBodyDataUri();
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
	RequestRepository $requestRepository,
	RequestEntity $requestEntity,
	ResponseRepository $responseRepository,
	Response $response,
	Uri $uri,
):void {
	if(!$requestRepository instanceof PrivateRequestRepository) {
		UnauthorisedRedirect::redirect($response, $uri);
		return;
	}

	$responseRepository->deleteAll($requestEntity);
	$response->reload();
}
