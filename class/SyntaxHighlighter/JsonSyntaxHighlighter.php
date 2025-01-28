<?php
namespace App\SyntaxHighlighter;

use Gt\Dom\DocumentFragment;
use Gt\Dom\Element;
use Gt\Logger\Log;

class JsonSyntaxHighlighter extends SyntaxHighlighter{
	public function format(Element $element):void {
		$id = $element->dataset->get("id");
		$cacheFile = "data/html-cache/response-formatted/$id.html";
		if(file_exists($cacheFile)) {
			$html = file_get_contents($cacheFile);
			$element->innerHTML = $html;
			return;
		}

		$document = $element->ownerDocument;

		$responseBodyElement = $element->querySelector(".response-body");
		$rawBodyString = $responseBodyElement->innerHTML;
		$json = json_decode($rawBodyString, true);
		if($jsonError = json_last_error()) {
			Log::info("Error decoding JSON (error $jsonError) - " . json_last_error_msg());
			return;
		}

		$fragment = $document->createDocumentFragment();
		$this->output($json, $fragment);
		$responseBodyElement->innerHTML = "";
		if($fragment->childNodes->length > 0) {
			$appended = $responseBodyElement->appendChild($fragment);
			$appended->classList->add("syntax-highlighter", "syntax-highlighter-json");
		}

		$html = $element->innerHTML;
		if(!is_dir(dirname($cacheFile))) {
			mkdir(dirname($cacheFile), recursive: true);
		}
//		file_put_contents($cacheFile, $html);
	}

	private function output(
		null|int|bool|float|string|array $json,
		Element|DocumentFragment $outputTo,
		int $nestingLevel = 0,
	):void {
		if(is_scalar($json) || is_null($json)) {
			$this->outputBasicType($json, $outputTo, $nestingLevel);
		}
		else {
			$this->outputDataStructure($json, $outputTo, $nestingLevel);
		}
	}

	private function outputBasicType(
		null|int|bool|float|string $value,
		DocumentFragment|Element $appendTo,
		int $nestedLevel = 0,
	):void {
		$valueTypeString = gettype($value);

		$document = $appendTo->ownerDocument;
		$valueElement = $document->createElement("span");
		if(is_null($value)) {
			$value = "null";
		}
		elseif(is_Bool($value)) {
			$value = $value ? "true" : "false";
		}
		elseif(is_string($value)) {
			$value = '"' . $value . '"';
		}

		$nestedValue = str_repeat("\t", $nestedLevel) . $value;
		$valueElement->textContent = $nestedValue;
		$valueElement->classList->add("type", "type-" . strtolower($valueTypeString));
		$appendTo->appendChild($valueElement);
	}

	private function outputDataStructure(
		array $data,
		DocumentFragment|Element $appendTo,
		int $nestingLevel = 0,
	):void {
		$document = $appendTo->ownerDocument;

		$detailsEl = $document->createElement("details");
		$detailsEl->open = true;
		$summaryEl = $document->createElement("summary");
		$detailsEl->appendChild($summaryEl);
		$expandedEl = $document->createElement("div");
		$expandedEl->classList->add("data-structure", "nested");
		if(array_is_list($data)) {
			$expandedEl->classList->add("data-structure-list");
		}
		else {
			$expandedEl->classList->add("data-structure-object");
		}

		$detailsEl->appendChild($expandedEl);
		$appendTo->appendChild($detailsEl);

		$openingBracketCharacter = array_is_list($data) ? "[" : "{";
		$openingBracketEl = $document->createElement("span");
		$openingBracketEl->textContent = str_repeat("\t", $nestingLevel) . $openingBracketCharacter;
		$openingBracketEl->classList->add("syntax", "syntax-array-bracket");
		$expandedOpeningBracketEl = $openingBracketEl->cloneNode(true);
		$expandedEl->appendChild($expandedOpeningBracketEl);
		$summaryEl->appendChild($openingBracketEl);

		$separatorEl = null;
		$expandedSeparatorEl = null;

		foreach($data as $key => $value) {
			$expandedRow = $document->createElement("div");
			$expandedEl->appendChild($expandedRow);
			$expandedRow->classList->add("data-element");

			$keyElement = $document->createElement("span");
			$keyElement->textContent = '"' . $key . '": ';
			$keyElement->classList->add("syntax", "syntax-summary-key");

			$this->output($value, $summaryEl, $nestingLevel + 1);
			$this->output($value, $expandedRow, $nestingLevel + 1);

			$separatorEl = $document->createElement("span");
			$separatorEl->textContent = ", ";
			$separatorEl->classList->add("syntax", "syntax-array-separator");
			$summaryEl->appendChild($separatorEl);
			$expandedSeparatorEl = $separatorEl->cloneNode(true);
			$expandedRow->appendChild($expandedSeparatorEl);

			if(array_is_list($data)) {
				$expandedRow->classList->add("data-element-list");
			}
			elseif(is_array($data)) {
				$summaryEl->appendChild($keyElement);
				$expandedRow->classList->add("data-element-object");
				$expandedKeyElement = $keyElement->cloneNode(true);
				$expandedKeyElement->classList->add("syntax", "syntax-key");
				$expandedRow->prepend($expandedKeyElement);
			}

			if(array_is_list($data)) {
				$expandedRow->dataset->set("index", $key);
			}
		}
		$separatorEl?->remove();
		$expandedSeparatorEl?->remove();

		$summaryEl->querySelectorAll("details")->forEach(function(Element $details) {
			$details->open = false;
			// TODO: onclick should click the parent details.
			$details->setAttribute("onclick", "return false");
		});

		$closingBracketCharacter = array_is_list($data) ? "]" : "}";
		$closingBracketEl = $document->createElement("span");
		$closingBracketEl->textContent = str_repeat("\t", $nestingLevel) . $closingBracketCharacter;
		$closingBracketEl->classList->add("syntax", "syntax-array-bracket");
		$summaryEl->appendChild($closingBracketEl);
		$expandedClosingBracketEl = $closingBracketEl->cloneNode(true);
		$expandedEl->appendChild($expandedClosingBracketEl);
	}
}
