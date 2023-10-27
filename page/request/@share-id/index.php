<?php
use App\ShareId;
use Gt\Http\Response;

function go(Response $response, ShareId $shareId):void {
	$response->redirect("/request/$shareId/_new/");
}
