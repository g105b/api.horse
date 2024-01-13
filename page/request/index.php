<?php
use App\Collection\CollectionEntity;
use App\ShareId;
use Gt\Http\Response;
use Gt\Routing\Path\DynamicPath;

function go(
	ShareId $shareId,
	CollectionEntity $collectionEntity,
	Response $response,
):void {
	$response->redirect("/request/$shareId/$collectionEntity->id/_new/");
}
