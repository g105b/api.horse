<?php
use App\Request\RequestEntity;
use App\Response\ResponseRepository;
use Gt\DomTemplate\Binder;

function go(Binder $binder, RequestEntity $requestEntity, ResponseRepository $responseRepository):void {
	$binder->bindList($responseRepository->getAll($requestEntity));
}
