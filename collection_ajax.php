<?php
require_once 'functions.php';
// Include necessary functions

$PARAMS = $_GET; // Capture updated URL parameters

// Fetch filtered collection based on parameters
$filteredCollection = filter_collection_by_media($DATA_FILES['releases'], isset($PARAMS['media_filter']) ? explode(',', $PARAMS['media_filter']) : []);

// Generate HTML for collection display
foreach ($filteredCollection as $release) {
    echo "<div class='record-item'>";
    echo "<img src='" . htmlspecialchars($release['basic_information']['cover_image']) . "' alt='" . htmlspecialchars($release['basic_information']['title']) . "'>";
    echo "<p>" . htmlspecialchars($release['basic_information']['title']) . " - " . htmlspecialchars($release['basic_information']['artists'][0]['name']) . "</p>";
    echo "</div>";
}
?>
