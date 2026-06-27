<?php
header("Content-Type: application/json; charset=utf-8");

// Die offizielle JSON-Schnittstelle deines Icecast-Servers
$stream_url = "https://radioserver01.ipgenservices.de:8100/status-json.xsl"; 

$context = stream_context_create([
    "http" => [
        "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\n",
        "timeout" => 5
    ]
]);

$data = @file_get_contents($stream_url, false, $context);

if ($data === false) {
    echo json_encode(["title" => "Joy FM – Live"]);
} else {
    echo $data;
}
?>
