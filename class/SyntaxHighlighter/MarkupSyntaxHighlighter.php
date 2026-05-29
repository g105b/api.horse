<?php
namespace App\SyntaxHighlighter;

use DOMCdataSection;
use DOMComment;
use DOMDocumentType;
use DOMElement;
use DOMNode;
use DOMProcessingInstruction;
use DOMText;
use Gt\Dom\DocumentFragment;
use Gt\Dom\Element as HtmlElement;

abstract class MarkupSyntaxHighlighter extends SyntaxHighlighter {
	public function format(HtmlElement $element, string $rawBody):void {
		$id = $element->dataset->get("id");
		$cacheFile = $id ? "data/html-cache/response-formatted/$id.html" : null;
		if($cacheFile && file_exists($cacheFile)) {
			$html = file_get_contents($cacheFile);
			$element->innerHTML = $html;
			return;
		}

		if($element->classList->contains("syntax-highlight")) {
			$syntaxHighlightElement = $element;
		}
		else {
			$syntaxHighlightElement = $element->querySelector(".syntax-highlight");
		}

		$parsedNodeList = $this->parse($rawBody);
		if(is_null($parsedNodeList)) {
			return;
		}

		$document = $element->ownerDocument;
		$fragment = $document->createDocumentFragment();
		$wrapper = $document->createElement("div");
		$wrapper->classList->add("syntax-highlighter", $this->getSyntaxHighlighterClassName());
		$fragment->appendChild($wrapper);
		$this->renderNodeList($parsedNodeList, $wrapper);

		if($wrapper->childNodes->length === 0) {
			return;
		}

		$syntaxHighlightElement->innerHTML = "";
		$syntaxHighlightElement->appendChild($fragment);

		$html = $element->innerHTML;
		if($cacheFile) {
			if(!is_dir(dirname($cacheFile))) {
				mkdir(dirname($cacheFile), recursive: true);
			}
			file_put_contents($cacheFile, $html);
		}
	}

	/** @return null|array<DOMNode> */
	abstract protected function parse(string $rawBody):?array;

	abstract protected function getSyntaxHighlighterClassName():string;

	/** @param array<DOMNode> $nodeList */
	private function renderNodeList(
		array $nodeList,
		HtmlElement|DocumentFragment $appendTo,
		int $nestingLevel = 0,
	):void {
		foreach($nodeList as $node) {
			$this->renderNode($node, $appendTo, $nestingLevel);
		}
	}

	private function renderNode(
		DOMNode $node,
		HtmlElement|DocumentFragment $appendTo,
		int $nestingLevel,
	):void {
		switch($node->nodeType) {
		case XML_ELEMENT_NODE:
			$this->renderElementNode($node, $appendTo, $nestingLevel);
			break;

		case XML_TEXT_NODE:
			$this->renderTextNode($node, $appendTo, $nestingLevel);
			break;

		case XML_COMMENT_NODE:
			$this->renderCommentNode($node, $appendTo, $nestingLevel);
			break;

		case XML_CDATA_SECTION_NODE:
			$this->renderCdataNode($node, $appendTo, $nestingLevel);
			break;

		case XML_PI_NODE:
			$this->renderProcessingInstructionNode($node, $appendTo, $nestingLevel);
			break;

		case XML_DOCUMENT_TYPE_NODE:
			$this->renderDocumentTypeNode($node, $appendTo, $nestingLevel);
			break;
		}
	}

	private function renderElementNode(
		DOMElement $node,
		HtmlElement|DocumentFragment $appendTo,
		int $nestingLevel,
	):void {
		$childNodeList = $this->getRenderableChildNodeList($node);
		if(empty($childNodeList)) {
			$line = $this->createLine($appendTo);
			$this->appendOpeningTag($line, $node, $nestingLevel, true);
			return;
		}

		if($this->canRenderInline($childNodeList)) {
			$line = $this->createLine($appendTo);
			$this->appendOpeningTag($line, $node, $nestingLevel);
			$this->appendSyntaxText($line, $childNodeList[0]->nodeValue, "syntax", "syntax-text");
			$this->appendClosingTag($line, $node);
			return;
		}

		$document = $appendTo->ownerDocument;
		$detailsElement = $document->createElement("details");
		$detailsElement->open = true;
		$summaryElement = $document->createElement("summary");
		$detailsElement->appendChild($summaryElement);
		$appendTo->appendChild($detailsElement);

		$this->appendOpeningTag($summaryElement, $node, $nestingLevel);
		$this->appendSyntaxText($summaryElement, "...", "syntax", "syntax-ellipsis");
		$this->appendClosingTag($summaryElement, $node);

		$expandedElement = $document->createElement("div");
		$expandedElement->classList->add("data-structure", "nested", "nested-$nestingLevel", "data-structure-markup");
		$detailsElement->appendChild($expandedElement);

		$openingLine = $this->createLine($expandedElement);
		$this->appendOpeningTag($openingLine, $node, $nestingLevel);
		$this->renderNodeList($childNodeList, $expandedElement, $nestingLevel + 1);
		$closingLine = $this->createLine($expandedElement);
		$this->appendClosingTag($closingLine, $node, $nestingLevel);
	}

