<?php
use App\Request\BodyEntityForm;
use App\Request\BodyEntityMultipart;
use App\Request\BodyEntityRaw;
use App\Request\BodyEntityUrlEncoded;
use App\Request\RequestEntity;
use App\Request\RequestRepository;
use Gt\Dom\Element;
use Gt\Dom\HTMLDocument;
use Gt\Dom\NodeList;
use Gt\DomTemplate\Binder;
use Gt\Http\Response;
use Gt\Input\Input;
use Gt\Routing\Path\DynamicPath;
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
		$element->querySelector("button[value=delete-request]")->remove();
	}

// TODO: Extract this functionality into a UI class:
	if($editorName = $input->getString("editor")) {
		$document->querySelector("[autofocus]")->autofocus = false;

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

function do_delete_request(
	RequestRepository $requestRepository,
	RequestEntity $requestEntity,
	Response $response,
):void {
	$requestRepository->delete($requestEntity);
	$response->redirect("../");
}

function do_update(
	Input $input,
	Response $response,

	RequestRepository $requestRepository,
	?RequestEntity $requestEntity,
):void {
	if(!$requestEntity) {
		$requestEntity = $requestRepository->create();
	}

	$endpointString = $input->getString("endpoint");
	if($queryString = parse_url($endpointString, PHP_URL_QUERY)) {
		$endpointString = strtok($endpointString, "?");
		parse_str($queryString, $queryParts);
		if($queryParts) {
			$requestEntity->queryStringParameters = [];
		}

		foreach($queryParts as $key => $value) {
			$requestEntity->addQueryParameter($key, $value);
		}
	}

	$requestEntity->name = $input->getString("name");
	$requestEntity->method = $input->getString("method");
	$requestEntity->endpoint = $endpointString;
	$requestRepository->update($requestEntity);

	$response->redirect("../$requestEntity->id/");
}

function do_new_query_parameter(
	Response $response,
	RequestRepository $requestRepository,
	?RequestEntity $requestEntity,
):void {
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
):void {
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
):void {
	$queryParameterEntity = $requestEntity->getQueryStringParameterById($input->getString("id"));
	$requestEntity->deleteQueryParameterEntity($queryParameterEntity);
	$requestRepository->update($requestEntity);
	$response->redirect("../$requestEntity->id/?editor=query-string-parameter");
}

function do_new_header(
	Response $response,
	RequestRepository $requestRepository,
	?RequestEntity $requestEntity,
):void {
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
):void {
	$headerEntity = $requestEntity->getHeaderById($input->getString("id"));
	$headerEntity->key = $input->getString("key");
	$headerEntity->value = $input->getString("value");
	$requestRepository->update($requestEntity);
	$response->redirect("../$requestEntity->id/?editor=header");
}

function do_delete_header(
	Input $input,
	Response $response,
	RequestRepository $requestRepository,
	RequestEntity $requestEntity,
):void {
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
):void {
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

	$requestEntity->setBody($bodyEntity);
	$requestRepository->update($requestEntity);
	$response->redirect("../$requestEntity->id/?editor=body");
}

function do_save_body_raw(
	Input $input,
	Response $response,
	RequestRepository $requestRepository,
	RequestEntity $requestEntity,
):void {
	$requestEntity->body->content = $input->getString("body-raw");
	$requestRepository->update($requestEntity);
	$response->redirect("../$requestEntity->id/?editor=body");
}

function do_new_body_parameter(
	Input $input,
	Response $response,
	RequestRepository $requestRepository,
	RequestEntity $requestEntity,
):void {
	$requestEntity->body->addBodyParameter();
	$requestRepository->update($requestEntity);
	$response->redirect("../$requestEntity->id/?editor=body");
}

function do_save_body_parameter(
	Input $input,
	Response $response,
	RequestRepository $requestRepository,
	RequestEntity $requestEntity,
):void {
	$bodyParameterEntity = $requestEntity->body->getParameterById($input->getString("id"));
	$bodyParameterEntity->key = $input->getString("key");
	$bodyParameterEntity->value = $input->getString("value");
	$requestRepository->update($requestEntity);
	$response->redirect("../$requestEntity->id/?editor=body");
}

function do_delete_body_parameter(
	Input $input,
	Response $response,
	RequestRepository $requestRepository,
	RequestEntity $requestEntity,
):void {
	$bodyParameterEntity = $requestEntity->body->getParameterById($input->getString("id"));
	$requestEntity->body->deleteParameter($bodyParameterEntity);
	$requestRepository->update($requestEntity);
	$response->redirect("../$requestEntity->id/?editor=body");
}
