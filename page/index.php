<?php
use Gt\Http\Response;

function go(Response $response):void {
	$response->redirect("/request/_new/");
}
