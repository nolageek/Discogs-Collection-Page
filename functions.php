<?php

//error_reporting(E_ALL);
//ini_set('display_errors', 'On');

class Debug {
    private static bool $enabled = false;
    
    public static function init(bool $enabled): void {
        self::$enabled = $enabled;
    }
    
    public static function log(string $message): void {
        if (self::$enabled) {
            echo htmlspecialchars($message) . "<br>";
        }
    }
    
    public static function isEnabled(): bool {
        return self::$enabled;
    }
}

Debug::init(!empty($_GET['debug']));


require_once('settings.php');

/* ******************************************************** */
/* The following should not be changed on a per-site basis  */
/* ******************************************************** */

// DEFAULT VALUES FOR PARAMETERS

$PARAMS = array(

    "username" => $DISCOGS_USERNAME, // Currently not used
    "type" => $_GET['type'] ?? "releases", // Currently not used
    "folder_id" => $_GET['folder_id'] ?? '0',
    "release_id" => $_GET['release_id'] ?? "",
    "artist_id" => $_GET['artist_id'] ?? "", // Currently not used
    "sort_by" => $_GET['sort_by'] ?? "added",
    "order" => $_GET['order'] ?? "desc",
    "per_page" => $_GET['per_page'] ?? "24",
    "page" => $_GET['page'] ?? "1",
    "debug" => $_GET['debug'] ?? "",
    #"view" 			=> $_GET['view'] 		?? "grid"

);


/* ******************************************************** */
/*                  DISCOGS API VARIABLES                   */
/* ******************************************************** */

// Discogs API URL
$DISCOGS_API_URL = "https://api.discogs.com/";
// Discogs API URL for user data
$DISCOGS_API_URL_USERS = $DISCOGS_API_URL . "users/";
// Reset the Debug message
$DEBUG_MSG = '';
// Do the math from hours to seconds
$MAX_AGE_IN_HOURS = 8;
$MAX_AGE_IN_SECONDS = $MAX_AGE_IN_HOURS * 60 * 60;

// What kind of page are we looking at right now?
$IS_STATISTICS = ($PARAMS['type'] == 'statistics') ? 1 : 0;
$IS_WANTLIST = ($PARAMS['type'] == 'wants') ? 1 : 0;
$IS_RELEASES = ($PARAMS['type'] == 'releases') ? 1 : 0;
$IS_SINGLE = ($PARAMS['release_id']) ? 1 : 0;
$IS_RELEASE_GALLERY = (!$IS_SINGLE) ? 1 : 0;

$collection_value = "";
 
$LOCAL_RELEASES_DATA_ROOT = $LOCAL_DATA_PATH_ROOT . "releases/";

// Shared Releases data (to avoid duplicates)
$LOCAL_RELEASE_DATA_FILE = $LOCAL_RELEASES_DATA_ROOT . $PARAMS['release_id'] . '.json';
$LOCAL_RELEASE_MASTER_DATA_FILE = $LOCAL_RELEASES_DATA_ROOT . $PARAMS['release_id'] . '_master.json';
//$LOCAL_USER_RELEASE_META_FILE = $LOCAL_RELEASES_DATA_ROOT . $PARAMS['release_id'] . '_meta.json';
$LOCAL_RELEASE_MUSICBRAINZ_FILE = $LOCAL_RELEASES_DATA_ROOT . $PARAMS['release_id'] . '_musicbrainz.json';
$LOCAL_RELEASE_COVERARTARCHIVE_FILE = $LOCAL_RELEASES_DATA_ROOT . $PARAMS['release_id'] . '_coverartarchive.json';

// user-specific data files
$LOCAL_USER_DATA_ROOT = $LOCAL_DATA_PATH_ROOT . $PARAMS['username'] . "/";
$LOCAL_USER_RELEASES_DATA_ROOT = $LOCAL_USER_DATA_ROOT . "releases/";
$LOCAL_USER_MYRELEASE_DATA_FILE = $LOCAL_USER_RELEASES_DATA_ROOT . $PARAMS['release_id'] . '_mine.json';

$DATA_FILES = array(
    // USER FILES, ALWAYS CACHED
    "releases"      => $LOCAL_USER_DATA_ROOT . "collection_data.json",
    "value"         => $LOCAL_USER_DATA_ROOT . "collection_value.json",
    "fields"        => $LOCAL_USER_DATA_ROOT . "collection_fields.json",
    "folders"       => $LOCAL_USER_DATA_ROOT . "folder_data.json",
    "wants"         => $LOCAL_USER_DATA_ROOT . "wantlist_data.json",
    "statistics"    => $LOCAL_USER_DATA_ROOT . "statistics.json",
    "profile"       => $LOCAL_USER_DATA_ROOT . "user_profile.json",
    "lists"         => $LOCAL_USER_DATA_ROOT . "user_lists.json",
    // RELEASE FILES, CACHED IF CONFIGURED AS SUCH
    "release"       => $LOCAL_RELEASE_DATA_FILE,
    "musicbrainz"   => $LOCAL_RELEASE_MUSICBRAINZ_FILE,
    "coverart"      => $LOCAL_RELEASE_COVERARTARCHIVE_FILE,
);



function display_socials($SOCIALS) {
    foreach ($SOCIALS as $name => $url) {
        if ($url) {
        switch(strtolower($name)) {
            case "bluesky":
                $faIcon = "fa-brands fa-$name";
                break;
            case "linktree":
                $faIcon = "fa-link";
                break;
            case "discogs":
                $faIcon = "fa-record-vinyl";
                break;
            default:
                $faIcon = "fa-$name";
            }

              echo " <a href=\"" . htmlspecialchars($url) . "\" target=\"_blank\" rel=\"noopener noreferrer\"><i class=\"fa fa-fw $faIcon mx-2\"></i></a>";
    }
}

}



if (file_exists($DATA_FILES['releases'])) {
    $age_of_collection_file = (time() - filemtime($DATA_FILES['releases']));
}


$options = array('http' => array('user_agent' => 'DiscogsCollectionPage'));
$CONTEXT = stream_context_create($options);
$options2 = array('http' => array('user_agent' => "User-Agent: MyMusicApp/1.0 (your@email.com)\r\n"));
$CONTEXT2 = stream_context_create($options2);
$user_bio = "This collection belongs to " . $PARAMS['username'] . ". There are many just like it, but this one is his.";

// GET FOLDER DATA FOR NAVIGATION BAR
$folderdata = file_get_contents($DATA_FILES['folders']);
$folders = json_decode($folderdata, true); // decode the JSON feed

// Get name, ID and number of items of current folder.
foreach ($folders['folders'] as $folder) {
    if ($folder['id'] == $PARAMS['folder_id']) {
        $current_folder_name = $folder['name'];
        $current_folder_count = $folder['count'];
    }
    if ($folder['id'] == 0) {
        $total_collection_count = $folder['count'];
    }
}

$UserProfileData = file_get_contents($DATA_FILES['profile']);
$userProfile = json_decode($UserProfileData, true); // decode the JSON feed

function add_debug($DEBUG_STR = "")
{
    global $PARAMS;
    if ($PARAMS['debug']):
        echo $DEBUG_STR . "</br>";
    endif;
}

Debug::log("No master id");

function build_url($baseUrl = "/", $PARAMS = [])
{
    // Filter out any parameters with empty values
    $filteredParams = array_filter($PARAMS, function ($value) {
        return !is_null($value) && $value !== '' && $value !== '0';
    });

    // Build the query string with the filtered parameters
    $queryString = http_build_query($filteredParams);

    // Return the full URL
    return $baseUrl . ($queryString ? '?' . $queryString : '');
}



// A function to set or update a parameter in the query array
function set_param($PARAMS, $key, $value)
{
    $PARAMS[$key] = $value;
    return $PARAMS; // Return the modified parameters array
}



function set_params($PARAMS, $newParams = [])
{
    // Merge new parameters with the existing ones
    $PARAMS = array_merge($PARAMS, $newParams);
    return $PARAMS; // Return the modified parameters array
}



function isValidFetchedData($data, $key = null)
{
    // Check if the data is valid JSON and the expected key is present
    if (isset($data[$key]) && !empty($data[$key])) {
        return true;
    }
    return false;
}



function isValidJsonFile($file, $key = null)
{
    if (!file_exists($file)) {
        return false;
    }

    $fileContent = file_get_contents($file);
    $decodedData = json_decode($fileContent, true);

    // Check if JSON is valid and if the key is set
    if (json_last_error() !== JSON_ERROR_NONE || !isset($decodedData[$key])) {
        return false;
    }

    // Ensure that the content under the key is not an empty array
    if (empty($decodedData[$key])) {
        return false;
    }

    return true;
}



function get_age_of($file_path, $long = 0)
{
    $AGE_OF_FILE = '0';
    if (file_exists($file_path)) {
        $AGE_OF_FILE = (time() - filemtime($file_path));
    }

    if ($long):
        $seconds = $AGE_OF_FILE;
        $H = floor($seconds / 3600);
        $i = ($seconds / 60) % 60;
        $s = $seconds % 60;
        return sprintf("%02d hours, %02d minutes, %02d seconds", $H, $i, $s);
    endif;

    return $AGE_OF_FILE;
}



// Fetch collection data and save it locally if it's older than $MAX_AGE_IN_SECONDS
function fetchCollectionData($username, $token, $data_file)
{
    global $PARAMS;
    global $DISCOGS_API_URL_USERS, $DATA_FILES, $CONTEXT, $MAX_AGE_IN_SECONDS;

    $collection_file = $data_file;
    $age_of_collection_file = get_age_of($data_file);

    // Check if the file exists, is valid, and not older than the max age
    if (isValidJsonFile($collection_file, 'releases') && $age_of_collection_file < $MAX_AGE_IN_SECONDS) {
        Debug::log("Collection data is up-to-date. $age_of_collection_file secs old.");
        return;
    }

    // Fetch new data if file is invalid or too old
    $apiUrl = $DISCOGS_API_URL_USERS . $username . '/collection/folders/0/releases';
    $page = 1;
    $perPage = 500;
    $hasMorePages = true;
    $allReleases = [];

    while ($hasMorePages) {
        $url = $apiUrl . '?page=' . $page . '&per_page=' . $perPage . '&token=' . $token;
    //    $response = file_get_contents($url, false, $CONTEXT);
    //    $data = json_decode($response, true);
        $response = @file_get_contents($url, false, $CONTEXT);
        if ($response === false) {
            error_log("API fetch failed: $url");
        return null;
        }
        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON decode failed for: $url");
            return null;
        }


        if (isset($data['releases'])) {
            $allReleases = array_merge($allReleases, $data['releases']);
        }

        if (isset($data['pagination']) && $data['pagination']['pages'] > $page) {
            $page++;
        } else {
            $hasMorePages = false;
        }
    }

    // Validate fetched data before saving it to avoid overwriting with empty/invalid data
    if (isValidFetchedData(['releases' => $allReleases], 'releases')) {
        $fileData = ['releases' => $allReleases];
        file_put_contents($collection_file, json_encode($fileData, JSON_PRETTY_PRINT));
        Debug::log("Collection data updated and saved to $collection_file.");
    } else {
        Debug::log("Invalid data fetched, skipping save for collection data.");
    }
}



// Fetch folder data and save it locally if it's older than $MAX_AGE_IN_SECONDS
function fetchUserProfileData($username)
{
    global $DISCOGS_API_URL_USERS, $DATA_FILES;

    // Fetch new folder data if file is invalid or too old
    $url = $DISCOGS_API_URL_USERS . $username;

    check_data_file($DATA_FILES['profile'], rand(120,168), $url);
}


function fetchMusicbrainzReleaseData($master_id, $data_file_musicbrainz, $data_file_coverartarchive)
{   
    Debug::log( "Fetching MB Release Data");
    Debug::log( "Master ID: $master_id");

    if (is_numeric($master_id)) : 
        $release_group_id = get_musicbrainz_master_release_group($master_id);

    else:
        Debug::log("No master id");
        return;
    endif;

    if ($release_group_id) :
        //Related Sites URL
        $url_mb_rs = "https://musicbrainz.org/ws/2/release-group/$release_group_id?inc=url-rels&fmt=json";
        //Release Group URL
        $url_bm_rg = "https://musicbrainz.org/ws/2/url?inc=release-group-rels&resource=https://www.discogs.com/master/"
            . $master_id
            . "&fmt=json";
        
        $urls_mb = ["related_sites" => $url_mb_rs, "release_group" => $url_bm_rg];
        Debug::log("MB Data_file: $data_file_musicbrainz");
        //Debug::log("MB URL: $urls_mb");

        // Fetch new folder data if file is invalid or too old
        check_data_file($data_file_musicbrainz, rand(110,128), $urls_mb);

        $url_caa = "https://coverartarchive.org/release-group/$release_group_id";
        Debug::log("CAA Data_file: $data_file_coverartarchive");
        Debug::log("CAA URL: $url_caa");
        // Fetch new folder data if file is invalid or too old
        check_data_file($data_file_coverartarchive, 118, $url_caa);
    endif;
}



