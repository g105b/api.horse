<?php
use App\Request\Collection\CollectionEntity;
use App\Request\Collection\CollectionRepository;
use App\Request\Collection\PrivateCollectionRepository;
use App\Slug;
use Gt\DomTemplate\Binder;
use Gt\Http\Response;
use Gt\Http\Uri;
use Gt\Input\Input;
use Gt\Routing\Path\DynamicPath;

function go(
	CollectionRepository $collectionRepository,
	CollectionEntity $collection,
	DynamicPath $dynamicPath,
	Binder $binder,
):void {
	$binder->bindKeyValue("shared", !($collectionRepository instanceof PrivateCollectionRepository));

	if($collectionRepository instanceof PrivateCollectionRepository) {
		$numCollections = $binder->bindList($collectionRepository->retrieveAll());
	}
	else {
		$current = $collectionRepository->retrieve($dynamicPath->get("collection-id"));
		$numCollections = $binder->bindList([$current]);
	}

	if($numCollections === 1) {
		$binder->bindKeyValue("single", true);
	}

	$binder->bindData($collection);
}

function do_rename(
	CollectionRepository $collectionRepository,
	CollectionEntity $collection,
	Input $input,
	Response $response,
	Uri $uri,
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
	CollectionRepository $collectionRepository,
	Input $input,
	Response $response,
	Uri $uri,
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
	CollectionRepository $collectionRepository,
	Input $input,
	Response $response,
	Uri $uri,
):void {
	$newId = $input->getString("collection");

// TODO: Refactor this into a class
	$uriPath = $uri->getPath();
	$uriPathParts = explode("/", $uriPath);
	$uriPathParts[3] = $newId;
	$uriPathParts[4] = "_new";
	$uriPath = implode("/", $uriPathParts);

	$collectionRepository->setCurrent($newId);
	$response->redirect($uriPath);
}

function do_delete(
	CollectionRepository $collectionRepository,
	CollectionEntity $collection,
	Response $response,
	Uri $uri,
):void {
	$collectionRepository->delete($collection);

	$firstCollection = $collectionRepository->retrieveAll()[0] ?? null;
	if(!$firstCollection) {
		$firstCollection = $collectionRepository->create();
	}
	$collectionRepository->setCurrent($firstCollection);
	$newId = $firstCollection->id;

	$uriPath = $uri->getPath();
	$uriPathParts = explode("/", $uriPath);
	$uriPathParts[3] = $newId;
	$uriPathParts[4] = "_new";
	$uriPath = implode("/", $uriPathParts);
	$response->redirect($uriPath);
}
