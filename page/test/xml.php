<?php
use App\SyntaxHighlighter\XmlSyntaxHighlighter;
use Gt\Dom\HTMLDocument;
use Gt\Logger\Log;

function go(HTMLDocument $document):void {
	$exampleMap = [
		"simple-xml" => <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<message>Hello, XML.</message>
XML,
		"nested-xml" => <<<'XML'
<catalog>
	<book id="bk001" genre="Fantasy">
		<title>The Night Gardener</title>
		<author first-name="Sam" last-name="Morris" />
		<price currency="GBP">12.99</price>
	</book>
	<book id="bk002" genre="Sci-Fi">
		<title>Vacuum Atlas</title>
		<author first-name="Rae" last-name="Bishop" />
		<price currency="USD">18.50</price>
	</book>
</catalog>
XML,
		"xml-mixed-nodes" => <<<'XML'
<?xml version="1.0"?>
<?xml-stylesheet href="/style.xsl" type="text/xsl"?>
<payload>
	<!-- This is a comment -->
	<data><![CDATA[<unsafe>literal markup</unsafe>]]></data>
</payload>
XML,
		"svg-xml" => <<<'XML'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 40">
	<rect x="2" y="2" width="116" height="36" rx="6" fill="#23395d" />
	<text x="60" y="25" text-anchor="middle" fill="#ffffff">API Horse</text>
</svg>
XML,
		"xhtml-document" => <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>Well-Formed XHTML</title>
	</head>
	<body>
		<div class="card">
			<p>This HTML is also valid XML.</p>
			<br />
		</div>
	</body>
</html>
XML,
		"xml-fragment" => <<<'XML'
<row id="1">First</row>
<row id="2">Second</row>
<row id="3"><label>Third</label><value>3</value></row>
XML,
		"invalid-xml" => <<<'XML'
<catalog>
	<book>
		<title>Broken Example</title>
</catalog>
XML,
		"invalid-html" => <<<'HTML'
<html>
	<body>
		<div>
			<p>Broken HTML
		</div>
	</body>
</html>
HTML,
	];

	foreach($document->querySelectorAll(".syntax-highlight") as $syntaxHighlightElement) {
		$key = $syntaxHighlightElement->dataset->get("example");
		if(!$key || !array_key_exists($key, $exampleMap)) {
			Log::warning("Unknown XML syntax highlighter example key: $key");
			continue;
		}

		$rawBody = $exampleMap[$key];
		$syntaxHighlightElement->textContent = $rawBody;

		$highlighter = new XmlSyntaxHighlighter();
		$highlighter->format($syntaxHighlightElement, $rawBody);
	}
}