function fetchCustomFieldsData($username, $token)
{
    global $DISCOGS_API_URL_USERS, $DATA_FILES;

    // Fetch new folder data if file is invalid or too old
    $url = $DISCOGS_API_URL_USERS . "$username/collection/fields?token=$token";

    check_data_file($DATA_FILES['fields'], 48, $url);
}



function fetchCoverArtArchiveData($username, $token)
{
    global $DISCOGS_API_URL_USERS, $DATA_FILES;

    // Fetch new folder data if file is invalid or too old
    $url = $DISCOGS_API_URL_USERS . "$username/collection/fields?token=$token";

    check_data_file($DATA_FILES['fields'], 24, $url);
}



function fetchUserListsData($username, $token)
{
    global $DISCOGS_API_URL_USERS, $DATA_FILES;

    // Fetch new folder data if file is invalid or too old
    $url = $DISCOGS_API_URL_USERS . "$username/lists?token=$token";

    check_data_file($DATA_FILES['lists'], 48, $url);
}



function fetchFolderData($username, $token, $data_file)
{
    global $DISCOGS_API_URL_USERS;

    // Fetch new folder data if file is invalid or too old
    $url = $DISCOGS_API_URL_USERS . $username . '/collection/folders?token=' . $token;
    
    check_data_file($data_file, 24, $url);

}



// Fetch wantlist data and save it locally if it's older than $MAX_AGE_IN_SECONDS
function fetchWantlistData($username, $token, $data_file)
{
    global $DISCOGS_API_URL_USERS, $CONTEXT, $MAX_AGE_IN_SECONDS;

    // Check if the file exists, is valid, and not older than the max age
    if (isValidJsonFile($data_file, 'wants') && (time() - filemtime($data_file)) < $MAX_AGE_IN_SECONDS) {

        Debug::log("Wantlist data is up-to-date.");

        return;
    }

    // Fetch new wantlist data if file is invalid or too old
    $apiUrl = $DISCOGS_API_URL_USERS . $username . '/wants';
    $page = 1;
    $perPage = 50;
    $hasMorePages = true;
    $allWants = [];

    while ($hasMorePages) {
        $url = $apiUrl . '?page=' . $page . '&per_page=' . $perPage . '&token=' . $token;

        Debug::log("$url<br>");


        $response = file_get_contents($url, false, $CONTEXT);
        $data = json_decode($response, true);

        if (isset($data['wants'])) {
            $allWants = array_merge($allWants, $data['wants']);
        }

        if (isset($data['pagination']) && $data['pagination']['pages'] > $page) {
            $page++;
        } else {
            $hasMorePages = false;
        }
    }

    // Validate fetched data before saving it
    if (isValidFetchedData(['wants' => $allWants], 'wants')) {
        $fileData = ['wants' => $allWants];
        file_put_contents($data_file, json_encode($fileData, JSON_PRETTY_PRINT));

        Debug::log("Wantlist data updated and saved to $data_file.");


    } else {

        Debug::log("Invalid data fetched, skipping save for wantlist data.");

    }
}



// Fetch collection value data and save it locally if it's older than $MAX_AGE_IN_SECONDS
function fetchCollectionValueData($username, $token, $data_file)
{
    global $DISCOGS_API_URL_USERS;

    // Fetch new collection value data if file is invalid or too old
    $apiUrl = $DISCOGS_API_URL_USERS . $username . '/collection/value?token=' . $token;

    check_data_file($data_file, 24, $apiUrl);
}



// Function to load JSON data from statistics.json
function load_statistics_data($file_path)
{
    if (!file_exists($file_path)) {
        throw new Exception("File not found: $file_path");
    }

    $json_data = file_get_contents($file_path);
    return json_decode($json_data, true); // Convert JSON to associative array
}



function get_statistics()
{
    global $DATA_FILES, $PARAMS;

    $collection_file = $DATA_FILES['releases'];
    $value_file = $DATA_FILES['value'];
    $statistics_file = $DATA_FILES['statistics'];
    $folder_file = $DATA_FILES['folders'];

    if (!file_exists($collection_file)) {
        echo "Merged collection.json file not found. Please merge the data first.<br>";
        return;
    }

    // Only regenerate if collection file is newer than statistics file
    if (file_exists($statistics_file) && filemtime($statistics_file) >= filemtime($collection_file)) {
        Debug::log("Using cached statistics file: $statistics_file");
        return json_decode(file_get_contents($statistics_file), true);
    }

    // Load the merged data
    $data = json_decode(file_get_contents($collection_file), true);
    $releases = $data['releases'];

    if (file_exists($folder_file)) {
        $folder_data = json_decode(file_get_contents($folder_file), true);
        Debug::log("Folder data file found: $folder_file<br>");
    } else {
        $folder_data = ['folders' => []];
        Debug::log("Folder data file not found: $folder_file<br>");
    }

    // Initialize statistics structure
    $statistics = [
        'total_releases' => count($releases),
        'unique_artists' => [],
        'unique_genres' => 0,
        'releases_per_month' => [],
        'releases_per_year' => [],
        'releases_added_per_month' => [],
        'releases_added_per_year' => [],
        'releases_per_genre' => [],
        'releases_per_style' => [],
        'releases_per_artist' => [],
        'most_common_format' => [],
        'most_frequent_label' => [],
        'total_value' => ['min' => 0, 'med' => 0, 'max' => 0],
        'oldest_release' => null,
        'newest_release' => null,
        'average_release_year' => 0,
        'most_common_release_year' => [],
        'genre_distribution' => [],
        'most_common_release_country' => [],
        'top_5_artists' => [],
        'top_5_genres' => [],
        'top_5_most_expensive_releases' => [],
        'folder_counts' => [],
        'total_collection_duration' => 0
    ];

    $years = [];
    $total_years_sum = 0;
    $title_lengths = [];
    $country_count = [];
    $unique_genres = [];
    $release_values = [];

    foreach ($releases as $release) {
        $release_id = $release['id'];
        $basic_info = $release['basic_information'];
        $title = $basic_info['title'] ?? '';
        $artists = $basic_info['artists'] ?? [];
        $artist_name = $artists[0]['name'] ?? '';
        $genres = $basic_info['genres'] ?? [];

        foreach ($artists as $artist) {
            $statistics['unique_artists'][$artist['name']] = true;
            if (!isset($statistics['releases_per_artist'][$artist['name']])) {
                $statistics['releases_per_artist'][$artist['name']] = 0;
            }
            $statistics['releases_per_artist'][$artist['name']]++;
        }

        foreach ($genres as $genre) {
            $unique_genres[$genre] = true;
            if (!isset($statistics['releases_per_genre'][$genre])) {
                $statistics['releases_per_genre'][$genre] = 0;
            }
            $statistics['releases_per_genre'][$genre]++;
        }

        $statistics['unique_genres'] = count($unique_genres);

        $formats = $basic_info['formats'] ?? [];
        foreach ($formats as $format) {
            $format_name = $format['name'];
            if (!isset($statistics['most_common_format'][$format_name])) {
                $statistics['most_common_format'][$format_name] = 0;
            }
            $statistics['most_common_format'][$format_name]++;
        }

        $tracklist = $release['tracklist'] ?? [];
        foreach ($tracklist as $track) {
            $duration = strtotime($track['duration']);
            $statistics['total_collection_duration'] += $duration ? $duration : 0;
        }

        $labels = $basic_info['labels'] ?? [];
        foreach ($labels as $label) {
            $label_name = $label['name'];
            if (!isset($statistics['most_frequent_label'][$label_name])) {
                $statistics['most_frequent_label'][$label_name] = 0;
            }
            $statistics['most_frequent_label'][$label_name]++;
        }

        $year = $basic_info['year'] ?? null;
        if ($year) {
            $years[] = $year;
            $total_years_sum += $year;
            if (!isset($statistics['releases_per_year'][$year])) {
                $statistics['releases_per_year'][$year] = 0;
            }
            $statistics['releases_per_year'][$year]++;
        }

        $title_lengths[$title] = strlen($title);

        $country = $release['country'] ?? null;
        if ($country) {
            if (!isset($country_count[$country])) {
                $country_count[$country] = 0;
            }
            $country_count[$country]++;
        }

        foreach ($genres as $genre) {
            if (!isset($statistics['releases_per_genre'][$genre])) {
                $statistics['releases_per_genre'][$genre] = 0;
            }
            $statistics['releases_per_genre'][$genre]++;
        }

        $styles = $basic_info['styles'] ?? [];
        foreach ($styles as $style) {
            if (!isset($statistics['releases_per_style'][$style])) {
                $statistics['releases_per_style'][$style] = 0;
            }
            $statistics['releases_per_style'][$style]++;
        }

        $date_added = isset($release['date_added']) ? strtotime($release['date_added']) : null;
        if ($date_added) {
            $month = date('Y-m', $date_added);
            $year_added = date('Y', $date_added);
            if (!isset($statistics['releases_added_per_month'][$month])) {
                $statistics['releases_added_per_month'][$month] = 0;
            }
            $statistics['releases_added_per_month'][$month]++;
            if (!isset($statistics['releases_added_per_year'][$year_added])) {
                $statistics['releases_added_per_year'][$year_added] = 0;
            }
            $statistics['releases_added_per_year'][$year_added]++;
        }

        if (isset($release['value'])) {
            $release_values[] = [
                'title' => $title,
                'artist' => $artist_name,
                'release_id' => $release_id,
                'value' => $release['value']
            ];
        }
    }

    ksort($statistics['releases_added_per_year']);

    arsort($statistics['releases_per_year']);
    $statistics['most_common_release_year'] = array_slice($statistics['releases_per_year'], 0, 1, true);

    if (count($years) > 0) {
        $statistics['average_release_year'] = round($total_years_sum / count($years));
    }

    arsort($country_count);
    $statistics['most_common_release_country'] = array_slice($country_count, 0, 1, true);

    $total_releases = count($releases);
    foreach ($statistics['releases_per_genre'] as $genre => $count) {
        $statistics['genre_distribution'][$genre] = round(($count / $total_releases) * 100, 2) . '%';
    }

    $ValueData = json_decode(file_get_contents($value_file), true);
    if (isset($ValueData)) {
        $statistics['total_value'] = $ValueData;
    }

    $statistics['unique_artists'] = count($statistics['unique_artists']);

    arsort($statistics['releases_per_artist']);
    arsort($statistics['releases_per_genre']);
    $statistics['top_5_artists'] = array_slice($statistics['releases_per_artist'], 1, 5, true);
    $statistics['top_5_genres'] = array_slice($statistics['releases_per_genre'], 0, 5, true);

    usort($release_values, function ($a, $b) {
        return $b['value'] - $a['value'];
    });
    $statistics['top_5_most_expensive_releases'] = array_slice($release_values, 0, 5);

    file_put_contents($statistics_file, json_encode($statistics, JSON_PRETTY_PRINT));
    Debug::log("Statistics generated and saved to statistics.json.");

    if (isset($folder_data['folders']) && is_array($folder_data['folders'])) {
        foreach ($folder_data['folders'] as $folder) {
            $statistics['folder_counts'][$folder['name']] = $folder['count'];
        }
    } else {
        Debug::log("No valid folder data found.");
    }

    return $statistics;
}




