<?php
namespace App\SyntaxHighlighter;

use Gt\Dom\Element;

abstract class SyntaxHighlighter {
	const CONTENT_TYPE_CLASS_MAP = [
		"application/json" => JsonSyntaxHighlighter::class,
	];

	abstract public function format(Element $element):void;
}
