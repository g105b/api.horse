<?php
use App\Http\FetchHandler;
use App\Http\RateLimiter;
use App\Request\BodyEntityForm;
use App\Request\BodyEntityMultipart;
use App\Request\BodyEntityRaw;
use App\Request\BodyEntityUrlEncoded;
use App\Request\Collection\CollectionEntity;
use App\Request\PrivateRequestRepository;
use App\Request\RequestEntity;
use App\Request\RequestRepository;
use App\Request\SecretRepository;
use App\Response\ResponseRepository;
use App\UnauthorisedUri;
use Gt\Dom\Element;
use Gt\Dom\HTMLDocument;
use Gt\Dom\NodeList;
use Gt\DomTemplate\Binder;
use Gt\Http\Response;
use Gt\Http\Uri;
use Gt\Input\Input;
use Gt\Session\Session;
use Gt\Ulid\Ulid;

function go(
	Input $input,
	HTMLDocument $document,
	Element $element,
	Binder $binder,
	?RequestEntity $requestEntity,
):void {
	$binder->bindData($requestEntity);

	if(!$requestEntity) {
		$element->querySelector("form.delete")->remove();
	}

	$document->querySelectorAll("[autofocus]")->forEach(function(Element $el) {
		$el->autofocus = false;
	});

// TODO: Extract this functionality into a UI class:
	if($editorName = $input->getString("editor")) {
		$editor = $document->querySelector("[data-editor='$editorName']");
		$editor->open = true;

		$editorAction = $input->getString("editor-action");
		if($editorAction === "new") {
			if($multiple = $editor->querySelector(".multiple")) {
				/** @var NodeList<Element> $liList */
				$liList = $multiple->querySelectorAll("li");
				if($lastLi = $liList[count($liList) - 1]) {
					$lastLi->querySelector("label input")->autofocus = true;
				}
			}
		}
	}
}

function do_delete(
	RequestRepository $requestRepository,
	RequestEntity $requestEntity,
	Response $response,
	Uri $uri,
):void {
	if(!$requestRepository instanceof PrivateRequestRepository) {
		$response->redirect(new UnauthorisedUri($uri, __FUNCTION__));
	}
	/** @var PrivateRequestRepository $requestRepository */

	$requestRepository->delete($requestEntity);
	$response->redirect("../");
}

function do_duplicate(
	RequestRepository $requestRepository,
	RequestEntity $requestEntity,
	Response $response,
	Uri $uri,
):void {
	if(!$requestRepository instanceof PrivateRequestRepository) {
		$response->redirect(new UnauthorisedUri($uri, __FUNCTION__));
	}
	/** @var PrivateRequestRepository $requestRepository */

	$baseName = $requestEntity->name ?: $requestEntity->id;
	$copyName = "$baseName (copy)";
	$copyNumber = 2;
	$existingNameList = array_map(
		fn(RequestEntity $request) => $request->name ?: $request->id,
		$requestRepository->retrieveAll(),
	);

	while(in_array($copyName, $existingNameList, true)) {
		$copyName = "$baseName (copy $copyNumber)";
		$copyNumber++;
	}

	/** @var RequestEntity $duplicate */
	$duplicate = unserialize(serialize($requestEntity));
	$duplicateId = $requestRepository->create($copyName)->id;
	$duplicate = $duplicate->with(["id" => $duplicateId]);
	$duplicate->name = $copyName;

	$requestRepository->update($duplicate);
	$response->redirect("../$duplicate->id/");
}

function do_update(
	Input $input,
	Response $response,
	RequestRepository $requestRepository,
	?RequestEntity $requestEntity,
	Uri $uri,
):void {
	if(!$requestRepository instanceof PrivateRequestRepository) {
		$response->redirect(new UnauthorisedUri($uri, __FUNCTION__));
	}
	/** @var PrivateRequestRepository $requestRepository */

	$entityName = $input->getString("name");
	if(!$requestEntity) {
		$requestEntity = $requestRepository->create($entityName ?: null);
	}

	$urlSuffix = "";

	$endpointString = $input->getString("endpoint");
	if(!str_contains($endpointString, "//")) {
		$endpointString = "http://$endpointString";
	}

	if($queryString = parse_url($endpointString, PHP_URL_QUERY)) {
		$endpointString = strtok($endpointString, "?");
		parse_str($queryString, $queryParts);
		if($queryParts) {
			$urlSuffix = "?editor=query-string-parameter";
		}

		foreach($queryParts as $key => $value) {
			$requestEntity->addQueryParameter($key, $value);
		}
	}

	$requestEntity->name = $entityName;
	$requestEntity->method = $input->getString("method");
	$requestEntity->endpoint = $endpointString;

	$requestRepository->update($requestEntity);
	$response->redirect("../$requestEntity->id/$urlSuffix");
}

