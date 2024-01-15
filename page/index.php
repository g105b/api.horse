<?php
use App\ShareId;
use Gt\Http\Response;

function go_before(
	ShareId $shareId,
	Response $response,
):void {
	$response->redirect("/request/$shareId");
}
