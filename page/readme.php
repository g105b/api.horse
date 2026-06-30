<?php
use GT\DomTemplate\Binder;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\GithubFlavoredMarkdownConverter;

function go(Binder $binder):void {
	$markdown = file_get_contents("README.md");
	$converter = new GithubFlavoredMarkdownConverter();
	$binder->bindValue($converter->convert($markdown));
}