function createBarChartCard($chartId, $labels, $data, $title = "Bar Chart")
{
    ob_start();
    $data = array_map(function ($amount) {
        // Remove dollar sign and commas, then convert to float, and finally round to an integer
        return intval(str_replace(['$', ','], '', $amount));
    }, $data);

    ?>
        <!-- Gallery item -->               
        <div class="statisticsGalleryItem chart-container">
            <div class="card bg-dark text-white h-100">
                <div class="card-header card-title"><?php echo $title; ?></div>
                <div class="card-body">
                    <canvas id="<?php echo $chartId; ?>" class="canvasBarChart"></canvas>
                </div>
                <div class="card-footer"><small><i class="fa fa-fw fa-chart-simple"></i></small></div>
            </div>
        </div>
        <!-- End gallery item -->

        <script>
            // Create the bar chart
            new Chart(document.getElementById('<?php echo $chartId; ?>').getContext('2d'), {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($labels); ?>,
                    datasets: [{
                        label: '<?php echo $title; ?>',
                        data: <?php echo json_encode($data); ?>,
                        backgroundColor: 'rgba(0, 123, 255, 0.5)',
                        borderColor: 'rgba(0, 123, 255, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: { duration: 500 },
                    plugins: {
                        legend: { display: false },
                        valueOnTop: { 
                            enabled: true, // Enable the plugin for this chart
                            textColor: 'CornflowerBlue', // Scoped text color
                            font: '12px Arial' // Scoped font
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { maxTicksLimit: 5 }
                        },
                        x: {
                            ticks: { maxRotation: 45, minRotation: 45 }
                        }
                    }
                }
            });
        </script>
        <?php
        return ob_get_clean();
}



function createPieChartCard($chartId, $labels, $data, $title = "Pie Chart", $show_legend = "true")
{
    ob_start();
    ?>
        <!-- Gallery item -->
        <div class="statisticsGalleryItem chart-container">
            <div class="card bg-dark text-white h-100">
                <div class="card-header card-title"><?php echo $title; ?></div>
                <div class="card-body">
                    <canvas id="<?php echo $chartId; ?>" class="canvasPieChart"></canvas>
                </div>
                <div class="card-footer"><small><i class="fa fa-fw fa-chart-pie"></i></small></div>
            </div>
        </div>
        <!-- End gallery item -->
        <script>
    new Chart(document.getElementById('<?php echo $chartId; ?>').getContext('2d'), {
        type: 'pie',
        data: {
            labels: <?php echo json_encode($labels); ?>,
            datasets: [{
                data: <?php echo json_encode($data); ?>,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.5)',
                    'rgba(54, 162, 235, 0.5)',
                    'rgba(255, 206, 86, 0.5)',
                    'rgba(75, 192, 192, 0.5)',
                    'rgba(153, 102, 255, 0.5)',
                    'rgba(255, 159, 64, 0.5)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }, // Disable the built-in legend
            },
            animation: { duration: 500 },
            layout: {
                padding: {
                    top: 50 // Add space for labels above the chart
                }
            }
        },
        plugins: [
            {
                id: 'customLabels',
                afterDraw(chart) {
                    const { ctx, chartArea: { left, right, top }, data } = chart;
                    const fontSize = 12;
                    const spacing = 30; // Space between labels
                    const labelCount = data.labels.length;
                    const totalWidth = labelCount * spacing;
                    const startX = (right + left) / 2 - totalWidth / 2;

                    ctx.save();
                    ctx.font = `${fontSize}px Arial`;
                    ctx.textAlign = 'left';
                    ctx.textBaseline = 'middle';

                    data.labels.forEach((label, index) => {
                        const color = data.datasets[0].backgroundColor[index];
                        const x = startX + index * spacing * 2;
                        const y = top - 20;

                        // Draw the color box
                        ctx.fillStyle = color;
                        ctx.fillRect(x, y, 10, 10);

                        // Draw the label text
                        ctx.fillStyle = color; // Match text color to the box color
                        ctx.fillText(label, x + 15, y + 5); // Add spacing between box and text
                    });

                    ctx.restore();
                }
            }
        ]
    });


        </script>
        <?php
        return ob_get_clean();
}



function createRadialGaugeChartCard($chartId, $labels, $data, $title = "Radial Gauge Chart")
{
    ob_start();

    // Convert monetary values to numbers and round them to whole numbers
    function convertToNumber($value)
    {
        return round(floatval(str_replace(['$', ','], '', $value)));
    }

    // Convert all values in the $data array
    $numericData = array_map('convertToNumber', $data);

    // Determine min, median, and max
    $min = min($numericData);
    $max = max($numericData);
    $median = $numericData[1]; // Assume the second value is the median

    // Round max to the next logical number
    if ($max < 10000) {
        $roundedMax = ceil($max / 1000) * 1000; // Round up to nearest 1000
    } elseif ($max < 100000) {
        $roundedMax = ceil($max / 5000) * 5000; // Round up to nearest 5000
    } else {
        $roundedMax = ceil($max / 10000) * 10000; // Round up to nearest 10000
    }

    // Normalize values to percentage for correct proportions
    $range = $roundedMax - $min;
    $minPercentage = (($median - $min) / $range) * 100;
    $medianPercentage = (($max - $median) / $range) * 100;
    $maxPercentage = 100 - ($minPercentage + $medianPercentage); // Ensure it sums to 100

    ?>
        <!-- Gauge Chart Card -->
        <div class="statisticsGalleryItem chart-container">
            <div class="card bg-dark text-white h-100">
                <div class="card-header card-title"><?php echo $title; ?></div>
                <div class="card-body d-flex justify-content-center">
                    <canvas id="<?php echo $chartId; ?>"></canvas>
                </div>
                <div class="card-footer"><small><i class="fa fa-fw fa-gauge-simple-high"></i></small>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener("DOMContentLoaded", function () {
                console.log("Chart.js is ready!");

                const ctx = document.getElementById('<?php echo $chartId; ?>').getContext('2d');

                // Destroy any existing chart instance before creating a new one
                if (Chart.getChart(ctx)) {
                    Chart.getChart(ctx).destroy();
                }

                // Original values for tooltips
                const realValues = [<?php echo $min; ?>, <?php echo $median; ?>, <?php echo $max; ?>];

                new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ["Min", "Median", "Max"],
                        datasets: [{
                            data: [<?php echo $minPercentage; ?>, <?php echo $medianPercentage; ?>, <?php echo $maxPercentage; ?>], // Proportional Segments
                            backgroundColor: [
                                'rgba(255, 206, 86, 0.7)',  // Min (Yellow)
                                'rgba(255, 159, 64, 0.7)',  // Median (Orange)
                                'rgba(255, 99, 132, 0.7)'   // Max (Red)
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        rotation: -90, // Starts at bottom
                        circumference: 180, // Creates semi-circle gauge
                        cutout: '70%', // Donut-style gauge
                        plugins: {
                            legend: { display: false }, // Show legend with colors
                            tooltip: {
                                enabled: true,
                                callbacks: {
                                    title: (tooltipItems) => tooltipItems[0].label, // Display Min, Median, Max
                                    label: (tooltipItem) => `Value: $${realValues[tooltipItem.dataIndex].toLocaleString()}`
                                }
                            }
                        }
                    }
                });
            });
        </script>
        <?php
        return ob_get_clean();
}



function createTreemapChartCard($chartId, $labels, $data, $title = "Treemap Chart")
{
    ob_start();
    ?>
        <!-- Gallery item -->
        <div class="statisticsGalleryItem chart-container">
            <div class="card bg-dark text-white h-100">
                <div class="card-header card-title"><?php echo $title; ?></div>
                <div class="card-body">
                    <canvas id="<?php echo $chartId; ?>"></canvas>
                </div>
                <div class="card-footer"><small><i class="fa fa-fw fa-table-cells-large"></i></small></div>
            </div>
        </div>

        <style>
            /* Ensure the chart container is responsive */
            #<?php echo $chartId; ?> {
                max-width: 100%; /* Prevents stretching outside screen */
                width: 100%; /* Full width */
                height: 500px; /* Adjusts height dynamically */
            }

            /* Adjust layout for mobile screens */
            @media (max-width: 768px) {
                #<?php echo $chartId; ?> {
                    height: 400px; /* Increase height on small screens */
                }
            }
        </style>

        <script>
            new Chart(document.getElementById('<?php echo $chartId; ?>').getContext('2d'), {
                type: 'treemap',
                data: {
                    datasets: [{
                        tree: <?php echo json_encode(array_map(function ($label, $value) {
                            return ['label' => $label, 'value' => $value];
                        }, $labels, $data)); ?>,
                        key: 'value',
                        backgroundColor: (ctx) => {
                            const colors = [
                                'rgba(255, 99, 132, 0.5)',
                                'rgba(54, 162, 235, 0.5)',
                                'rgba(255, 206, 86, 0.5)',
                                'rgba(75, 192, 192, 0.5)',
                                'rgba(153, 102, 255, 0.5)',
                                'rgba(255, 159, 64, 0.5)'
                            ];
                            return colors[ctx.dataIndex % colors.length];
                        },
                        borderColor: 'rgba(255, 255, 255, 0.8)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false, // Allows dynamic resizing
                    aspectRatio: 0.5, // Taller chart instead of wide
                    layout: {
                        padding: 10
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            enabled: true,
                            callbacks: {
                                title: function(tooltipItems) {
                                    const dataset = tooltipItems[0].dataset.tree;
                                    const index = tooltipItems[0].dataIndex;
                                    return dataset[index].label; // Show the correct label
                                },
                                label: function(tooltipItem) {
                                    const dataset = tooltipItem.dataset.tree;
                                    const index = tooltipItem.dataIndex;
                                    return `${dataset[index].value.toLocaleString()} items`;
                                }
                            }
                        }
                    }
                }
            });
        </script>
        <?php
        return ob_get_clean();
}



function SimpleDataArrayCard($labels, $data, $title = "Data Card")
{
    ?>

        <!-- Gallery item -->               
        <div class="statisticsGalleryItem chart-container">
        <div class="card bg-dark text-white h-100">
        <div class="card-header card-title"><?php echo $title; ?></div>
        <div class="card-body">
        <?php
        // Check that both arrays have the same number of elements
        if (count($labels) === count($data)) {
            foreach ($labels as $index => $label) { ?>

                                <p class="card-text text-center google-league-gothic" id="resize"><?php echo $label; ?></p> 
                        <?php
            }
        } else {
            echo "The arrays do not have the same length.";
        }
        ?> 
                </div>         
            <div class="card-footer"><small><i class="fa fa-fw fa-clipboard"></i> </small></div>
        </div>
    </div>
    <!-- End gallery item -->


<?php }



function SimpleDataItemCard($data, $title = "Data Item Card", $subtitle = "")
{
    ?>

        <!-- Gallery item -->               
        <div class="statisticsGalleryItem chart-container">
        <div class="card bg-dark text-white h-100">
        <div class="card-header card-title"><?php echo $title; ?></div>
        <div class="card-body">

            <h6 class="card-subtitle mb-2 text-muted text-center"><?php echo $subtitle; ?></h6>
            <p class="card-text text-center google-league-gothic" id="resize"><?php echo $data ?></p> 
            </div>

            <div class="card-footer"><small><i class="fa fa-fw fa-clipboard"></i> </small></div>
        </div>
    </div>
    <!-- End gallery item -->

<?php }



function extract_labels_and_data($data_array)
{
    $labels = array_keys($data_array);
    $data = array_values($data_array);
    return [$labels, $data];
}



function get_collection($data_file, $table)
{
    global $PARAMS;

    // Load the entire collection from the local JSON file
    if (!file_exists($data_file)) {
        echo "Collection file not found!";
        return [$table => [], 'total_pages' => 0, 'value' => null, 'date_added_stats' => []];
    }

    // Decode the full collection JSON
    $data = json_decode(file_get_contents($data_file), true);

    // Ensure the specified table is set in the JSON (e.g., 'releases', 'wants', 'folders')
    if (!isset($data[$table])) {
        echo "Invalid JSON structure: '$table' not found!";
        return [$table => [], 'total_pages' => 0, 'value' => null, 'date_added_stats' => []];
    }

    // Extract the specified table data (e.g., 'releases', 'wants', 'folders')
    $items = $data[$table];

    // Filter by folder_id if specified and applicable, but skip filtering if folder_id is 0 (show all items)
    if ($table === 'releases' && isset($PARAMS['folder_id']) && $PARAMS['folder_id'] != 0) {
        $items = array_filter($items, function ($item) use ($PARAMS) {
            return $item['folder_id'] == $PARAMS['folder_id'];
        });
    }

    // Filter by artist_id if specified
    if ($table === 'releases' && isset($PARAMS['artist_id']) && !empty($PARAMS['artist_id'])) {
        $items = array_filter($items, function ($item) use ($PARAMS) {
            foreach ($item['basic_information']['artists'] as $artist) {
                if ($artist['id'] == $PARAMS['artist_id']) {
                    return true;
                }
            }
            return false;
        });
    }

    // Get the total number of items after filtering
    $total_items = count($items);

    // Sort the items based on the 'sort_by' and 'order' parameters if applicable
    $sort_by = $PARAMS['sort_by'] ?? 'date_added';
    $order = $PARAMS['order'] ?? 'asc';

    // Map short sort_by options to their respective fields
    $sortField = match ($sort_by) {
        'added' => 'date_added',
        'title' => 'basic_information.title',
        'year' => 'basic_information.year',
        'artist' => 'basic_information.artists.0.name',
        default => 'date_added',
    };

    usort($items, function ($a, $b) use ($sortField, $order) {
        $valueA = get_hardcoded_value($a, $sortField);
        $valueB = get_hardcoded_value($b, $sortField);

        if ($valueA === null)
            $valueA = '';
        if ($valueB === null)
            $valueB = '';

        if (is_numeric($valueA) && is_numeric($valueB)) {
            $comparison = $valueA - $valueB;
        } else {
            $comparison = strcmp($valueA, $valueB);
        }

        return ($order === 'desc') ? -$comparison : $comparison;
    });

    // Paginate the results based on 'page' and 'per_page' parameters
    $page = $PARAMS['page'] ?? 1;
    $per_page = $PARAMS['per_page'] ?? 48;
    $offset = ($page - 1) * $per_page;

    // Calculate total pages
    $total_pages = ceil($total_items / $per_page);

    // Extract the slice of items for the current page
    $pagedItems = array_slice($items, $offset, $per_page);

    // Retrieve collection value data from the JSON file
    $collection_value = $data['value'] ?? null;


    // Return the paginated items, total pages, collection value, and date_added statistics
    return array($pagedItems, $total_pages, $collection_value); //, $date_added_stats);
}



