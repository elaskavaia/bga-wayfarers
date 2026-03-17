#!/usr/bin/env php
<?php
/**
 * Convert BGA game log HTML to plain text.
 * Usage: php misc/gamelog2text.php /tmp/game.log.html
 */

if ($argc < 2) {
    fprintf(STDERR, "Usage: php %s <game.log.html>\n", $argv[0]);
    exit(1);
}

$file = $argv[1];
$html = file_get_contents($file);
if ($html === false) {
    fprintf(STDERR, "Cannot read file: %s\n", $file);
    exit(1);
}

// Remove reflection time blocks (multiline)
$html = preg_replace('/<div class="reflexiontimes_block"[^>]*>.*?<\/div>\s*<\/div>/s', '', $html);

// Only keep content inside <div id="gamelogs"...>
if (preg_match('/<div id="gamelogs"[^>]*>(.*)<\/div>\s*$/s', $html, $m)) {
    $html = $m[1];
}

// Process move headers: "Move N : timestamp"
$html = preg_replace_callback(
    '/<div class="smalltext">\s*(Move \d+)\s*:<span[^>]*>\s*(.*?)\s*<\/span>\s*<\/div>/s',
    function ($m) {
        return "\n--- " . $m[1] . " : " . trim($m[2]) . " ---\n";
    },
    $html
);

// Process game log entries
$html = preg_replace_callback(
    '/<div class="gamelogreview[^"]*">(.*?)<\/div>/s',
    function ($m) {
        return "  " . strip_tags($m[1]) . "\n";
    },
    $html
);

// Strip any remaining HTML tags
$text = strip_tags($html);

// Clean up whitespace
$text = preg_replace('/[ \t]+\n/', "\n", $text);
$text = preg_replace('/\n{2,}(?=  )/', "\n", $text);
$text = preg_replace('/\n{3,}/', "\n\n", $text);
$text = trim($text) . "\n";

echo $text;
