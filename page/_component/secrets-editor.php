<?php
use App\Request\SecretRepository;
use Gt\DomTemplate\Binder;
use Gt\Http\Response;
use Gt\Input\Input;

function go(
	SecretRepository $secretRepository,
	Binder $binder,
):void {
	$binder->bindList($secretRepository->getAll());
}

function do_delete(
	SecretRepository $secretRepository,
	Input $input,
	Response $response,
):void {
//	$secretRepository->remove($input->getString("key"));
	$response->reload();
}

function do_add(
	SecretRepository $secretRepository,
	Input $input,
	Response $response,
):void {
	$secretRepository->create(
		$input->getString("key"),
		$input->getString("value"),
	);
	$response->reload();
}