function display_related_sites() {
        
    global $LOCAL_RELEASE_MUSICBRAINZ_FILE;
        // Whitelisted labels (either from type-id or domain)
        $allowed_sites = [
            'AllMusic', 'Discogs', 'Pitchfork', 'Genius', 'Bandcamp',
            'SoundCloud', 'YouTube', 'Official homepage', 'homepage','Wikipedia',
            'iTunes', 'Spotify', 'Myspace', 'RYM', 'Rateyourmusic','Tidal','Metal Archives','AOTY'
        ];
    
        // Known MusicBrainz type-id to label map
        $type_map = [
            'a50a1d20-2b20-4d2c-9a29-eb771dd78386' => 'AllMusic',
            '99e550f3-5ab4-3110-b5b9-fe01d970b126' => 'Discogs',
            '156344d3-da8b-40c6-8b10-7b1c22727124' => 'Genius', // Lyrics
            'c3ac9c3b-f546-4d15-873f-b294d2c1b708' => 'Review', // fallback
            'b988d08c-5d86-4a57-9557-c83b399e3580' => 'Wikidata',
            'b78c22ad-f82c-4e02-9f9e-eda07765b5b6' => 'Bandcamp',
            '92e42e8a-69be-4b8a-9f6b-63ebd6ad61d3' => 'SoundCloud',
            '857c74d6-3f9e-4e6f-8112-f24f3c1a2fc3' => 'YouTube',
            '6f1c7d79-449d-4c1f-8c47-7b748b8d2e04' => 'Official homepage',
            'a9e7a0ff-3963-3c06-9c4c-7b5a45b63e51' => 'Wikipedia',
            '1f5b3c78-cd7c-4b93-9b78-42ee39f8f45b' => 'iTunes',
            '8e5e3a7e-8f84-4ce1-8e9c-768d8f821d8b' => 'Spotify',
            'e0c9b41e-d48b-4f3c-8be7-b34cd6e5f469' => 'Myspace',
            'e59b1c48-1829-43a2-9282-f4b5bcbf6db6' => 'Rateyourmusic',
            '1e13d665-1d2a-4fe4-9435-2f3b5447b486' => 'Tidal',
        ];
    
        if (!file_exists($LOCAL_RELEASE_MUSICBRAINZ_FILE)) {
            echo "<p>No MusicBrainz data found.</p>";
            return;
        }
    
        $json = file_get_contents($LOCAL_RELEASE_MUSICBRAINZ_FILE);
        $data = json_decode($json, true);
    
        $relations = $data['related_sites']['relations'] ?? $data['release_group']['relations'] ?? [];
    
        if (empty($relations)) {
            echo "<p>No related sites found in MusicBrainz data.</p>";
            return;
        }
    
        foreach ($relations as $relation) {
            if (!isset($relation['url']['resource'])) continue;
    
            $url = htmlspecialchars($relation['url']['resource']);
            $typeId = $relation['type-id'] ?? null;
            $domain = parse_url($url, PHP_URL_HOST);
            $label = null;
            $faIcon = "arrow-up-right-from-square";
            $style = "";
    
            // Check by type-id first
            if ($typeId && isset($type_map[$typeId])) {
                $label = $type_map[$typeId];
             }
    
            // Fallback to domain detection if not in map or is generic
            //if (!$label || strtolower($label) === 'review') {
                if (str_contains($domain, 'pitchfork')) {
                    $label = 'Pitchfork';
                } elseif (str_contains($domain, 'genius.com')) {
                    $label = 'Genius';
                    $faIcon = "brain";
                    $style = "color: darkgrey; background-color: yellow;";
                } elseif (str_contains($domain, 'rateyourmusic.com')) {
                    $label = 'RYM';
                    $faIcon = "star";
                    $style = "color: white; background-color: cornflowerblue;";
                } elseif (str_contains($domain, 'bandcamp.com')) {
                    $label = 'Bandcamp';
                } elseif (str_contains($domain, 'soundcloud.com')) {
                    $label = 'SoundCloud';
                } elseif (str_contains($domain, 'youtube.com')) {
                    $label = 'YouTube';
                    $faIcon = "youtube";
                    $style = "color: white; background-color: red;";
                } elseif (str_contains($domain, 'itunes.apple.com')) {
                    $label = 'iTunes';
                    $faIcon = "itunes-note";
                    $style = "color: red; background-color: black;";
                } elseif (str_contains($domain, 'spotify.com')) {
                    $label = 'Spotify';
                    $faIcon = "spotify";
                    $style = "color: black; background-color: green;";
                } elseif (str_contains($domain, 'myspace.com')) {
                    $label = 'Myspace';
                } elseif (str_contains($domain, 'tidal.com')) {
                    $label = 'Tidal';
                } elseif (str_contains($domain, 'wikipedia')) {
                    $label = 'Wikipedia';
                    $faIcon = 'wikipedia-w';
                    $style = "color: black; background-color: lightgrey;";
                } elseif (str_contains($domain, 'allmusic.com')) {
                    $label = 'AllMusic';
                    $faIcon = "music";
                    $style = "color: white; background-color: royalblue;";
                } elseif (str_contains($domain, 'albumoftheyear.org')) {
                    $label = 'AOTY';
                    $faIcon = "music";
                    $style = "color: white; background-color: royalblue;";
                } elseif (str_contains($domain, 'discogs.com')) {
                    $label = 'Discogs';
                    $faIcon = "crown";
                    $style = "background-color: darkgrey";
                } elseif (str_contains($domain, 'metal-archives.com')) {
                    $label = 'Metal Archives';
                    $faIcon = "guitar";
                    $style = "background-color: darkred";
                }
            //}
            
            if ($label && in_array($label, $allowed_sites)) {
                echo "<a href=\"$url\" class=\"btn btn-sm m-1 border-0\" role=\"button\" style=\"$style\"><span class=\"pe-1 border-1 border-mute border-end disabled \"><i class=\"text-black fa fa-fw fa-$faIcon\"></i></span> $label</a>";
            }
        }
    

        

    }
    



// Helper function to access values based on predefined sortField
function get_hardcoded_value($item, $sortField)
{
    switch ($sortField) {
        case 'date_added':
            return $item['date_added'] ?? '';
        case 'basic_information.title':
            return $item['basic_information']['title'] ?? '';
        case 'basic_information.year':
            return $item['basic_information']['year'] ?? '';
        case 'basic_information.artists.0.name':
            return $item['basic_information']['artists'][0]['name'] ?? '';
        default:
            return '';
    }
}




function get_random_release_id($folder_id)
{
    global $DATA_FILES;

    // Load the entire collection from the local JSON file
    if (!file_exists($DATA_FILES['releases'])) {
        echo "Collection file not found!";
        return null;
    }

    // Decode the full collection JSON
    $data = json_decode(file_get_contents($DATA_FILES['releases']), true);

    // Ensure the 'releases' table is set in the JSON
    if (!isset($data['releases'])) {
        echo "'releases' not found in the JSON!";
        return null;
    }

    // Filter the releases by the given folder_id, unless folder_id is 0 (all releases)
    $filteredReleases = array_filter($data['releases'], function ($release) use ($folder_id) {
        return ($folder_id == 0 || $release['folder_id'] == $folder_id);
    });

    // If no releases found after filtering
    if (empty($filteredReleases)) {
        echo "No releases found for folder_id: $folder_id";
        return null;
    }

    // Select a random release from the filtered list
    $randomRelease = $filteredReleases[array_rand($filteredReleases)];
    // Return the release_id of the randomly selected release
    return $randomRelease['id'] ?? null;
}



function get_release_information($release_id, $return = 1)
{
    if (!is_numeric($release_id)) {
        echo "<div class=\"alert alert-danger\">Invalid ID. Here is a random release:</div>";
        $release_id = get_random_release_id('0');
    }

    global $DISCOGS_API_URL, $DISCOGS_TOKEN;
    global $LOCAL_RELEASES_DATA_ROOT;


    // Create the releases directory if it doesn't exist
    if (!is_dir($LOCAL_RELEASES_DATA_ROOT)) {
        mkdir($LOCAL_RELEASES_DATA_ROOT, 0777, true);
    }

    // If the file is invalid or older than 12 hours, fetch the data from the API
    $releasejson = $DISCOGS_API_URL
        . "releases/"
        . $release_id
        . "?token=" . $DISCOGS_TOKEN;


    if (is_numeric($release_id)) {
        Debug::log("$release_id is numeric!");
        $LOCAL_RELEASE_DATA_FILE = $LOCAL_RELEASES_DATA_ROOT . $release_id . '.json';
        $releasedata = check_data_file($LOCAL_RELEASE_DATA_FILE, 64, $releasejson);
        if ($return) 
            return process_release_data($releasedata);

    } else {
        Debug::log("$release_id is not numeric!");
        return;
    }
    
}



function get_release_master_information($master_id, $release_id)
{

    global $DISCOGS_API_URL, $DISCOGS_TOKEN;
    global $LOCAL_RELEASE_MASTER_DATA_FILE, $LOCAL_RELEASES_DATA_ROOT, $LOCAL_USER_RELEASES_DATA_ROOT;

    // Create the releases directory if it doesn't exist
    if (!is_dir($LOCAL_USER_RELEASES_DATA_ROOT)) {
        mkdir($LOCAL_USER_RELEASES_DATA_ROOT, 0777, true);
    }

     // If the file is invalid or older than 12 hours, fetch the data from the API
    $masterreleasejson = $DISCOGS_API_URL
        . "masters/"
        . $master_id
        . "?token=" . $DISCOGS_TOKEN;

    // Save the my-release data to the local file
    if (is_numeric($master_id)) {
        Debug::log("Master $master_id is numeric!");
        $LOCAL_RELEASE_MASTER_DATA_FILE = $LOCAL_RELEASES_DATA_ROOT . $release_id . '_master.json';
        $masterreleaseinfo = check_data_file($LOCAL_RELEASE_MASTER_DATA_FILE, 64, $masterreleasejson);
        return $masterreleaseinfo;

    } else {
        Debug::log("Master $release_id is not numeric!");
        return;
    }

}

function get_musicbrainz_master_release_group($master_id)
{
    global $CONTEXT2;
    $musicbrainzjson = "https://musicbrainz.org/ws/2/url?inc=release-group-rels&resource=https://www.discogs.com/master/"
        . $master_id
        . "&fmt=json";

    Debug::log("MB Json URL: $musicbrainzjson");


    $musicbrainzdata = @file_get_contents($musicbrainzjson, false, $CONTEXT2);

    if ($musicbrainzdata === FALSE) {
        return;
    }
    
    $data = json_decode($musicbrainzdata, true);
    if (!isset($data['relations'][0]['release_group'])) {
        return;
    }

    $release_group = $data['relations'][0]['release_group'];
    Debug::log("Release Group ID: " . $release_group['id']);
    return $release_group['id'];
}

function get_musicbrainz_id($release_id)
{
    global $LOCAL_USER_RELEASES_DATA_ROOT;
    $MUSICBRAINZ_DATA_FILE = $LOCAL_USER_RELEASES_DATA_ROOT . $release_id . '_musicbrainz.json';

    Debug::log("MB Datafile: $MUSICBRAINZ_DATA_FILE");

    $musicbrainzid = 0;

    if (
        file_exists($MUSICBRAINZ_DATA_FILE)
    ) {
        // Check if the file contains valid JSON
        $musicbrainzdata = file_get_contents($MUSICBRAINZ_DATA_FILE);
        $musicbrainzinfo = json_decode($musicbrainzdata, true);

        Debug::log($musicbrainzdata);

        // Check if the JSON is valid and not null
        if (json_last_error() === JSON_ERROR_NONE && $musicbrainzinfo !== null) {
            $musicbrainzid = $musicbrainzinfo['id'];
        }
    }

    return $musicbrainzid;
}


