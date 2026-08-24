<?php
use App\Maintenance\DataGarbageCollector;

function go(DataGarbageCollector $garbageCollector):void {
	$garbageCollector->collect();
}