function do_new_query_parameter(
	Response $response,
	RequestRepository $requestRepository,
	?RequestEntity $requestEntity,
	Uri $uri,
):void {
	if(!$requestRepository instanceof PrivateRequestRepository) {
		$response->redirect(new UnauthorisedUri($uri, __FUNCTION__));
	}
	/** @var PrivateRequestRepository $requestRepository */

	if(!$requestEntity) {
		$requestEntity = $requestRepository->create();
	}

	$requestEntity->addQueryParameter();
	$requestRepository->update($requestEntity);
	$response->redirect("../$requestEntity->id/?editor=query-string-parameter&editor-action=new");
}

function do_save_query_parameter(
	Input $input,
	Response $response,
	RequestRepository $requestRepository,
	RequestEntity $requestEntity,
	Uri $uri,
):void {
	if(!$requestRepository instanceof PrivateRequestRepository) {
		$response->redirect(new UnauthorisedUri($uri, __FUNCTION__));
	}
	/** @var PrivateRequestRepository $requestRepository */

	$queryParameterEntity = $requestEntity->getQueryStringParameterById($input->getString("id"));
	$queryParameterEntity->key = $input->getString("key");
	$queryParameterEntity->value = $input->getString("value");
	$requestRepository->update($requestEntity);
	$response->redirect("../$requestEntity->id/?editor=query-string-parameter");
}

function do_delete_query_parameter(
	Input $input,
	Response $response,
	RequestRepository $requestRepository,
	RequestEntity $requestEntity,
	Uri $uri,
):void {
	if(!$requestRepository instanceof PrivateRequestRepository) {
		$response->redirect(new UnauthorisedUri($uri, __FUNCTION__));
	}
	/** @var PrivateRequestRepository $requestRepository */

	$queryParameterEntity = $requestEntity->getQueryStringParameterById($input->getString("id"));
	$requestEntity->deleteQueryParameterEntity($queryParameterEntity);
	$requestRepository->update($requestEntity);
	$response->redirect("../$requestEntity->id/?editor=query-string-parameter");
}

function do_new_header(
	Response $response,
	RequestRepository $requestRepository,
	?RequestEntity $requestEntity,
	Uri $uri,
):void {
	if(!$requestRepository instanceof PrivateRequestRepository) {
		$response->redirect(new UnauthorisedUri($uri, __FUNCTION__));
	}
	/** @var PrivateRequestRepository $requestRepository */

	if(!$requestEntity) {
		$requestEntity = $requestRepository->create();
	}

	$requestEntity->addHeader();
	$requestRepository->update($requestEntity);
	$response->redirect("../$requestEntity->id/?editor=header&editor-action=new");
}

function do_save_header(
	Input $input,
	Response $response,
	RequestRepository $requestRepository,
	RequestEntity $requestEntity,
	Uri $uri,
):void {
	if(!$requestRepository instanceof PrivateRequestRepository) {
		$response->redirect(new UnauthorisedUri($uri, __FUNCTION__));
	}
	/** @var PrivateRequestRepository $requestRepository */

	$key = $input->getString("key");
	$value = $input->getString("value");

	$headerEntity = $requestEntity->getHeaderById($input->getString("id"));
	$headerEntity->key = $key;
	$headerEntity->value = $value;
	if(strtolower($key) === "content-type") {
		$requestEntity->inferredContentType = false;
	}
	$requestRepository->update($requestEntity);
	$response->redirect("../$requestEntity->id/?editor=header");
}

function do_delete_header(
	Input $input,
	Response $response,
	RequestRepository $requestRepository,
	RequestEntity $requestEntity,
	Uri $uri,
):void {
	if(!$requestRepository instanceof PrivateRequestRepository) {
		$response->redirect(new UnauthorisedUri($uri, __FUNCTION__));
	}
	/** @var PrivateRequestRepository $requestRepository */

	$headerEntity = $requestEntity->getHeaderById($input->getString("id"));
	$requestEntity->deleteHeaderEntity($headerEntity);
	$requestRepository->update($requestEntity);
	$response->redirect("../$requestEntity->id/?editor=header");
}

