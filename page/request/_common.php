<?php
use App\Collection\CollectionEntity;
use App\ShareId;
use Gt\DomTemplate\Binder;

function go(
	ShareId $shareId,
	CollectionEntity $collectionEntity,
	Binder $binder,
):void {
	$binder->bindKeyValue("shareId", $shareId);
	$binder->bindKeyValue("collectionId", $collectionEntity->id);
}
