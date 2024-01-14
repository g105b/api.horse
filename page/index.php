<?php
use App\Collection\CollectionEntity;
use App\ShareId;
use Gt\Http\Response;
use Gt\Session\Session;

function go_before(
	ShareId $shareId,
	Response $response,
):void {
	$response->redirect("/request/$shareId");
}
