<?php
use App\Collection\CollectionEntity;
use App\Request\RequestRepository;
use App\ShareId;
use Gt\DomTemplate\Binder;

function go(
	ShareId $shareId,
	CollectionEntity $collectionEntity,
	Binder $binder,
	RequestRepository $requestRepository,
):void {
	$binder->bindKeyValue("shareId", $shareId);
	$binder->bindKeyValue("collectionId", $collectionEntity->id);
	$binder->bindList($requestRepository->retrieveAll());
}
