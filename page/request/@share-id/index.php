<?php
use App\Collection\CollectionEntity;
use App\ShareId;
use Gt\Http\Response;

function go(
	Response $response,
	ShareId $shareId,
	CollectionEntity $collectionEntity,
):void {
	$response->redirect("/request/$shareId/$collectionEntity->id/_new/");
}
