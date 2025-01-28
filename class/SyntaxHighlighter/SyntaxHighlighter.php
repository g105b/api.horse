<?php
namespace App\SyntaxHighlighter;

use Gt\Dom\Element;

abstract class SyntaxHighlighter {
	abstract public function format(Element $element):void;
}
