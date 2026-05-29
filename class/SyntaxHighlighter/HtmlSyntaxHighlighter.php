<?php
namespace App\SyntaxHighlighter;

use DOMDocument;
use DOMNode;
use Gt\Logger\Log;

class HtmlSyntaxHighlighter extends MarkupSyntaxHighlighter {
	/** @return null|array<DOMNode> */
	protected function parse(string $rawBody):?array {
		$document = new DOMDocument();
		$document->preserveWhiteSpace = true;
		$previousUseInternalErrors = libxml_use_internal_errors(true);

		try {
			$loaded = $document->loadHTML(
				$rawBody,
				LIBXML_NONET | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
			);
			$errorList = libxml_get_errors();
			libxml_clear_errors();

			if(!$loaded || $errorList) {
				$errorMessageList = array_map(
					fn($error) => trim($error->message),
					$errorList,
				);
				if($errorMessageList) {
					Log::info("Error decoding HTML - " . implode("; ", $errorMessageList));
				}
				return null;
			}

			return iterator_to_array($document->childNodes);
		}
		finally {
			libxml_use_internal_errors($previousUseInternalErrors);
		}
	}

	protected function getSyntaxHighlighterClassName():string {
		return "syntax-highlighter-html";
	}
}
