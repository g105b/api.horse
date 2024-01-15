<?php
use App\ShareId;
use Gt\DomTemplate\Binder;

function go(
	ShareId $shareId,
	Binder $binder,
):void {
	$binder->bindKeyValue("shareId", $shareId);
}