function do_set_body_type(
	Input $input,
	Response $response,
	RequestRepository $requestRepository,
	?RequestEntity $requestEntity,
	Uri $uri,
):void {
	if(!$requestRepository instanceof PrivateRequestRepository) {
		$response->redirect(new UnauthorisedUri($uri, __FUNCTION__));
	}
	/** @var PrivateRequestRepository $requestRepository */

	if(!$requestEntity) {
		$requestEntity = $requestRepository->create();
	}

	$type = $input->getString("body-type");
	$id = new Ulid("body");
	$bodyEntity = match($type) {
		default => null,
		"form-multipart" => new BodyEntityMultipart($id),
		"form-url" => new BodyEntityUrlEncoded($id),
		"text", "json", "xml" => new BodyEntityRaw($id, $type),
	};

	if($requestEntity->body instanceof BodyEntityForm
	&& $bodyEntity instanceof BodyEntityForm) {
		foreach($requestEntity->body->parameters as $existingParameter) {
			$bodyEntity->addBodyParameter(
				$existingParameter->key,
				$existingParameter->value,
			);
		}
	}

	$foundContentTypeHeader = false;
	if(!$requestEntity->inferredContentType) {
		foreach($requestEntity->headers as $header) {
			if(strtolower($header->key) === "content-type") {
				$foundContentTypeHeader = true;
			}
		}
	}

	if(!$foundContentTypeHeader) {
		$contentType = match($type) {
			"json", "xml" => "application/$type",
			"text" => "text/plain",
			"form-multipart" => "multipart/form-data",
			"form-url" => "application/x-www-form-urlencoded",
			default => "application/octet-stream",
		};
		$requestEntity->inferContentType($contentType);
	}

	$requestEntity->setBody($bodyEntity);
	$requestRepository->update($requestEntity);
	$response->redirect("../$requestEntity->id/?editor=body");
}

function do_save_body_raw(
	RequestRepository $requestRepository,
	RequestEntity $requestEntity,
	Input $input,
	Response $response,
	Uri $uri,
):void {
	if(!$requestRepository instanceof PrivateRequestRepository) {
		$response->redirect(new UnauthorisedUri($uri, __FUNCTION__));
	}
	/** @var PrivateRequestRepository $requestRepository */

	$requestEntity->body->content = $input->getString("body-raw");
	$requestRepository->update($requestEntity);
	$response->redirect("../$requestEntity->id/?editor=body");
}

function do_new_body_parameter(
	RequestRepository $requestRepository,
	RequestEntity $requestEntity,
	Response $response,
	Uri $uri,
):void {
	if(!$requestRepository instanceof PrivateRequestRepository) {
		$response->redirect(new UnauthorisedUri($uri, __FUNCTION__));
	}
	/** @var PrivateRequestRepository $requestRepository */

	$requestEntity->body->addBodyParameter();
	$requestRepository->update($requestEntity);
	$response->redirect("../$requestEntity->id/?editor=body&editor-action=new");
}

function do_save_body_parameter(
	RequestRepository $requestRepository,
	RequestEntity $requestEntity,
	Input $input,
	Response $response,
	Uri $uri,
):void {
	if(!$requestRepository instanceof PrivateRequestRepository) {
		$response->redirect(new UnauthorisedUri($uri, __FUNCTION__));
	}
	/** @var PrivateRequestRepository $requestRepository */

	$bodyParameterEntity = $requestEntity->body->getParameterById($input->getString("id"));
	$bodyParameterEntity->key = $input->getString("key");
	$bodyParameterEntity->value = $input->getString("value");
	$requestRepository->update($requestEntity);
	$response->redirect("../$requestEntity->id/?editor=body");
}

function do_delete_body_parameter(
	RequestRepository $requestRepository,
	RequestEntity $requestEntity,
	Input $input,
	Response $response,
	Uri $uri,
):void {
	if(!$requestRepository instanceof PrivateRequestRepository) {
		$response->redirect(new UnauthorisedUri($uri, __FUNCTION__));
	}
	/** @var PrivateRequestRepository $requestRepository */

	$bodyParameterEntity = $requestEntity->body->getParameterById($input->getString("id"));
	$requestEntity->body->deleteParameter($bodyParameterEntity);
	$requestRepository->update($requestEntity);
	$response->redirect("../$requestEntity->id/?editor=body");
}

function do_send(
	RequestRepository $requestRepository,
	ResponseRepository $responseRepository,
	SecretRepository $secretRepository,
	?RequestEntity $requestEntity,
	FetchHandler $fetchHandler,
	RateLimiter $rateLimiter,
	Response $response,
	Session $session,
	Uri $uri,
):void {
	if(!$requestEntity) {
		$response->reload();
	}

	if(!$requestRepository instanceof PrivateRequestRepository) {
		$response->redirect(new UnauthorisedUri($uri, __FUNCTION__));
	}
	/** @var PrivateRequestRepository $requestRepository */

	$requestEntity = $requestEntity->withInjectedSecrets($secretRepository->getAll());
	$requestUri = $requestEntity->getFetchableUri();
	$rateLimiter->limit($requestUri->getHost(), $session->getId());
	$responseEntity = $fetchHandler->fetchResponse($requestEntity);
// TODO: Stop deleting all the responses after issue #3 is implemented.
	$responseRepository->deleteAll($requestEntity);
	$responseRepository->storeResponse($requestEntity, $responseEntity);
	$response->reload();
}