function fetch_json_with_throttle($url)
{
    global $PARAMS;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => true, // 
        CURLOPT_USERAGENT => 'RateMyCrate/1.0 (+https://ratemycrate.com)',
    ]);

    $response = curl_exec($ch);
    if ($response === false) {
        Debug::log("cURL error for $url: " . curl_error($ch));
        curl_close($ch);
        return null;
    }

    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Split headers/body more safely
    $headers_raw = substr($response, 0, $header_size);
    $body = trim(substr($response, $header_size));

    // Parse headers into array
    $headers = [];
    foreach (explode("\r\n", $headers_raw) as $line) {
        if (strpos($line, ':') !== false) {
            list($k, $v) = explode(':', $line, 2);
            $headers[trim($k)] = trim($v);
        }
    }

    // Throttle based on rate limit headers
    if (isset($headers['X-Discogs-Ratelimit-Remaining'])) {
        $remaining = (int) $headers['X-Discogs-Ratelimit-Remaining'];
        if ($remaining <= 1) {
            Debug::log("Approaching rate limit. Sleeping 60s...");
            sleep(60);
        }
    }

    // Retry on 429
    if ($status_code === 429) {
        Debug::log("429 Too Many Requests. Sleeping 60s and retrying...");
        sleep(60);
        return fetch_json_with_throttle($url);
    }

// Detect "See: ..." redirect to archive.org JSON
if (preg_match('/See:\s*(https?:\/\/\S+)/', $body, $matches)) {
    $redirect_url = trim($matches[1]);
    Debug::log("Redirecting to archive.org JSON: $redirect_url");
    return fetch_json_with_throttle($redirect_url); // try again with the new URL
}

// Decode the JSON body
$decoded = json_decode($body, true);
if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
    if (!empty($PARAMS['debug'])) {
        add_debug("Invalid JSON from $url: " . json_last_error_msg());
        add_debug("HTTP status: $status_code");
        add_debug("Raw response body (first 500 chars): " . substr($body, 0, 500));    }
    return null;
}

    return $decoded;
}





function fetch_data_file($data_file, $data_file_json, $flags = 0)
{
    global $PARAMS;

    if (empty($data_file_json)) {
        Debug::log("No input provided.");
        return null;
    }

    $combined_data = is_array($data_file_json) ? [] : null;
    $sources = is_array($data_file_json) ? $data_file_json : ['single' => $data_file_json];

    foreach ($sources as $key => $url) {
        Debug::log("Fetching data from: $url");
        $decoded_data = fetch_json_with_throttle($url);
        //echo $decoded_data;

        if ($decoded_data === null) {
            Debug::log("Failed to decode JSON from $url");
            continue;
        }

        if (is_array($data_file_json)) {
            $combined_data[$key] = $decoded_data;
        } else {
            $combined_data = $decoded_data;
        }
    }

    if ($combined_data !== null) {
        $json = json_encode($combined_data, JSON_PRETTY_PRINT);
        if ($json === false) {
            Debug::log("JSON encoding failed: " . json_last_error_msg());
            return null;
        }

        file_put_contents($data_file, $json, $flags);
        return $combined_data;
    }

    Debug::log("No valid data retrieved, not saving file.");
    return null;
}





function check_data_file($data_file, $age_in_hours = 8, $data_file_json = "")
{

    $max_seconds = $age_in_hours * 60 * 60;

    Debug::log("Checking $data_file");

    // IF DATA IS VALID AND IS NOT OLD, RETURN DATA

    if (
        file_exists($data_file) && (time() - filemtime($data_file)) < $max_seconds
    ) {

        Debug::log("-> $data_file is not old.");
        // Check if the file contains valid JSON
        $data_file_data = file_get_contents($data_file);
        $data_file_info = json_decode($data_file_data, true);

        // Check if the JSON is valid and not null
        if (json_last_error() === JSON_ERROR_NONE && $data_file_json !== null) {
            Debug::log("-> $data_file is valid.");
            return $data_file_info;

        }
    }

    if ($age_in_hours > 0) {
        Debug::log("-> $data_file is $age_in_hours hours old.");
        Debug::log("-> Updating $data_file.");
        $data_file_info = fetch_data_file($data_file, $data_file_json);
    }


        return $data_file_info;

}



function get_my_release_information($release_id)
{
    global $DISCOGS_API_URL_USERS, $PARAMS, $DISCOGS_TOKEN, $CONTEXT;
    global $LOCAL_USER_RELEASES_DATA_ROOT;
    
    $myreleasejson = $DISCOGS_API_URL_USERS
        . $PARAMS['username']
        . "/collection/releases/"
        . $release_id
        . "?token=" . $DISCOGS_TOKEN;

    if (is_numeric($release_id)) {
        Debug::log("My $release_id is numeric!");
        $LOCAL_USER_MYRELEASE_DATA_FILE = $LOCAL_USER_RELEASES_DATA_ROOT . $release_id . '_mine.json';
        $myreleaseinfo = check_data_file($LOCAL_USER_MYRELEASE_DATA_FILE, rand(128,168), $myreleasejson);
        return $myreleaseinfo;

    } else {
        Debug::log("My $release_id is not numeric!");
        return;
    }

}


function process_release_data($releasedata)
{
    Debug::log("Processing Release Data");
    $myreleaseinfo = get_my_release_information($releasedata['id']);

    $releaseinfo = $releasedata;
    
    // Combine artist names
    $releaseinfo['artists_plain'] = implode(", ", array_column($releasedata['artists'], 'name'));

    $releaseinfo['artists_linked'] = '';
    $releaseinfo['artists_linked'] .= implode(" ", array_map(function ($artist) {
        $artist_url = "https://www.discogs.com/artist/{$artist['id']}";
        return "<a class='display-release-data-indent list-group-item list-group-item-action' href=\"{$artist_url}\" target=\"_blank\">{$artist['name']} <i class='fa fa-fw fa-arrow-up-right-from-square'></i></a> ";
    }, $releasedata['artists']));
    $releaseinfo['artists_linked'] .= "";

    $releaseinfo['artists_list'] = '<ul class="list-group">';
    $releaseinfo['artists_list'] .= implode(" ", array_map(function ($artist) {
        return "<li class='display-release-data-indent list-group-item list-group-item-action'>{$artist['name']}</li> ";
    }, $releasedata['artists']));
    $releaseinfo['artists_list'] .= '</ul>';

    //global $myreleaseinfo;
    // Process tracklist and extra artists
    $release_tracklist_rows = '';
    $track_extraartists_list = '';
    if (array_key_exists('tracklist', $releasedata)) {
        $tracklist = $releasedata['tracklist'];
        $number_of_release_tracklist_tracks = sizeof($tracklist);
        $release_tracklist_rows = '<tr><th scope="col">Disk/Side</th><th scope="col">Track Name</th><th scope="col">mm:ss</th></tr>';
        for ($i = 0; $i < $number_of_release_tracklist_tracks; $i++) {
            $track_extraartists_list = ''; // <-- This line prevents leftover data!
            if (array_key_exists('extraartists', $releasedata['tracklist'][$i])) {
                $track_extraartists = $tracklist[$i]['extraartists'];
                $roles = [];

                // Group artists by their roles
                foreach ($track_extraartists as $extraartist) {
                    $role = $extraartist['role'];
                    $name = $extraartist['name'];

                    // Group the artists under their respective roles
                    if (!array_key_exists($role, $roles)) {
                        $roles[$role] = [];
                    }
                    $roles[$role][] = $name;
                }

                // Build the list of artists for each role
                $track_extraartists_list = '';
                foreach ($roles as $role => $artistss) {
                    $artist_names = implode(', ', $artistss);  // Combine artist names with a comma separator
                    $track_extraartists_list .= '<strong>' . $role . ':</strong> ' . $artist_names . '<br>';
                }
            }

            $duration = $tracklist[$i]['duration'];
            if ($duration == "") {
                $duration = "--:--";
            }
            $release_tracklist_rows .= '<tr><th data-align="left" style="width:1%"><p class="text-body-secondary track-list-position">'
                . $tracklist[$i]['position']
                . " "
                . '</p></th><td data-align="left"><p class="text-body-primary track-list-titles fw-bold">'
                . $tracklist[$i]['title']
                . '</p><p class="display-release-data-indent text-muted track-list-artists fw-light">'
                . $track_extraartists_list
                . '</p></td><td data-align="left" class="text-body-secondary track-list-position">'
                . $duration
                . '</td></tr>';
        }
    }

    // Store processed tracklist
    $releaseinfo['tracklist_rows'] = $release_tracklist_rows;


    // Process extra artists at the release level
    $extra_artists_rows = '<ul class="list-group">';
    if (array_key_exists('extraartists', $releasedata)) {
        $extraartists = $releasedata['extraartists'];
        $grouped_artists = [];

        // Group artists by role
        foreach ($extraartists as $extraartist) {
            $role = $extraartist['role'];
            $name = $extraartist['name'];
            $tracks = (isset($extraartist['tracks']) && !empty($extraartist['tracks'])) ? ' (' . $extraartist['tracks'] . ')' : '';


            if (!isset($grouped_artists[$role])) {
                $grouped_artists[$role] = [];
            }
            $grouped_artists[$role][] = $name . $tracks;
        }

        // Build the HTML output
        foreach ($grouped_artists as $role => $artists) {
            $extra_artists_rows .= "<li class=\"list-group-item\"><strong>$role</strong></li>\n";
            foreach ($artists as $artist) {
                $extra_artists_rows .= "<li class=\"display-release-data-indent list-group-item list-group-item-action text-muted track-list-artists fw-light\">$artist</li>\n";
            }
        }
    }
    $extra_artists_rows .= '</ul>' . "\n\n";

    // Store processed extra artists
    $releaseinfo['extra_artists_rows'] = $extra_artists_rows;


    $labelnames = '';
    if (array_key_exists('labels', $releaseinfo)):
        $number_of_labels = sizeof($releasedata['labels']);
        for ($i = 0; $i < $number_of_labels; $i++):
            $labelnames .= '<li class="display-release-data-indent list-group-item list-group-item-action">';

            if (array_key_exists('name', $releasedata['labels'][$i]))
                $labelnames .= $releaseinfo['labels'][$i]['name'];
            if (array_key_exists('catno', $releasedata['labels'][$i]))
                $labelnames .= ', ' . $releaseinfo['labels'][$i]['catno']
                    . '</li>';
        endfor;
    endif;
    // Store processed label names
    $labelnames .= '';
    $releaseinfo['label_names'] = $labelnames;


    $formats = '';
    $vinyl_descriptions = '';
    if (array_key_exists('formats', $releasedata)):
        $number_of_formats = sizeof($releasedata['formats']);
        for ($i = 0; $i < $number_of_formats; $i++):
            $format_separator = '<li class="display-release-data-indent list-group-item list-group-item-action">';
            if ($i != 0 && $number_of_formats > 1):
                $format_separator = '</li><li class="display-release-data-indent list-group-item list-group-item-action">';
            endif;
            if (array_key_exists('name', $releasedata['formats'][$i])):
                $qty = '';
                if ($releasedata['formats'][$i]['qty'] > 1)
                    $qty = ' (x ' . $releasedata['formats'][$i]['qty'] . ')';
                $formats = $formats
                    . $format_separator
                    . '<strong>'
                    . $releasedata['formats'][$i]['name']
                    . '</strong>'
                    . $qty;
            endif;
            if (!array_key_exists('text', $releasedata['formats'][$i]) && !array_key_exists('descriptions', $releasedata['formats'][$i]))
                $formats = $formats
                    . ' ! ';
            if (array_key_exists('descriptions', $releasedata['formats'][$i])):
                $formats = $formats
                    . ', ' . implode(", ", $releasedata['formats'][$i]['descriptions']);
                $vinyl_descriptions = implode(", ", $releasedata['formats'][$i]['descriptions']);
            endif;
            if (!array_key_exists('descriptions', $releasedata['formats'][$i]))
                $formats = $formats
                    . ' ! ';
            if (array_key_exists('text', $releasedata['formats'][$i]))
                $formats = $formats
                    . ', <i>' . $releasedata['formats'][$i]['text'] . '</i>';
            // . '<br>';
        endfor;

    endif;
    // Store processed label names
    $formats .= '</li>';
    $formats .= '';
    $releaseinfo['formats'] = $formats;
    $releaseinfo['vinyl_descriptions'] = $vinyl_descriptions;

    $genres = implode(", ", $releasedata['genres']);
    // Store processed label names
    $releaseinfo['genres'] = $genres;

    $styles = "";
    if (array_key_exists('styles', $releaseinfo))
        $styles = implode(", ", $releasedata['styles']);
    // Store processed label names
    $releaseinfo['styles'] = $styles;

    $identifier_rows = '<ul class="list-group">';
    if (array_key_exists('identifiers', $releasedata)):
        $identifiers = $releasedata['identifiers'];
        $number_of_identifiers = sizeof($identifiers);
        for ($i = 0; $i < $number_of_identifiers; $i++):
            $identifier_type = '';
            $identifier_value = '';
            $identifier_description = '';
            $identifier_type = $identifiers[$i]['type'];
            $identifier_value = $identifiers[$i]['value'];
            if (isset($identifiers[$i]['description']))
                $identifier_description = $identifiers[$i]['description'];

            $identifier_rows = $identifier_rows . '<li class="list-group-item list-group-item-action"><strong>'
                . $identifier_type . ' (' . @$identifier_description
                . ')</strong></li>'
                . '<li class="display-release-data-indent list-group-item list-group-item-action text-muted track-list-artists fw-light">'
                . $identifier_value
                . '</li>';


        endfor;
    endif;
    $identifier_rows = $identifier_rows . '</ul>';
    // Store processed label names
    $releaseinfo['identifier_rows'] = $identifier_rows;

    $list_of_companies_rows = '<ul class="list-group">';
    if (array_key_exists('companies', $releasedata)) {
        $companies = $releasedata['companies'];
        $grouped_companies = [];

        // Group companies by entity_type_name
        foreach ($companies as $company) {
            $entity_type_name = $company['entity_type_name'];
            $company_name = $company['name'];

            if (!isset($grouped_companies[$entity_type_name])) {
                $grouped_companies[$entity_type_name] = [];
            }
            $grouped_companies[$entity_type_name][] = $company_name;
        }

        // Build the HTML output
        foreach ($grouped_companies as $entity_type_name => $company_names) {
            $list_of_companies_rows .= '<li class="list-group-item list-group-item-action"><strong>' . $entity_type_name . '</strong></li>' . "\n";
            foreach ($company_names as $name) {
                $list_of_companies_rows .= '<li class="display-release-data-indent list-group-item list-group-item-action text-muted fw-light">' . $name . '</li>' . "\n";
            }
        }
    }
    $list_of_companies_rows .= '</ul>'
        . "\n\n";



    // Store processed label names
    $releaseinfo['companies_rows'] = $list_of_companies_rows;

    $releasenotes = '';
    $my_release_notes = '';

    if (array_key_exists('notes', $releasedata))
        $releasenotes = $releasedata['notes'];

    // Store processed label names
    $releaseinfo['releasenotes'] = $releasenotes;

    //
    // "My Release" information
    //
    $my_release_notes_rows = '';
    if (array_key_exists('notes', $myreleaseinfo['releases'][0])):
        $my_release_notes_rows = '';
        foreach ($myreleaseinfo['releases'][0]['notes'] as $mynotes):
            if ($mynotes['field_id'] == 1):
                $noteicon = 'fa fa-fw fa-record-vinyl';
                $notetype = 'Media';
            elseif ($mynotes['field_id'] == 2):
                $noteicon = 'fa fa-fw fa-square';
                $notetype = 'Jacket';
            elseif ($mynotes['field_id'] == 3):
                $noteicon = 'fa fa-fw fa-clipboard';
                $notetype = 'Notes';
                //$my_release_notes = '<li class="card list-group dsdasd">' . $mynotes['value'] . '</li>';
            elseif ($mynotes['field_id'] == 4):
                $noteicon = 'fa fa-fw fa-clipboard';
                $notetype = 'Category';
            endif;

            $my_release_notes_rows = $my_release_notes_rows
                . '<li class="card-header list-group-item text-muted"><i class="text-muted '
                . $noteicon
                . '"></i> '
                . $notetype
                . '</li><li class="display-release-data-indent list-group-item display-release-data-indent">'
                . $mynotes['value']
                . '</li>
';
        endforeach;
    endif;
    $releaseinfo['rating'] = $releasedata['community']['rating']['average'];
    $releaseinfo['myrating'] = $myreleaseinfo['releases'][0]['rating'];

    // Store processed label names
    $releaseinfo['my_release_notes_rows'] = $my_release_notes_rows;
    $releaseinfo['my_release_notes'] = $my_release_notes;

    //Master Release information
    if (isset($releaseinfo['master_id']) && $releaseinfo['master_id']) {
        $masterreleaseinfo = get_release_master_information($releaseinfo['master_id'], $releaseinfo['id']);
    
    $releaseinfo['master_id'] = $masterreleaseinfo['id'] ?? "";
    $releaseinfo['master_release_year'] = $masterreleaseinfo['year'] ?? "";
    $releaseinfo['master_uri'] = $masterreleaseinfo['uri'] ?? "";
    $releaseinfo['master_main_release_id'] = $masterreleaseinfo['main_release'] ?? "";
    $releaseinfo['master_main_release_url'] = $masterreleaseinfo['main_release_url'] ?? "";
    // Musicbrainz ID information
    $mbdata = get_musicbrainz_master_release_group($releaseinfo['master_id']);
    $releaseinfo['master_musicbrainz_release_group_id'] = $mbdata ?? "";
    }

    $musicbrainzid = get_musicbrainz_id($releasedata['id']);
    $releaseinfo['musicbrainzid'] = $musicbrainzid;
    $releaseinfo['most_recent_release'] = $masterreleaseinfo['most_recent_release'] ?? "";



    return $releaseinfo;
}


