<?php
use App\Request\RequestRepository;
use App\ShareId;
use Gt\DomTemplate\Binder;

function go(
	Binder $binder,
	RequestRepository $requestRepository,
	ShareId $shareId,
):void {
	$binder->bindKeyValue("shareId", $shareId);
	$binder->bindList($requestRepository->retrieveAll());
}
