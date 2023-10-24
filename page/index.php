<?php
use App\ShareId;
use Gt\Http\Response;
use Gt\Session\Session;

function go(Response $response, ShareId $shareId):void {
	$response->redirect("/request/$shareId/");
}