// PULL DISCOGS DATA REGARDING MY COLLECTION

if ($PARAMS['type'] != 'statistics'):
    $getcollection = get_collection($DATA_FILES[$PARAMS['type']], $PARAMS['type']);
    $collection = $getcollection[0];       // Paginated collection data
    $total_pages = $getcollection[1];      // Total number of pages
    $collection_value = $getcollection[2]; // Collection value data
endif;


function paginate($current, $last, $max = null)
{
    global $PARAMS;


    // Use last page number as max if not provided
    $max = ($max === null) ? $last : $max;

    // Calculate the range of pages to display
    $halfMax = floor($max / 2);

    // Always include the first and last pages in the navigation
    $start = max(1, min($last - $max + 1, $current - $halfMax));
    $end = min($last, $start + $max - 1);

    // Adjust the start if the end is at the last page
    $start = max(1, $end - $max + 1);

    // Display ellipsis if needed
    $ellipsisStart = ($start > 1) ? true : false;
    $ellipsisEnd = ($end < $last) ? true : false;

    // Generate HTML for pagination
    $html = '';

    if ($ellipsisStart) {
        $url = build_url("/", set_param($PARAMS, 'page', '1'));
        $html .= '<!-- Ellipsis Start -->';
        $html .= '<li class="page-item"><a class="page-link paginationItem" href="' . $url . '">1</a></li><li class="page-item"><a class="page-link paginationItem disabled" href="#">..</a></li>';
    }
    $html .= '<!-- Pages Start -->';
    for ($i = $start; $i <= $end; $i++) {
        $url = build_url("/", set_param($PARAMS, 'page', $i));
        $html .= ($i == $current) ? '<li class="page-item"><a class="page-link paginationItem disabled">' . $i . '</a></li>' : '<li class="page-item"><a class="text-accent_color page-link paginationItem" href="' . $url . '">' . $i . '</a></li>';
    }

    if ($ellipsisEnd) {
        $url = build_url("/", set_param($PARAMS, 'page', $last));
        $html .= '<!-- Ellipsis End -->';
        $html .= '<li class="page-item"><a class="page-link paginationItem disabled">..</a></li><li class="page-item"><a class="text-accent_color page-link paginationItem" href="' . $url . '">' . $last . '</a></li>';
    }

    return $html;
}


function wrap_table_rows($title, $rows)
{

    $table = '<!-- START TABLE ' . $title . ' -->'
        . '<div class="p-1 table-responsive">'
        . '<table class="table table-striped table-bordered">'
        . '<tbody>';

    if ($title):
        $table = $table . '<tr><th scope="row" colspan="3" style="width:1%">' . $title . '</th></tr>';
    endif;

    $table = $table . $rows;

    $table = $table
        . '</tbody>'
        . '</table>'
        . '</div> <!-- END ' . $title . ' -->';

    return $table;
}


function wrap_listgroup_items($groupname, $items)
{

    $table = "\n <!-- START $groupname  -->\n";

    $table .= $items;

    $table .= "<!-- END $groupname -->";
    //. '</ul> <!-- END ' . $groupname . ' -->';

    return $table;
}


function wrap_list_group_items($string, $is_current = 0)
{
    $is_active = ($is_current == 1) ? " active" : "";
    $list_group_item = "<li class='display-release-data-indent list-group-item list-group-item-action$is_active'>$string</li>";
    return $list_group_item;
}



function wrap_accordian_rows($header, $data, $open = 0)
{
    $header_no_spaces = str_replace(' ', '', $header);
    $accordian = '<!-- START ' . $header . ' -->'
        . '<div class="accordion-item">'
        . '<h2 class="accordion-header font-weight-bold" id="heading'
        . $header_no_spaces
        . '">'
        . '<button class="accordion-button';

    if ($open):
        $accordian = $accordian . '';
    else:
        $accordian = $accordian . ' collapsed';
    endif;

    $accordian = $accordian
        . '" type="button" data-bs-toggle="collapse" data-bs-target="#collapse'
        . $header_no_spaces
        . '" aria-expanded="false" aria-controls="collapse'
        . $header_no_spaces
        . '"><strong>'
        . $header
        . '</strong></button></h2>'
        . '<div id="collapse'
        . $header_no_spaces
        . '" class="accordion-collapse collapse';

    if ($open):
        $accordian = $accordian . ' show';
    endif;

    $accordian = $accordian
        . '" aria-labelledby="heading'
        . $header_no_spaces
        . '">'
        . '<div class="accordion-body">';

    $accordian = $accordian . $data;

    $accordian = $accordian
        . ' </div>'
        . '</div>'
        . '</div>'
        . '<!-- END ' . $header . ' -->';

    return $accordian;
}


function get_image_url($release_id, $download_images = null)
{
    global $LOCAL_RELEASES_DATA_ROOT, $IMAGE_PATH_ROOT, $IMAGE_PATH_ROOT_URL, $DOWNLOAD_IMAGES;

    $image_url = "/img/no-album-art.png";
    $should_download = ($DOWNLOAD_IMAGES == 1 || $download_images == 1);

    // Step 0: If downloading is enabled, check if local image already exists
    if ($should_download) {
        $known_extensions = ['jpg', 'jpeg', 'png', 'webp'];
        foreach ($known_extensions as $ext) {
            $local_path = $IMAGE_PATH_ROOT. $release_id . '.' . $ext;
            $image_url = $IMAGE_PATH_ROOT_URL . $release_id . '.' . $ext;
            if (file_exists($local_path)) {
                add_debug("Local image already exists: $local_path (skipping JSON checks)");
                return $image_url;
            }
        }
    }

    $have_final_image = 0;

    // Step 1: Try Cover Art Archive
    $coverart_path = $LOCAL_RELEASES_DATA_ROOT . $release_id . "_coverartarchive.json";
    if (file_exists($coverart_path)) {
        $cover_data = json_decode(file_get_contents($coverart_path), true);
        $image_url = $cover_data['images'][0]['image'] ?? '';
        if (filter_var($image_url, FILTER_VALIDATE_URL)) {
            add_debug("$image_url is Valid URL (CoverArt Archive)");
            $have_final_image = 1;
        } else {
            add_debug("$image_url is NOT Valid URL (CoverArtArchive)");
        }
    } else {
        add_debug("$coverart_path does NOT exist");
    }

    // Step 2: Fallback to Discogs-style local JSON
    $release_path = $LOCAL_RELEASES_DATA_ROOT . $release_id . ".json";
    if (!$have_final_image && file_exists($release_path)) {
        $release_data = json_decode(file_get_contents($release_path), true);
        $image_url = $release_data['images'][0]['uri'] ?? '';
        if (filter_var($image_url, FILTER_VALIDATE_URL)) {
            add_debug("$image_url is Valid URL (Release JSON)");
            $have_final_image = 1;
        } else {
            add_debug("$image_url is NOT Valid URL (Release JSON)");
        }
    } elseif (!$have_final_image) {
        add_debug("$release_path does NOT exist");
    }

    // Step 3: If image is found and downloading is allowed, download it
    if ($have_final_image && $should_download) {
        $ext = pathinfo(parse_url($image_url, PHP_URL_PATH), PATHINFO_EXTENSION);
        if (!$ext) $ext = 'jpg';

        $local_image_path = $IMAGE_PATH_ROOT . $release_id . '.' . $ext;

        add_debug("Downloading image to: $local_image_path");
        $image_data = file_get_contents($image_url);
        if ($image_data !== false) {
            file_put_contents($local_image_path, $image_data);
            return $local_image_path;
        } else {
            add_debug("Failed to download image from $image_url");
        }
    }

    // Step 4: Return remote image URL or empty
    return $image_url;
}




