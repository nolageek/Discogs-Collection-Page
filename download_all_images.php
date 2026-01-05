<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');

require_once("functions.php"); // Contains fetchCollectionData() and get_image_url()
require_once("settings.php"); // Contains fetchCollectionData() and get_image_url()

// === Config ===
@set_time_limit(0);
header('Content-Type: text/html; charset=utf-8');

// === Step 1: Fetch latest collection data ===
fetchCollectionData($DISCOGS_USERNAME, $DISCOGS_TOKEN, $DATA_FILES['releases']);

// === Step 2: Load the saved JSON ===
$collection = json_decode(file_get_contents($DATA_FILES['releases']), true);

echo "<h2>Downloading Cover Images</h2>";
flush();

if (!$collection || !isset($collection['releases'])) {
    echo "⚠️ No collection data found or invalid format.<br>";
    exit;
}

$total = count($collection['releases']);
$current = 0;
$downloaded = 0;
$skipped = 0;
$missing = 0;

foreach ($collection['releases'] as $item) {
    $current++;

    $release_id = $item['basic_information']['id'] ?? null;
    $title = htmlspecialchars($item['basic_information']['title'] ?? 'Unknown Title');
    $year = $item['basic_information']['year'] ?? 'Unknown Year';

    if (!$release_id || !is_numeric($release_id)) {
        echo "[$current / $total] ❌ Invalid release ID<br>";
        flush();
        continue;
    }

    // === Check if local image already exists ===
    $expected_extensions = ['jpg', 'jpeg', 'png', 'webp']; // known possibilities
    $local_image_path = null;

    foreach ($expected_extensions as $ext) {
        $path = $IMAGE_PATH_ROOT . $release_id . '.' . $ext;
        if (file_exists($path)) {
            $local_image_path = $path;
            break;
        }
    }

    if ($local_image_path) {
        // already exists — skip download
        $thumb_url = str_replace($_SERVER['DOCUMENT_ROOT'], '', $local_image_path);
        echo "[$current / $total] 🔁 Already had image for <strong>$title</strong> ($year)<br>";
        echo "<img src=\"$thumb_url\" style=\"height:100px;border-radius:4px;margin:4px 0;\"><br>";
        $skipped++;
    } else {
        // needs to be downloaded
        $image_result = get_image_url($release_id, 1); // will download if possible
        if ($image_result && file_exists($image_result)) {
            $thumb_url = str_replace($_SERVER['DOCUMENT_ROOT'], '', $image_result);
            echo "[$current / $total] ✅ Downloaded for <strong>$title</strong> ($year)<br>";
            echo "<img src=\"$thumb_url\" style=\"height:100px;border-radius:4px;margin:4px 0;\"><br>";
            $downloaded++;
        } else {
            echo "[$current / $total] ❌ No image for <strong>$title</strong> ($year)<br>";
            $missing++;
        }
    }

    flush();
    usleep(30000); // polite delay
}

echo "<hr>";
echo "<h3>Summary:</h3>";
echo "Total processed: <strong>$total</strong><br>";
echo "✅ Downloaded: <strong>$downloaded</strong><br>";
echo "🔁 Skipped (already existed): <strong>$skipped</strong><br>";
echo "❌ Missing: <strong>$missing</strong><br>";
