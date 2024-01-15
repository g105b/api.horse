<?php
use App\Collection\CollectionEntity;
use App\ShareId;
use Gt\DomTemplate\Binder;

function go(
	CollectionEntity $collectionEntity,
	Binder $binder,
):void {
	$binder->bindKeyValue("collectionId", $collectionEntity->id);
}
