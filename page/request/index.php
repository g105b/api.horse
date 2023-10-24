<?php
use App\ShareId;
use Gt\Http\Response;
use Gt\Routing\Path\DynamicPath;

function go(Response $response, ShareId $shareId):void {
	$response->redirect("/request/$shareId/_new/");
}
