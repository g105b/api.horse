<?php
use Gt\Http\Response;
use Gt\Routing\Path\DynamicPath;

function go(Response $response, DynamicPath $dynamicPath):void {
	$shareId = $dynamicPath->get("share-id");
	$response->redirect("/request/$shareId/_new/");
}
