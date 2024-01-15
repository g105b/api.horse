<?php
use Gt\DomTemplate\Binder;
use Gt\Input\Input;

function go(Input $input, Binder $binder):void {
	$feature = $input->getString("feature");
	$binder->bindKeyValue("feature", (bool)$feature);
}
