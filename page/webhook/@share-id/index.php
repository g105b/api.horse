<?php
use Gt\Http\Response;
use Gt\Routing\Path\DynamicPath;
use Gt\Ulid\Ulid;

function go(Response $response, DynamicPath $dynamicPath):void {
	$shareId = $dynamicPath->get("share-id");
	$response->redirect("/webhook/$shareId/_new/");
}