	private function renderTextNode(
		DOMText $node,
		HtmlElement|DocumentFragment $appendTo,
		int $nestingLevel,
	):void {
		$value = $node->nodeValue ?? "";
		if(trim($value) === "") {
			return;
		}

		$line = $this->createLine($appendTo);
		$this->appendIndent($line, $nestingLevel);
		$this->appendSyntaxText($line, $value, "syntax", "syntax-text");
	}

	private function renderCommentNode(
		DOMComment $node,
		HtmlElement|DocumentFragment $appendTo,
		int $nestingLevel,
	):void {
		$line = $this->createLine($appendTo);
		$this->appendIndent($line, $nestingLevel);
		$this->appendSyntaxText($line, "<!--{$node->nodeValue}-->", "syntax", "syntax-comment");
	}

	private function renderCdataNode(
		DOMCdataSection $node,
		HtmlElement|DocumentFragment $appendTo,
		int $nestingLevel,
	):void {
		$line = $this->createLine($appendTo);
		$this->appendIndent($line, $nestingLevel);
		$this->appendSyntaxText($line, "<![CDATA[{$node->nodeValue}]]>", "syntax", "syntax-cdata");
	}

	private function renderProcessingInstructionNode(
		DOMProcessingInstruction $node,
		HtmlElement|DocumentFragment $appendTo,
		int $nestingLevel,
	):void {
		$line = $this->createLine($appendTo);
		$this->appendIndent($line, $nestingLevel);
		$data = trim($node->data ?? "");
		$this->appendSyntaxText(
			$line,
			$data ? "<?{$node->target} $data?>" : "<?{$node->target}?>",
			"syntax",
			"syntax-processing-instruction",
		);
	}

	private function renderDocumentTypeNode(
		DOMDocumentType $node,
		HtmlElement|DocumentFragment $appendTo,
		int $nestingLevel,
	):void {
		$line = $this->createLine($appendTo);
		$this->appendIndent($line, $nestingLevel);

		$doctype = "<!DOCTYPE $node->name";
		if($node->publicId) {
			$doctype .= ' PUBLIC "' . $node->publicId . '"';
		}
		if($node->systemId) {
			$doctype .= ' "' . $node->systemId . '"';
		}
		$doctype .= ">";

		$this->appendSyntaxText($line, $doctype, "syntax", "syntax-doctype");
	}

	/** @return array<DOMNode> */
	private function getRenderableChildNodeList(DOMElement $node):array {
		$childNodeList = [];
		foreach(iterator_to_array($node->childNodes) as $childNode) {
			if($childNode->nodeType === XML_TEXT_NODE && trim($childNode->nodeValue ?? "") === "") {
				continue;
			}

			$childNodeList[] = $childNode;
		}

		return $childNodeList;
	}

	/** @param array<DOMNode> $childNodeList */
	private function canRenderInline(array $childNodeList):bool {
		return count($childNodeList) === 1
			&& $childNodeList[0] instanceof DOMText;
	}

	private function createLine(HtmlElement|DocumentFragment $appendTo):HtmlElement {
		$line = $appendTo->ownerDocument->createElement("div");
		$line->classList->add("data-element", "data-element-markup");
		$appendTo->appendChild($line);
		return $line;
	}

	private function appendOpeningTag(
		HtmlElement $appendTo,
		DOMElement $node,
		int $nestingLevel,
		bool $selfClosing = false,
	):void {
		$this->appendIndent($appendTo, $nestingLevel);
		$this->appendSyntaxText($appendTo, "<", "syntax", "syntax-tag-angle");
		$this->appendSyntaxText($appendTo, $node->tagName, "syntax", "syntax-tag-name");

		foreach(iterator_to_array($node->attributes ?? []) as $attribute) {
			$this->appendSyntaxText($appendTo, " ", "syntax");
			$this->appendSyntaxText($appendTo, $attribute->name, "syntax", "syntax-attribute-name");
			$this->appendSyntaxText($appendTo, "=", "syntax", "syntax-attribute-equals");
			$this->appendSyntaxText($appendTo, '"', "syntax", "syntax-attribute-quote");
			$this->appendSyntaxText($appendTo, $attribute->value, "syntax", "syntax-attribute-value");
			$this->appendSyntaxText($appendTo, '"', "syntax", "syntax-attribute-quote");
		}

		$this->appendSyntaxText(
			$appendTo,
			$selfClosing ? " />" : ">",
			"syntax",
			"syntax-tag-angle",
		);
	}

	private function appendClosingTag(
		HtmlElement $appendTo,
		DOMElement $node,
		int $nestingLevel = 0,
	):void {
		$this->appendIndent($appendTo, $nestingLevel);
		$this->appendSyntaxText($appendTo, "</", "syntax", "syntax-tag-angle");
		$this->appendSyntaxText($appendTo, $node->tagName, "syntax", "syntax-tag-name");
		$this->appendSyntaxText($appendTo, ">", "syntax", "syntax-tag-angle");
	}

	private function appendIndent(HtmlElement $appendTo, int $nestingLevel):void {
		if($nestingLevel === 0) {
			return;
		}

		$this->appendSyntaxText(
			$appendTo,
			str_repeat("\t", $nestingLevel),
			"syntax",
			"syntax-indent",
		);
	}

	private function appendSyntaxText(
		HtmlElement $appendTo,
		string $text,
		string ...$classNameList,
	):void {
		$span = $appendTo->ownerDocument->createElement("span");
		$span->textContent = $text;
		$span->classList->add(...$classNameList);
		$appendTo->appendChild($span);
	}
}
