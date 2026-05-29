<?php
namespace App\SyntaxHighlighter;

use DOMDocument;
use Gt\Logger\Log;

class XmlSyntaxHighlighter extends MarkupSyntaxHighlighter {
	/** @return null|array<\DOMNode> */
	protected function parse(string $rawBody):?array {
		$document = new DOMDocument();
		$document->preserveWhiteSpace = true;
		$previousUseInternalErrors = libxml_use_internal_errors(true);

		try {
			if($document->loadXML($rawBody, LIBXML_NONET)) {
				libxml_clear_errors();
				return iterator_to_array($document->childNodes);
			}

			libxml_clear_errors();

			$fragmentDocument = new DOMDocument();
			$fragmentDocument->preserveWhiteSpace = true;
			$wrapperName = "api-horse-root";
			$wrappedBody = "<$wrapperName>$rawBody</$wrapperName>";
			if(!$fragmentDocument->loadXML($wrappedBody, LIBXML_NONET)) {
				$errorMessageList = array_map(
					fn($error) => trim($error->message),
					libxml_get_errors(),
				);
				if($errorMessageList) {
					Log::info("Error decoding XML - " . implode("; ", $errorMessageList));
				}
				libxml_clear_errors();
				return null;
			}

			libxml_clear_errors();

			$wrapper = $fragmentDocument->documentElement;
			if(!$wrapper) {
				return null;
			}

			return iterator_to_array($wrapper->childNodes);
		}
		finally {
			libxml_use_internal_errors($previousUseInternalErrors);
		}
	}

	protected function getSyntaxHighlighterClassName():string {
		return "syntax-highlighter-xml";
	}
}
