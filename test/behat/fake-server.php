<?php
$responseFile = getenv("BEHAT_FAKE_RESPONSE_FILE")
	?: __DIR__ . "/response/example.com.http";

if(!is_file($responseFile)) {
	http_response_code(500);
	header("Content-Type: text/plain");
	echo "Fake response file not found: $responseFile";
	return;
}

$responseText = file_get_contents($responseFile);
$parts = preg_split("/\r?\n\r?\n/", $responseText, 2);
$headerText = $parts[0] ?? "";
$body = $parts[1] ?? "";
$headerLines = preg_split("/\r?\n/", $headerText);
$statusLine = array_shift($headerLines) ?: "HTTP/1.1 200 OK";

if(preg_match("/^HTTP\/\d(?:\.\d)?\s+(\d{3})(?:\s+(.*))?$/", $statusLine, $matches)) {
	header($statusLine, true, (int)$matches[1]);
}
else {
	http_response_code(200);
	header($statusLine);
}

foreach($headerLines as $headerLine) {
	if($headerLine === "") {
		continue;
	}

	header($headerLine, false);
}

echo $body;
