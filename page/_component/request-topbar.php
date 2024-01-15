<?php
use App\Request\Collection\CollectionEntity;
use App\Request\Collection\CollectionRepository;
use App\Slug;
use Gt\DomTemplate\Binder;
use Gt\Http\Response;
use Gt\Http\Uri;
use Gt\Input\Input;

function go(
	Binder $binder,
	CollectionRepository $collectionRepository,
	CollectionEntity $collection,
):void {
	$binder->bindData($collection);

	$numCollections = $binder->bindList($collectionRepository->retrieveAll());
	if($numCollections === 1) {
		$binder->bindKeyValue("single", true);
	}
}

function do_rename(
	Input $input,
	Response $response,
	Uri $uri,
	CollectionRepository $collectionRepository,
	CollectionEntity $collection,
):void {
	$newName = $input->getString("name");
	$collectionRepository->rename($collection, $newName);

	$uriPath = $uri->getPath();
	$uriPathParts = explode("/", $uriPath);
	$uriPathParts[3] = new Slug($newName);
	$uriPath = implode("/", $uriPathParts);

	$response->redirect($uriPath);
}