function save_meta_file($meta_file, $image_url)
{
    $meta_data = ['image_url' => $image_url];
    file_put_contents($meta_file, json_encode($meta_data, JSON_PRETTY_PRINT));
    Debug::log("Saved image URL to _meta.json: " . $meta_file);
}



/*
function get_valid_cover_image($release_id)
{
    $releaseinfo = get_release_information($release_id);
    $cover_image_discogs_source_url = $releaseinfo['cover_image'] ?? '';
    Debug::log("Master ID: " . ($release['basic_information']['master_id'] ?? 'No Master ID'));

    // Initialize variables
    $release_group_id = '';
    $cover_image_musicbrainz_source_url = '';

    // Fetch MusicBrainz release group ID if master_id exists
    if (!empty($release['basic_information']['master_id'])) {
        $release_group_data = get_musicbrainz_master_release_group($releaseinfo['master_id']);

        if ($release_group_data && !empty($release_group_data['release_group_id'])) {
            $release_group_id = $release_group_data['release_group_id'];
            Debug::log("Release Group ID: " . $release_group_id);

            $cover_image_musicbrainz_source_url = "https://coverartarchive.org/release-group/$release_group_id/front";
        }
    }

    // Try MusicBrainz first
    if (!empty($cover_image_musicbrainz_source_url)) {
        Debug::log("Trying MusicBrainz: " . $cover_image_musicbrainz_source_url);

        if (is_valid_image_url($cover_image_musicbrainz_source_url, true)) {
            Debug::log("✅ Using MusicBrainz Image: " . $cover_image_musicbrainz_source_url);
            return [$cover_image_musicbrainz_source_url, "MusicBrainz"];
        }
    }

    // If MusicBrainz fails, try Discogs
    if (!empty($cover_image_discogs_source_url) && is_valid_image_url($cover_image_discogs_source_url, false)) {
        Debug::log("⚠️ MusicBrainz failed, using Discogs.");
        return [$cover_image_discogs_source_url, "Discogs"];
    }

    return [null, "None"]; // No valid image found
} */



function is_valid_image_url($url, $allow_redirects = false)
{
    if (empty($url)) {
        return false;
    }

    // Get headers from URL
    $headers = @get_headers($url, 1);
    if (!$headers) {
        Debug::log("❌ No headers found for URL: $url");
        return false;
    }

    Debug::log("Headers for $url: " . print_r($headers, true));

    // Check if response contains 200 OK
    if (isset($headers[0]) && str_contains(strtolower($headers[0]), '200 ok')) {
        Debug::log("✅ Found valid image for URL: $url");
        return true;
    }

    // Handle redirects (MusicBrainz)
    if ($allow_redirects && isset($headers['Location'])) {
        $final_url = is_array($headers['Location']) ? end($headers['Location']) : $headers['Location'];
        Debug::log("🔄 Following redirect to: $final_url");

        // Recursively check if final URL is valid
        return is_valid_image_url($final_url, false);
    }

    Debug::log("❌ Image not found at URL: $url");
    return false;
}







function css_is_hidden($page_type = "all")
{
    global $PARAMS;
    $displayStatus = "d-none";

    if ($page_type == "all"):
        $page_type = $PARAMS['type'];
    endif;

    if ($page_type == "single" && $PARAMS['release_id']):
        $displayStatus = "";
    endif;

    if ($PARAMS['type'] == $page_type):
        $displayStatus = "";
    endif;

    return $displayStatus;
}

// function make_fontawesome($icon_name = "font-awesome", $textColor = "white", $primaryColor = 'primary', $secondaryColor = 'black', )
// {
//     $icon_name_lower = strtolower($icon_name); // Ensure input is lowercase
//     $output_badge = '';
//     // Map certain names to specific FontAwesome icons
//     // Badge logic based on icon_name_lower
//     if (in_array($icon_name_lower, ["12", "10", "7", "lp", "ep", "single", "mp3", "flac"])) {
//         $output_badge = "<span class=\"badge badge-pill format-badge-secondary text-$textColor bg-$secondaryColor\">$icon_name</span>";
//     } elseif (in_array($icon_name_lower, ["cd", "vinyl", "lathe", "cass", "file"])) {
//         $output_badge = "<span class=\"badge badge-pill format-badge-primary text-$textColor bg-$primaryColor\">$icon_name</span>";
//     } elseif ($icon_name_lower === "new") {
//         $output_badge = "<span class=\"badge badge-pill format-badge-primary text-$textColor bg-success\">NEW</span>";
//     } else {
//         $output_badge = "<span class=\"badge badge-pill format-badge-primary text-$textColor bg-$secondaryColor\">$icon_name</span>";
//     }

//     // Optional icon output (only relevant for specific cases)

//     return $output_badge;
// }

function make_badge($badgeText = "none", $backgroundColor = "bg-accent_color", $textColor = "text-muted")
{
    $badgeText_lower = strtolower($badgeText); // Ensure input is lowercase
    $output_badge = '';
    // Map certain names to specific FontAwesome icons
    // Badge logic based on icon_name_lower
    if (in_array($badgeText_lower, ["12", "10", "7", "lp", "ep", "single", "mp3", "flac"])) {
        $backgroundColor = "bg-black";
    } elseif (in_array($badgeText_lower, ["cd", "vinyl", "lathe", "cass", "file"])) {
    } elseif ($badgeText_lower === "new") {
        $textColor = "fg-limegreen";
        $backgroundColor = "";
        $badgeText = '<i class="fa-solid fa-certificate"></i>';
    } else {
        $textColor = "text-muted";
        $backgroundColor = "bg-black";
    }

    $output_badge = "<span class=\"badge badge-pill format-badge-primary $textColor $backgroundColor\">$badgeText</span>";
    // Optional icon output (only relevant for specific cases)

    return $output_badge;
}



function display_gallery_item($release)
{
    global $IMAGE_PATH_ROOT_URL_PREFIX_200;
    global $IMAGE_PATH_ROOT_URL_SUFFIX;
    global $PARAMS, $IS_WANTLIST;

    $artists = implode(", ", array_column($release['basic_information']['artists'], "name"));
    $title = $release['basic_information']['title'];
    $release_id = $release['basic_information']['id'];
    $primaryFormat = $release['basic_information']['formats'][0]['name'];
    $adddate = date('m/d/y', strtotime(substr($release['date_added'], 0, 10)));
    $allFormats = $primaryFormat;
    $number_of_formats = sizeof($release['basic_information']['formats']);
    $ebaySearchURL = "https://www.ebay.com/sch/i.html?_nkw=";

    // Initialize an array to collect all descriptions
    $formatName = '';
    $secondaryFormatBadge = '';
    for ($i = 0; $i < $number_of_formats; $i++) {
        $formatDescriptions = [];
        $formatName = '';

        // Check if the 'descriptions' key exists for the current format
        $formatKeywords = ['Vinyl', 'Lathe', 'Cass', 'LP', 'CD', 'EP', '12', '10', '7', "File", "Flac", "MP3"];

        $secondaryFormatBadge = ''; // Initialize badge string

        if (!empty($release['basic_information']['formats'])) {
            foreach ($release['basic_information']['formats'] as $format) {
                // Extract format name and descriptions
                $formatName = $format['name'] ?? '';
                $formatDescriptions = $format['descriptions'] ?? [];
                $allFormats = $formatName . (!empty($formatDescriptions) ? ', ' . implode(', ', $formatDescriptions) : '');

                // Add badges for matching formats
                foreach ($formatKeywords as $keyword) {
                    if (str_contains($allFormats, $keyword)) {
                        $secondaryFormatBadge .= make_badge($keyword);
                    }
                }
            }

        }
    }

    //if ($IS_WANTLIST) :
        //$image_url = $release['basic_information']['cover_image'];
    //else:
        $image_url = $IMAGE_PATH_ROOT_URL_PREFIX_200 . get_image_url($release_id);
    //endif;

    $is_new_class = 'border-0';
    $is_new_badge = '';
    if (strtotime($adddate) > strtotime('-14 days')):
        $is_new_class = ' border-0';
        $is_new_badge = make_badge("New", "text-warning");
    endif;


    $footer_text = make_badge($adddate, "bg-white", "text-dark");

    $release_urlParams = [
        'release_id' => $release_id,
        'username' => '',
        'type' => '',
        'sort_by' => '',
        'page' => '',
        'order' => '',
        'per_page' => '',
        'artist_id' => '0'
    ];
    $release_url = build_url("/", set_params($PARAMS, $release_urlParams));
    $discogs_url = "https://www.discogs.com/release/$release_id";

    if ($PARAMS['sort_by'] == 'title') {
        $first_line_text = $title;
        $second_line_text = $artists;
    } else {
        $first_line_text = $artists;
        $second_line_text = $title;
    }



    ?> 
    <!-- Gallery item -->               
    <div class="releaseGalleryItem">
        <div class="card bg-dark text-white <?php echo $is_new_class; ?>">
        
        <a href="<?php if ($IS_WANTLIST) {
            echo $discogs_url;
        } else {
            echo $release_url;
        } ?>"  title="<?php echo $second_line_text; ?> by <?php echo $first_line_text; ?>" class="stretched-link">
                <img loading="lazy" src="<?php echo $image_url . $IMAGE_PATH_ROOT_URL_SUFFIX; ?>" class="card-img img-fluid img-thumbnail" alt="<?php echo $second_line_text; ?> by <?php echo $first_line_text; ?>">
            
                <div class="card-img-overlay blurtop">
            
                    <h5 class="card-title"><?php echo $first_line_text; ?></h5>
                    <h5 class="card-subtitle"><?php echo $second_line_text; ?></h5>
                </div>
        </a>
                <div class="card-body my-0 py-1 second">
                <?php if ($IS_WANTLIST) {
                    echo "<a class=\"btn btn-primary btn-xs\" href=\"$ebaySearchURL+$first_line_text+$second_line_text+$primaryFormat\" title=\"Search for release on eBay\">eBay</a>";
                } ?>
                </div>

        <div class="card-footer second"><span class="float-start"><small><?php echo $footer_text; ?>     <?php echo $is_new_badge; ?></small></span> <span class="float-end"><small><?php echo $secondaryFormatBadge; ?></small></span></div>
        </div>
    </div>
    <!-- End gallery item -->
            <?php } // End display_gallery_item() 



function render_stars($rating, $max_stars = 5) {
    $full_stars = floor($rating);
    $half_star = ($rating - $full_stars) >= 0.25 && ($rating - $full_stars) < 0.75;
    $empty_stars = $max_stars - $full_stars - ($half_star ? 1 : 0);

    $stars_html = '';

    // Full stars
    for ($i = 0; $i < $full_stars; $i++) {
        $stars_html .= '<i class="small fa fa-fw fa-solid fa-star text-warning"></i>';
    }

    // Half star
    if ($half_star) {
        $stars_html .= '<i class="small fa fa-fw fa-solid fa-star-half-stroke text-warning"></i>';
    }

    // Empty stars
    for ($i = 0; $i < $empty_stars; $i++) {
        $stars_html .= '<i class="small fa fa-fw fa-regular fa-star text-warning"></i>';
    }

    return $stars_html;
}


