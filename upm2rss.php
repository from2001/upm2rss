<?php
// UPM to RSS Converter
// https://github.com/from2001/upm2rss

// Check if the 'id' GET parameter is set
if (!isset($_GET['id'])) {
    die('No ID provided');
}

// Construct the URL to fetch the JSON data
$id = $_GET['id'];
$url = "https://packages.unity.com/$id";

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

// Construct the RSS feed
header('Content-Type: application/xml; charset=UTF-8');
echo "<?xml version='1.0' encoding='UTF-8'?>\n";
echo "<rss version='2.0'>\n";
echo "<channel>\n";

// Channel section
echo "<title>" . htmlspecialchars($json['name']) . "</title>\n";
echo "<link>https://packages.unity.com/" . htmlspecialchars($json['_id']) . "</link>\n";
echo "<description>" . htmlspecialchars($json['description']) . "</description>\n";

// Items section
if (isset($json['versions']) && is_array($json['versions'])) {
    foreach ($json['versions'] as $version) {
        echo "<item>\n";
        echo "<title>" . htmlspecialchars($version['name']) . "@" . htmlspecialchars($version['version']) . "</title>\n";
        echo "<link>" . htmlspecialchars($version['documentationUrl']) . "</link>\n";
        echo "<description>" . htmlspecialchars($version['_upm']['changelog']) . "</description>\n";
        // Assuming 'time' is an array with version timestamps
        echo "<pubDate>" . htmlspecialchars($json['time'][$version['version']]) . "</pubDate>\n";
        echo "</item>\n";
    }
}

echo "</channel>\n";
echo "</rss>\n";
?>
