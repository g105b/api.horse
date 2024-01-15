<?php
use App\Request\Collection\CollectionEntity;
use Gt\DomTemplate\Binder;

function go(
	CollectionEntity $collectionEntity,
	Binder $binder,
):void {
	$binder->bindKeyValue("collectionId", $collectionEntity->id);
}