function display_release_data($releaseinfo)
{
    global $PARAMS, $SOCIALS, $IMAGE_PATH_ROOT_URL;

    $id = $releaseinfo['id'];
    $title = $releaseinfo['title'];
    $artists_linked = $releaseinfo['artists_linked'];
    $labelnames = $releaseinfo['label_names'];
    $formats = $releaseinfo['formats'];
    $genres = $releaseinfo['genres'];
    $styles = $releaseinfo['styles'];
    $year = $releaseinfo['year'];
    $rating = $releaseinfo['rating'];
    $myrating = $releaseinfo['myrating'];

    //$images = $releaseinfo['images'];
    $releasenotes = $releaseinfo['releasenotes'];
    $tracklist = $releaseinfo['tracklist_rows'];
    $identifiers = $releaseinfo['identifier_rows'];
    $companies = $releaseinfo['companies_rows'];
    $extra_artists_rows = $releaseinfo['extra_artists_rows'];
    $my_release_notes_rows = $releaseinfo['my_release_notes_rows'];
    $my_release_notes = $releaseinfo['my_release_notes'];
    $master_release_url = $releaseinfo['master_uri'] ?? "";
    $master_year = $releaseinfo['master_release_year'] ?? "";
    $display_master_year = ($year === $master_year) || is_numeric($year) && !is_numeric($master_year) ? 0 : 1;
    $master_id = $releaseinfo['master_id'] ?? "";
    $artists_plain = $releaseinfo['artists_plain'];
    $musicbrainzid = $releaseinfo['musicbrainzid'] ?? "";
    $mb_release_group = $releaseinfo['master_musicbrainz_release_group_id'] ?? "";
    $resource_url = "https://api.discogs.com/releases/$id";

    ?>

        <div class="col-md-4 col-sm-6 my-3">
        <div class="accordion" id="accordionExample">
      <div class="accordion-item">
        <h2 class="accordion-header font-weight-bold" id="headingReleaseData">
          <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseReleaseData" aria-expanded="true" aria-controls="collapseReleaseData">
          <strong>Release Data</strong>
          </button>
        </h2>
        <div id="collapseReleaseData" class="accordion-collapse collapse show" aria-labelledby="headingReleaseData" data-bs-parent="#accordionExample">
          <div class="card accordion-body">


      <ul class="list-group cardReleaseData">
      <li class="card-header list-group-item"><i class="fa fa-fw fa-quote-left"></i> Title</li>
      <?php echo wrap_list_group_items($title); ?>
      </ul>


      <ul class="list-group cardReleaseData">
      <li class="card-header list-group-item"><i class="fa fa-fw fa-person-half-dress"></i> Artist(s)</li>
      <?php echo $artists_linked; ?>
      </ul>

      <ul class="list-group cardReleaseData">
      <li class="card-header list-group-item"><i class="fa fa-fw fa-calendar"></i> Released</li>
      <?php echo wrap_list_group_items($year);
      if ($display_master_year)
          echo wrap_list_group_items($master_year . " (OG Pressing)"); ?>
      </ul>

      <ul class="list-group cardReleaseData">
        <li class="card-header list-group-item"><i class="fa fa-fw fa-building"></i> Rating(s)</li>
        <div data-coreui-toggle="rating" data-coreui-value="3"></div>
        <?php if ($rating)
            echo wrap_list_group_items(render_stars($rating) . " (Community)") ?>

        <?php if ($myrating)
            echo wrap_list_group_items(render_stars($myrating) . " (Mine)") ?>
      </ul>


      <?php if (!empty($series)): ?>
            <ul class="list-group cardReleaseData">
          <li class="card-header list-group-item"><i class="fa fa-fw fa-chart-simple"></i> Series</li>
          <?php if ($series)
              echo wrap_list_group_items($series); ?>
          </ul>
      <?php endif; ?>

      <ul class="list-group cardReleaseData">
        <li class="card-header list-group-item"><i class="fa fa-fw fa-building"></i> Label(s)</li>
        <?php if ($labelnames)
            echo $labelnames; ?>
      </ul>
 

      <ul class="list-group cardReleaseData">
        <li class="card-header list-group-item"><i class="fa fa-fw fa-record-vinyl"></i> Format(s)</li>
      <?php echo $formats; ?>
      </ul>
  

      <ul class="list-group cardReleaseData">
      <li class="card-header list-group-item"><i class="fa fa-fw fa-tag"></i> Genre(s)</li>
      <?php echo wrap_list_group_items($genres); ?>
      </ul>


      <ul class="list-group cardReleaseData">
      <li class="card-header list-group-item"><i class="fa fa-fw fa-tags"></i> Style(s)</li>
      <?php if ($styles)
          echo wrap_list_group_items($styles); ?>
      </ul>

      <ul class="list-group cardReleaseData">
      <li class="card-header list-group-item"><i class="fa fa-fw fa-tags"></i> Release Note(s)</li>
      <?php if ($my_release_notes_rows)
          echo $my_release_notes_rows; ?>
      </ul>

      <ul class="list-group cardReleaseData">
      <li class="card-header list-group-item"><i class="text-musicbrainz fa fa-fw fa-brain"></i> MusicBrainz ID</li>
      <?php if ($musicbrainzid)
          echo wrap_list_group_items($musicbrainzid); ?>

      <li class="card-header list-group-item"><i class="text-musicbrainz fa fa-fw fa-brain"></i> MusicBrainz Group ID</li>
      <?php if ($mb_release_group)
          echo wrap_list_group_items($mb_release_group); ?>

      </ul>

      <ul class="list-group cardReleaseData">
      <li class="card-header list-group-item"><i class="text-master fa fa-fw fa-crown"></i> Master</li>
      <?php if ($master_id): ?>
          <a class='display-release-data-indent list-group-item list-group-item-action' href='<?php echo $master_release_url; ?>'>ID #<?php echo $master_id; ?> <i class='fa fa-fw fa-arrow-up-right-from-square'></i></a>
      <?php endif; ?>
         </li>
      </ul>

      <div class="card list-group cardReleaseData">
      <div class="card-header">
      <i class="fa fa-fw fa-link"></i> Outbound Links
      </div>
      <div class="card-body ">
    <div class="related-sites">
        <div class="d-grid gap-2 d-md-block">
        <?php display_related_sites(); ?>

                                    <a class="btn btn-sm m-1 border-0 btn-primary" href="https://www.discogs.com/release/<?php echo $id ?>" title="View on discogs.com"><span class="pe-1 border-1 border-mute border-end"><i class="text-black fa fa-fw fa-record-vinyl"></i></span> Discogs</a>
                                    <?php if ($PARAMS['debug']) { ?><a class="btn btn-sm m-1 border-0 btn-primary" href="<?php echo $resource_url; ?>" title="View JSON data from discogs.com API"><span class="pe-1 border-1 border-mute border-end"><i class="fa fa-fw fa-code"></i></span> json</a><?php } ?>
                                    <?php if ($PARAMS['debug']) { ?><a class="btn btn-sm m-1 border-0 btn-primary bg-success" href="https://musicbrainz.org/ws/2/url?resource=https://www.discogs.com/master/<?php echo $master_id; ?>" title="View MusicBrainz ID"><span class="pe-1 border-1 border-mute border-end"><i class="fa fa-fw fa-code"></i></span> MBID</a> <?php } ?>
                                    <?php if ($SOCIALS['lastfm']) { ?><a class="btn btn-sm m-1 border-0 bg-danger" href="https://openscrobbler.com/scrobble/album/view/dsid/release-<?php echo $id; ?>" title="Scrobble via last.fm"><span class="pe-1 border-1 border-mute border-end"><i class="fa fa-fw fa-lastfm"></i></span> LastFm</a><?php } ?>
                                    <?php if ($SOCIALS['bluesky']) { ?><a class="btn btn-sm m-1 border-0 bg-info" href="https://bsky.app/intent/compose?text=%23nowspinning%0D%0A%0D%0A<?php echo $title; ?> by <?php echo $artists_plain; ?> %0D%0A%0D%0A%23vinylsky %23vinylrecords %23vinylcommunity #vinylcollection" target="_blank" rel="noopener noreferrer" title="Send skeet to BlueSky"><span class="pe-1 border-1 border-mute border-end"><i class="fa fa-fw fa-brands fa-bluesky"></i></span> BlueSky</a><?php } ?>
        </div>
                                    </div>
                                </div>  
      </div>
      </div>
      </div>
    </div>
                        </div>
      </div>





                    <div class="col-sm-6 col-md-8 my-3">
                        <div class="bg-white rounded shadow-sm">

                            <div class="accordion" id="accordionExample">

                                <?php echo wrap_accordian_rows('Track List', wrap_table_rows('', $tracklist), 'opened'); ?>
                                <?php echo wrap_accordian_rows('Release Notes', '<pre>' . $releasenotes . '</pre>', 'opened'); ?>
                                <?php echo wrap_accordian_rows('My Release Notes', $my_release_notes, 'opened'); ?>
                                <?php echo wrap_accordian_rows('Credits', wrap_listgroup_items('Credits', $extra_artists_rows)); ?>
                                <?php echo wrap_accordian_rows('Companies', wrap_listgroup_items('Companies', $companies)); ?>
                                <?php echo wrap_accordian_rows('Identifiers', wrap_listgroup_items('Identifiers', $identifiers)); ?>

                            </div>

                        </div>
                    </div>



            <?php  #echo $releasedata; 
}







// Handle form submission


/**
 * Updated createAlbumArtGridImage function
 */
function createAlbumArtGridImage($collection, $numberOfAlbums, $gridSize = '3x3', $outputFile = 'album_art_grid.png')
{
    global $IMAGE_PATH_ROOT_URL; // Use the global variable if needed in the script

    // Parse grid size
    list($rows, $cols) = explode('x', $gridSize);
    $rows = (int) $rows;
    $cols = (int) $cols;
    $maxGrid = $rows * $cols;

    // Limit the number of albums to the smaller value (max grid size or requested albums)
    $albumsToShow = min($numberOfAlbums, $maxGrid);

    // Fetch the last x albums from the collection
    $lastAlbums = array_slice(array_reverse($collection), 0, $albumsToShow);

    // Image dimensions
    $cellSize = 200; // Each album art will be 200x200 pixels
    $gridWidth = $cols * $cellSize;
    $gridHeight = $rows * $cellSize;

    // Create the blank grid canvas
    $gridImage = imagecreatetruecolor($gridWidth, $gridHeight);
    $backgroundColor = imagecolorallocate($gridImage, 255, 255, 255); // White background
    imagefill($gridImage, 0, 0, $backgroundColor);

    // Fetch and place each album art in the grid
    $x = 0;
    $y = 0;

    foreach ($lastAlbums as $release) {
        $release_id = $release['basic_information']['id'];
        // Use get_image_url() to retrieve the image URL
        $imageUrl = get_image_url($release_id);

        // Download the image from the provided URL
        $albumArt = @imagecreatefromjpeg($imageUrl);

        if ($albumArt) {
            // Resize the image to fit the cell size
            $resizedArt = imagecreatetruecolor($cellSize, $cellSize);
            imagecopyresampled(
                $resizedArt,
                $albumArt,
                0,
                0,
                0,
                0,
                $cellSize,
                $cellSize,
                imagesx($albumArt),
                imagesy($albumArt)
            );
            imagedestroy($albumArt);

            // Place the resized album art on the grid
            imagecopy($gridImage, $resizedArt, $x * $cellSize, $y * $cellSize, 0, 0, $cellSize, $cellSize);
            imagedestroy($resizedArt);
        } else {
            // If the image fails to load, use a placeholder
            $placeholder = imagecreate($cellSize, $cellSize);
            $placeholderBg = imagecolorallocate($placeholder, 200, 200, 200); // Light grey
            imagefill($placeholder, 0, 0, $placeholderBg);
            $textColor = imagecolorallocate($placeholder, 0, 0, 0); // Black text
            imagestring($placeholder, 5, 10, 10, "No Art", $textColor);

            // Place the placeholder in the grid
            imagecopy($gridImage, $placeholder, $x * $cellSize, $y * $cellSize, 0, 0, $cellSize, $cellSize);
            imagedestroy($placeholder);
        }

        // Move to the next cell
        $x++;
        if ($x >= $cols) {
            $x = 0;
            $y++;
        }

        // Stop if we've filled the grid
        if ($y >= $rows) {
            break;
        }
    }

    // Save the combined grid as an image file (overwrites if the file exists)
    imagepng($gridImage, $outputFile);
    imagedestroy($gridImage);

    return $outputFile; // Return the file path
}


class SimpleBSON
{
    // Encode PHP array (JSON) to BSON
    public static function encode($data)
    {
        return pack('V', strlen(json_encode($data)) + 4 + 1) . json_encode($data) . "\x00";
    }

    // Decode BSON back to PHP array (JSON)
    public static function decode($bson)
    {
        $length = unpack('V', substr($bson, 0, 4))[1];
        $jsonString = substr($bson, 4, $length - 5); // Remove null terminator
        return json_decode($jsonString, true);
    }

    // Save multiple JSON files as a single BSON file
    public static function saveJsonToBson($bsonFile, $jsonFiles)
    {
        $bsonData = '';

        foreach ($jsonFiles as $jsonFile) {
            if (file_exists($jsonFile)) {
                $jsonData = json_decode(file_get_contents($jsonFile), true);
                $bsonData .= self::encode($jsonData);
            }
        }

        file_put_contents($bsonFile, $bsonData);
    }

    // Read BSON file and return an array of JSON objects
    public static function readBsonFile($bsonFile)
    {
        $bsonData = file_get_contents($bsonFile);
        $documents = [];
        $offset = 0;

        while ($offset < strlen($bsonData)) {
            $length = unpack('V', substr($bsonData, $offset, 4))[1];
            $bsonChunk = substr($bsonData, $offset, $length);
            $documents[] = self::decode($bsonChunk);
            $offset += $length;
        }

        return $documents;
    }
}

// Example Usage: Convert multiple JSON files to a single BSON file
#$jsonFiles = ['collection1.json', 'collection2.json', 'collection3.json'];
#SimpleBSON::saveJsonToBson('collection.bson', $jsonFiles);

// Example Usage: Read BSON file back into an array
#$data = SimpleBSON::readBsonFile('collection.bson');
#print_r($data);
