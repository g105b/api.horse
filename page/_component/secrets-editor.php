<?php
use App\Request\Collection\CollectionRepository;
use App\Request\Collection\PrivateCollectionRepository;
use App\Request\SecretRepository;
use App\UnauthorisedUri;
use Gt\DomTemplate\Binder;
use Gt\Http\Response;
use Gt\Http\Uri;
use Gt\Input\Input;

function go(
	CollectionRepository $collectionRepository,
	SecretRepository $secretRepository,
	Binder $binder,
):void {
	$showSecretSuffix = $collectionRepository instanceof PrivateCollectionRepository;
	$binder->bindList($secretRepository->getAll($showSecretSuffix));
}

function do_delete(
	CollectionRepository $collectionRepository,
	SecretRepository $secretRepository,
	Input $input,
	Response $response,
	Uri $uri,
):void {
	if(!$collectionRepository instanceof PrivateCollectionRepository) {
		$response->redirect(new UnauthorisedUri($uri, __FUNCTION__));
		return;
	}

	$response->reload();
}

function do_add(
	CollectionRepository $collectionRepository,
	SecretRepository $secretRepository,
	Input $input,
	Response $response,
	Uri $uri,
):void {
	if(!$collectionRepository instanceof PrivateCollectionRepository) {
		$response->redirect(new UnauthorisedUri($uri, __FUNCTION__));
	}

	$secretRepository->create(
		$input->getString("key"),
		$input->getString("value"),
	);
	$response->reload();
}
