<?php
// UPM to Atom Converter
// https://github.com/from2001/upm2rss

// Check if the 'name' GET parameter is set
if (!isset($_GET['name'])) {
    die('No ID provided');
}

// Construct the URL to fetch the JSON data
$name = $_GET['name'];
$url = "https://packages.unity.com/$name";

// Use context to get HTTP response header
$context = stream_context_create(array('http' => array('ignore_errors' => true)));
$jsonData = file_get_contents($url, false, $context);

// Check for 404 or other error codes in the response header
if (isset($http_response_header) && strpos($http_response_header[0], "404")) {
    die('Error: Data not found (404)');
}

// Continue if we have valid data
if (!$jsonData) {
    die('Could not retrieve data');
}

// Decode the JSON data
$json = json_decode($jsonData, true);
if (!$json) {
    die('Invalid JSON data');
}

// XML escape helper for Atom output
function atom_escape($text) {
    return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

// Inline markdown -> HTML (code spans, links, bold). Input is escaped first.
function inline_md_to_html($text) {
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    // Code spans
    $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text);
    // Links [label](url)
    $text = preg_replace_callback('/\[([^\]]+)\]\(([^)]+)\)/', function ($m) {
        return '<a href="' . $m[2] . '">' . $m[1] . '</a>';
    }, $text);
    // Bold
    $text = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $text);
    return $text;
}

// Block-level markdown -> HTML supporting headings and bullet lists
function md_to_html($md) {
    $lines = preg_split('/\r\n|\r|\n/', (string)$md);
    $out = '';
    $inList = false;
    $closeList = function () use (&$out, &$inList) {
        if ($inList) {
            $out .= "</ul>\n";
            $inList = false;
        }
    };
    foreach ($lines as $line) {
        if (preg_match('/^(#{1,6})\s+(.+)$/', $line, $m)) {
            $closeList();
            $level = strlen($m[1]);
            $out .= "<h{$level}>" . inline_md_to_html($m[2]) . "</h{$level}>\n";
        } elseif (preg_match('/^\s*[-*]\s+(.+)$/', $line, $m)) {
            if (!$inList) {
                $out .= "<ul>\n";
                $inList = true;
            }
            $out .= '<li>' . inline_md_to_html($m[1]) . "</li>\n";
        } elseif (trim($line) === '') {
            $closeList();
        } else {
            $closeList();
            $out .= '<p>' . inline_md_to_html($line) . "</p>\n";
        }
    }
    $closeList();
    return $out;
}

// Determine the feed-level <updated> from the most recent version timestamp
$feedUpdated = '';
if (isset($json['time']) && is_array($json['time'])) {
    foreach ($json['time'] as $key => $ts) {
        if ($key === 'created' || $key === 'modified') {
            continue;
        }
        if ($ts > $feedUpdated) {
            $feedUpdated = $ts;
        }
    }
    if ($feedUpdated === '' && isset($json['time']['modified'])) {
        $feedUpdated = $json['time']['modified'];
    }
}

// Self URL for the Atom feed (best effort reconstruction)
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
$selfPath = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
$selfUrl = $host !== '' ? $scheme . '://' . $host . $selfPath : '';

$packageId = isset($json['_id']) ? $json['_id'] : $name;
$packageUrl = 'https://packages.unity.com/' . $packageId;

// Output the Atom feed
header('Content-Type: application/atom+xml; charset=UTF-8');
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
echo "<feed xmlns=\"http://www.w3.org/2005/Atom\">\n";

echo '  <id>urn:upm:' . atom_escape($packageId) . "</id>\n";
echo '  <title>Release notes from ' . atom_escape($json['name']) . "</title>\n";
echo '  <link rel="alternate" type="text/html" href="' . atom_escape($packageUrl) . "\"/>\n";
if ($selfUrl !== '') {
    echo '  <link rel="self" type="application/atom+xml" href="' . atom_escape($selfUrl) . "\"/>\n";
}
if ($feedUpdated !== '') {
    echo '  <updated>' . atom_escape($feedUpdated) . "</updated>\n";
}
if (!empty($json['description'])) {
    echo '  <subtitle>' . atom_escape($json['description']) . "</subtitle>\n";
}

if (isset($json['versions']) && is_array($json['versions'])) {
    foreach ($json['versions'] as $version) {
        $versionId = isset($version['version']) ? $version['version'] : '';
        $entryTitle = $version['name'] . '@' . $versionId;
        $entryLink = isset($version['documentationUrl']) ? $version['documentationUrl'] : $packageUrl;
        $entryUpdated = isset($json['time'][$versionId]) ? $json['time'][$versionId] : '';
        $changelogMd = isset($version['_upm']['changelog']) ? $version['_upm']['changelog'] : '';
        $changelogHtml = md_to_html($changelogMd);

        echo "  <entry>\n";
        echo '    <id>urn:upm:' . atom_escape($packageId) . ':' . atom_escape($versionId) . "</id>\n";
        echo '    <title>' . atom_escape($entryTitle) . "</title>\n";
        echo '    <link rel="alternate" type="text/html" href="' . atom_escape($entryLink) . "\"/>\n";
        if ($entryUpdated !== '') {
            echo '    <updated>' . atom_escape($entryUpdated) . "</updated>\n";
        }
        echo '    <content type="html">' . atom_escape($changelogHtml) . "</content>\n";
        if (!empty($version['author']['name'])) {
            echo "    <author>\n";
            echo '      <name>' . atom_escape($version['author']['name']) . "</name>\n";
            echo "    </author>\n";
        }
        echo "  </entry>\n";
    }
}

echo "</feed>\n";
?>
