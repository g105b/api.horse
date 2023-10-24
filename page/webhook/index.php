<?php
use App\ShareId;
use Gt\Http\Response;
use Gt\Routing\Path\DynamicPath;

function go(Response $response, ShareId $shareId):void {
	$response->redirect("/webhook/$shareId/_new/");
}
