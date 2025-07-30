<?php
use App\SyntaxHighlighter\JsonSyntaxHighlighter;
use Gt\Dom\HTMLDocument;

function go(HTMLDocument $document):void {
	foreach($document->querySelectorAll(".syntax-highlight") as $i => $jsonElement) {
		$highlighter = new JsonSyntaxHighlighter();
		$highlighter->format($jsonElement);
	}
}
