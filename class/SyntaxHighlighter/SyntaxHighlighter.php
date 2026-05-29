<?php
namespace App\SyntaxHighlighter;

use Gt\Dom\Element;

abstract class SyntaxHighlighter {
	const CONTENT_TYPE_CLASS_MAP = [
		"application/json" => JsonSyntaxHighlighter::class,
		"application/xml" => XmlSyntaxHighlighter::class,
		"text/xml" => XmlSyntaxHighlighter::class,
		"application/xhtml+xml" => XmlSyntaxHighlighter::class,
		"image/svg+xml" => XmlSyntaxHighlighter::class,
		"text/html" => HtmlSyntaxHighlighter::class,
	];

	abstract public function format(Element $element, string $rawBody):void;
}
