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
	$numCollections = $binder->bindList($collectionRepository->retrieveAll());
	if($numCollections === 1) {
		$binder->bindKeyValue("single", true);
	}

	$binder->bindData($collection);
}

function do_rename(
	Input $input,
	Response $response,
	Uri $uri,
	CollectionRepository $collectionRepository,
	CollectionEntity $collection,
):void {
	$newName = $input->getString("name");
	$newId = new Slug($newName);
	$collectionRepository->rename($collection, $newName);

// TODO: Refactor this into a class
	$uriPath = $uri->getPath();
	$uriPathParts = explode("/", $uriPath);
	$uriPathParts[3] = $newId;
	$uriPath = implode("/", $uriPathParts);

	$collectionRepository->setCurrent($newId);
	$response->redirect($uriPath);
}

function do_create(
	Input $input,
	Response $response,
	Uri $uri,
	CollectionRepository $collectionRepository,
):void {
	$newName = $input->getString("name");
	$collection = $collectionRepository->create($newName);
	$collectionRepository->save($collection);

// TODO: Refactor this into a class
	$uriPath = $uri->getPath();
	$uriPathParts = explode("/", $uriPath);
	$uriPathParts[3] = $collection->id;
	$uriPath = implode("/", $uriPathParts);

	$collectionRepository->setCurrent($collection);
	$response->redirect($uriPath);
}

function do_change_collection(
	Input $input,
	Response $response,
	Uri $uri,
	CollectionRepository $collectionRepository,
):void {
	$newId = $input->getString("collection");

// TODO: Refactor this into a class
	$uriPath = $uri->getPath();
	$uriPathParts = explode("/", $uriPath);
	$uriPathParts[3] = $newId;
	$uriPath = implode("/", $uriPathParts);

	$collectionRepository->setCurrent($newId);
	$response->redirect($uriPath);
}
