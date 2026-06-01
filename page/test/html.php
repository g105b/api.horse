<?php
use App\SyntaxHighlighter\HtmlSyntaxHighlighter;
use Gt\Dom\HTMLDocument;
use Gt\Logger\Log;

function go(HTMLDocument $document):void {
	$exampleMap = [
		"simple-html" => <<<'HTML'
<div class="notice"><strong>Heads up:</strong> This is a short HTML fragment.</div>
HTML,
		"nested-html" => <<<'HTML'
<!doctype html>
<html>
	<head>
		<title>Example HTML</title>
	</head>
	<body>
		<main>
			<section class="hero">
				<h1>API Horse</h1>
				<p>Markup rendered with no client-side JavaScript.</p>
			</section>
		</main>
	</body>
</html>
HTML,
		"html-comment" => <<<'HTML'
<article>
	<!-- Editorial note -->
	<h2>Changelog</h2>
	<p>Added syntax highlighting for HTML responses.</p>
</article>
HTML,
		"invalid-html" => <<<'HTML'
<html>
	<body>
		<section>
			<h1>Broken Example
		</section>
	</body>
</html>
HTML,
	];

	foreach($document->querySelectorAll(".syntax-highlight") as $syntaxHighlightElement) {
		$key = $syntaxHighlightElement->dataset->get("example");
		if(!$key || !array_key_exists($key, $exampleMap)) {
			Log::warning("Unknown HTML syntax highlighter example key: $key");
			continue;
		}

		$rawBody = $exampleMap[$key];
		$syntaxHighlightElement->textContent = $rawBody;

		$highlighter = new HtmlSyntaxHighlighter();
		$highlighter->format($syntaxHighlightElement, $rawBody);
	}
}
