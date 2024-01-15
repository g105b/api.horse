<?php
use App\Request\Collection\CollectionEntity;
use App\ShareId;
use Gt\Http\Response;

function go_before(
	ShareId $shareId,
	CollectionEntity $collectionEntity,
	Response $response,
):void {
	$response->redirect("/request/$shareId/$collectionEntity->id/_new/");
}
