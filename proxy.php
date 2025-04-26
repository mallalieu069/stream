<?php
$url = $_GET['url'] ?? '';

if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    die("Invalid URL");
}

// Determine file type
$ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
$contentTypes = [
    'm3u8' => 'application/vnd.apple.mpegurl',
    'ts' => 'video/MP2T',
    'key' => 'application/octet-stream'
];

$contentType = $contentTypes[$ext] ?? 'application/octet-stream';
header("Content-Type: $contentType");

// Fetch the original content
$opts = [
    "http" => [
        "header" => "User-Agent: Mozilla/5.0\r\n"
    ]
];
$context = stream_context_create($opts);
$content = @file_get_contents($url, false, $context);

if ($content === false) {
    http_response_code(502);
    die("Failed to fetch stream content.");
}

// Rewrite .m3u8 URLs to go through proxy
if ($ext === 'm3u8') {
    $base = dirname($url);
    $content = preg_replace_callback('/^(?!#)(.+)$/m', function ($matches) use ($base) {
        $line = trim($matches[1]);
        if (parse_url($line, PHP_URL_SCHEME)) {
            return "proxy.php?url=" . urlencode($line);
        } else {
            return "proxy.php?url=" . urlencode($base . '/' . $line);
        }
    }, $content);
}

echo $content;
