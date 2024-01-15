<?php
use Gt\Http\Response;

function go_before(Response $response):void {
	$response->redirect("./_new");
}
