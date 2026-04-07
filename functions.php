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

// Load statistics functions only when the stats page is actually requested.
// This keeps chart code and its large JS dependencies off every other page.
if (!empty($_GET['type']) && $_GET['type'] === 'statistics') {
    require_once(__DIR__ . '/functions_statistics.php');
}


/* ******************************************************** */
/* The following should not be changed on a per-site basis  */
/* ******************************************************** */

// DEFAULT VALUES FOR PARAMETERS

$PARAMS = array(

    "username"  => $DISCOGS_USERNAME,
    "type"      => $_GET['type']       ?? "releases",
    "folder_id" => $_GET['folder_id']  ?? '0',
    "release_id"=> $_GET['release_id'] ?? "",
    "artist_id" => $_GET['artist_id']  ?? "",
    "sort_by"   => $_GET['sort_by']    ?? "added",
    "order"     => $_GET['order']      ?? "desc",
    "per_page"  => $_GET['per_page']   ?? "24",
    "page"      => $_GET['page']       ?? "1",
    "debug"     => $_GET['debug']      ?? "",
	"format_filter" => $_GET['format_filter'] ?? "",

);


/* ******************************************************** */
/*                  DISCOGS API VARIABLES                   */
/* ******************************************************** */

$DISCOGS_API_URL        = "https://api.discogs.com/";
$DISCOGS_API_URL_USERS  = $DISCOGS_API_URL . "users/";
$DEBUG_MSG              = '';
$MAX_AGE_IN_HOURS       = 8;
$MAX_AGE_IN_SECONDS     = $MAX_AGE_IN_HOURS * 60 * 60;

$IS_STATISTICS    = ($PARAMS['type'] == 'statistics') ? 1 : 0;
$IS_WANTLIST      = ($PARAMS['type'] == 'wants')      ? 1 : 0;
$IS_RELEASES      = ($PARAMS['type'] == 'releases')   ? 1 : 0;
$IS_SINGLE        = ($PARAMS['release_id'])            ? 1 : 0;
$IS_RELEASE_GALLERY = (!$IS_SINGLE)                   ? 1 : 0;

$collection_value = "";

$LOCAL_RELEASES_DATA_ROOT = $LOCAL_DATA_PATH_ROOT . "releases/";

$LOCAL_RELEASE_DATA_FILE             = $LOCAL_RELEASES_DATA_ROOT . $PARAMS['release_id'] . '.json';
$LOCAL_RELEASE_MASTER_DATA_FILE      = $LOCAL_RELEASES_DATA_ROOT . $PARAMS['release_id'] . '_master.json';
$LOCAL_RELEASE_MUSICBRAINZ_FILE      = $LOCAL_RELEASES_DATA_ROOT . $PARAMS['release_id'] . '_musicbrainz.json';
$LOCAL_RELEASE_COVERARTARCHIVE_FILE  = $LOCAL_RELEASES_DATA_ROOT . $PARAMS['release_id'] . '_coverartarchive.json';

$LOCAL_USER_DATA_ROOT          = $LOCAL_DATA_PATH_ROOT . $PARAMS['username'] . "/";
$LOCAL_USER_RELEASES_DATA_ROOT = $LOCAL_USER_DATA_ROOT . "releases/";
$LOCAL_USER_MYRELEASE_DATA_FILE = $LOCAL_USER_RELEASES_DATA_ROOT . $PARAMS['release_id'] . '_mine.json';

$DATA_FILES = array(
    "releases"          => $LOCAL_USER_DATA_ROOT . "collection_data.json",
    "value"             => $LOCAL_USER_DATA_ROOT . "collection_value.json",
    "fields"            => $LOCAL_USER_DATA_ROOT . "collection_fields.json",
    "folders"           => $LOCAL_USER_DATA_ROOT . "folder_data.json",
    "wants"             => $LOCAL_USER_DATA_ROOT . "wantlist_data.json",
    "statistics"        => $LOCAL_USER_DATA_ROOT . "statistics.json",
    "statistics_summary"=> $LOCAL_USER_DATA_ROOT . "statistics_summary.json",
    "profile"           => $LOCAL_USER_DATA_ROOT . "user_profile.json",
    "lists"             => $LOCAL_USER_DATA_ROOT . "user_lists.json",
    "release"           => $LOCAL_RELEASE_DATA_FILE,
    "musicbrainz"       => $LOCAL_RELEASE_MUSICBRAINZ_FILE,
    "coverart"          => $LOCAL_RELEASE_COVERARTARCHIVE_FILE,
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
            echo " <a href=\"" . htmlspecialchars($url) . "\" target=\"_blank\" rel=\"noopener noreferrer\">"
               . "<i class=\"fa fa-fw $faIcon mx-2\"></i></a>";
        }
    }
}



if (file_exists($DATA_FILES['releases'])) {
    $age_of_collection_file = (time() - filemtime($DATA_FILES['releases']));
}


$options  = array('http' => array('user_agent' => 'DiscogsCollectionPage'));
$CONTEXT  = stream_context_create($options);
$options2 = array('http' => array('user_agent' => "User-Agent: MyMusicApp/1.0 (your@email.com)\r\n"));
$CONTEXT2 = stream_context_create($options2);
$user_bio = "This collection belongs to " . $PARAMS['username'] . ". There are many just like it, but this one is his.";

// GET FOLDER DATA FOR NAVIGATION BAR
$folderdata = file_get_contents($DATA_FILES['folders']);
$folders    = json_decode($folderdata, true);

foreach ($folders['folders'] as $folder) {
    if ($folder['id'] == $PARAMS['folder_id']) {
        $current_folder_name  = $folder['name'];
        $current_folder_count = $folder['count'];
    }
    if ($folder['id'] == 0) {
        $total_collection_count = $folder['count'];
    }
}

$UserProfileData = file_get_contents($DATA_FILES['profile']);
$userProfile     = json_decode($UserProfileData, true);


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
    $filteredParams = array_filter($PARAMS, function ($value) {
        return !is_null($value) && $value !== '' && $value !== '0';
    });
    $queryString = http_build_query($filteredParams);
    return $baseUrl . ($queryString ? '?' . $queryString : '');
}


function set_param($PARAMS, $key, $value)
{
    $PARAMS[$key] = $value;
    return $PARAMS;
}


function set_params($PARAMS, $newParams = [])
{
    $PARAMS = array_merge($PARAMS, $newParams);
    return $PARAMS;
}


function isValidFetchedData($data, $key = null)
{
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
    $fileContent  = file_get_contents($file);
    $decodedData  = json_decode($fileContent, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($decodedData[$key])) {
        return false;
    }
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


function fetchCollectionData($username, $token, $data_file)
{
    global $PARAMS;
    global $DISCOGS_API_URL_USERS, $DATA_FILES, $CONTEXT, $MAX_AGE_IN_SECONDS;

    $collection_file        = $data_file;
    $age_of_collection_file = get_age_of($data_file);

    if (isValidJsonFile($collection_file, 'releases') && $age_of_collection_file < $MAX_AGE_IN_SECONDS) {
        Debug::log("Collection data is up-to-date. $age_of_collection_file secs old.");
        return;
    }

    $apiUrl      = $DISCOGS_API_URL_USERS . $username . '/collection/folders/0/releases';
    $page        = 1;
    $perPage     = 500;
    $hasMorePages = true;
    $allReleases = [];

    while ($hasMorePages) {
        $url      = $apiUrl . '?page=' . $page . '&per_page=' . $perPage . '&token=' . $token;
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

    if (isValidFetchedData(['releases' => $allReleases], 'releases')) {
        $fileData = ['releases' => $allReleases];
        file_put_contents($collection_file, json_encode($fileData, JSON_PRETTY_PRINT));
        Debug::log("Collection data updated and saved to $collection_file.");
    } else {
        Debug::log("Invalid data fetched, skipping save for collection data.");
    }
}


function fetchUserProfileData($username)
{
    global $DISCOGS_API_URL_USERS, $DATA_FILES;
    $url = $DISCOGS_API_URL_USERS . $username;
    check_data_file($DATA_FILES['profile'], rand(120, 168), $url);
}


function fetchMusicbrainzReleaseData($master_id, $data_file_musicbrainz, $data_file_coverartarchive)
{
    Debug::log("Fetching MB Release Data");
    Debug::log("Master ID: $master_id");

    if (is_numeric($master_id)):
        $release_group_id = get_musicbrainz_master_release_group($master_id);
    else:
        Debug::log("No master id");
        return;
    endif;

    if ($release_group_id):
        $url_mb_rs  = "https://musicbrainz.org/ws/2/release-group/$release_group_id?inc=url-rels&fmt=json";
        $url_bm_rg  = "https://musicbrainz.org/ws/2/url?inc=release-group-rels&resource=https://www.discogs.com/master/"
            . $master_id . "&fmt=json";
        $urls_mb    = ["related_sites" => $url_mb_rs, "release_group" => $url_bm_rg];
        Debug::log("MB Data_file: $data_file_musicbrainz");
        check_data_file($data_file_musicbrainz, rand(110, 128), $urls_mb);

        $url_caa = "https://coverartarchive.org/release-group/$release_group_id";
        Debug::log("CAA Data_file: $data_file_coverartarchive");
        Debug::log("CAA URL: $url_caa");
        check_data_file($data_file_coverartarchive, 118, $url_caa);
    endif;
}


function fetchCustomFieldsData($username, $token)
{
    global $DISCOGS_API_URL_USERS, $DATA_FILES;
    $url = $DISCOGS_API_URL_USERS . "$username/collection/fields?token=$token";
    check_data_file($DATA_FILES['fields'], 48, $url);
}


function fetchCoverArtArchiveData($username, $token)
{
    global $DISCOGS_API_URL_USERS, $DATA_FILES;
    $url = $DISCOGS_API_URL_USERS . "$username/collection/fields?token=$token";
    check_data_file($DATA_FILES['fields'], 24, $url);
}


function fetchUserListsData($username, $token)
{
    global $DISCOGS_API_URL_USERS, $DATA_FILES;
    $url = $DISCOGS_API_URL_USERS . "$username/lists?token=$token";
    check_data_file($DATA_FILES['lists'], 48, $url);
}


function fetchFolderData($username, $token, $data_file)
{
    global $DISCOGS_API_URL_USERS;
    $url = $DISCOGS_API_URL_USERS . $username . '/collection/folders?token=' . $token;
    check_data_file($data_file, 24, $url);
}


function fetchWantlistData($username, $token, $data_file)
{
    global $DISCOGS_API_URL_USERS, $CONTEXT, $MAX_AGE_IN_SECONDS;

    if (isValidJsonFile($data_file, 'wants') && (time() - filemtime($data_file)) < $MAX_AGE_IN_SECONDS) {
        Debug::log("Wantlist data is up-to-date.");
        return;
    }

    $apiUrl      = $DISCOGS_API_URL_USERS . $username . '/wants';
    $page        = 1;
    $perPage     = 50;
    $hasMorePages = true;
    $allWants    = [];

    while ($hasMorePages) {
        $url      = $apiUrl . '?page=' . $page . '&per_page=' . $perPage . '&token=' . $token;
        Debug::log("$url<br>");
        $response = file_get_contents($url, false, $CONTEXT);
        $data     = json_decode($response, true);
        if (isset($data['wants'])) {
            $allWants = array_merge($allWants, $data['wants']);
        }
        if (isset($data['pagination']) && $data['pagination']['pages'] > $page) {
            $page++;
        } else {
            $hasMorePages = false;
        }
    }

    if (isValidFetchedData(['wants' => $allWants], 'wants')) {
        $fileData = ['wants' => $allWants];
        file_put_contents($data_file, json_encode($fileData, JSON_PRETTY_PRINT));
        Debug::log("Wantlist data updated and saved to $data_file.");
    } else {
        Debug::log("Invalid data fetched, skipping save for wantlist data.");
    }
}


function fetchCollectionValueData($username, $token, $data_file)
{
    global $DISCOGS_API_URL_USERS;
    $apiUrl = $DISCOGS_API_URL_USERS . $username . '/collection/value?token=' . $token;
    check_data_file($data_file, 24, $apiUrl);
}


function get_collection($data_file, $table)
{
    global $PARAMS;

    if (!file_exists($data_file)) {
        echo "Collection file not found!";
        return [$table => [], 'total_pages' => 0, 'value' => null];
    }

    $data = json_decode(file_get_contents($data_file), true);

    if (!isset($data[$table])) {
        echo "Invalid JSON structure: '$table' not found!";
        return [$table => [], 'total_pages' => 0, 'value' => null];
    }

    $items = $data[$table];

    if ($table === 'releases' && isset($PARAMS['folder_id']) && $PARAMS['folder_id'] != 0) {
        $items = array_filter($items, function ($item) use ($PARAMS) {
            return $item['folder_id'] == $PARAMS['folder_id'];
        });
    }

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
	
	// Filter by format_filter if specified
    if ($table === 'releases' && isset($PARAMS['format_filter']) && !empty($PARAMS['format_filter'])) {
        $items = array_filter($items, function ($item) use ($PARAMS) {
            foreach ($item['basic_information']['formats'] as $format) {
                if (strcasecmp($format['name'], $PARAMS['format_filter']) === 0) {
                    return true;
                }
            }
            return false;
        });
    }

    $total_items = count($items);

    $sort_by = $PARAMS['sort_by'] ?? 'date_added';
    $order   = $PARAMS['order']   ?? 'asc';

    $sortField = match ($sort_by) {
        'added'  => 'date_added',
        'title'  => 'basic_information.title',
        'year'   => 'basic_information.year',
        'artist' => 'basic_information.artists.0.name',
        default  => 'date_added',
    };

    usort($items, function ($a, $b) use ($sortField, $order) {
        $valueA = get_hardcoded_value($a, $sortField);
        $valueB = get_hardcoded_value($b, $sortField);
        if ($valueA === null) $valueA = '';
        if ($valueB === null) $valueB = '';
        if (is_numeric($valueA) && is_numeric($valueB)) {
            $comparison = $valueA - $valueB;
        } else {
            $comparison = strcmp($valueA, $valueB);
        }
        return ($order === 'desc') ? -$comparison : $comparison;
    });

    $page      = $PARAMS['page']     ?? 1;
    $per_page  = $PARAMS['per_page'] ?? 48;
    $offset    = ($page - 1) * $per_page;
    $total_pages = ceil($total_items / $per_page);
    $pagedItems  = array_slice($items, $offset, $per_page);

    $collection_value = $data['value'] ?? null;

    return array($pagedItems, $total_pages, $collection_value);
}

function get_distinct_formats($data_file) {
    if (!file_exists($data_file)) return [];
    $data = json_decode(file_get_contents($data_file), true);
    if (!isset($data['releases'])) return [];

    $formats = [];
    foreach ($data['releases'] as $release) {
        if (!isset($release['basic_information']['formats'])) continue;
        foreach ($release['basic_information']['formats'] as $format) {
            $name = $format['name'];
            if (!isset($formats[$name])) {
                $formats[$name] = 0;
            }
            $formats[$name]++;
        }
    }
    arsort($formats);
    return $formats;
}



function display_related_sites() {

    global $LOCAL_RELEASE_MUSICBRAINZ_FILE;

    $allowed_sites = [
        'AllMusic', 'Discogs', 'Pitchfork', 'Genius', 'Bandcamp',
        'SoundCloud', 'YouTube', 'Official homepage', 'homepage', 'Wikipedia',
        'iTunes', 'Spotify', 'Myspace', 'RYM', 'Rateyourmusic', 'Tidal',
        'Metal Archives', 'AOTY',
    ];

    $type_map = [
        'a50a1d20-2b20-4d2c-9a29-eb771dd78386' => 'AllMusic',
        '99e550f3-5ab4-3110-b5b9-fe01d970b126' => 'Discogs',
        '156344d3-da8b-40c6-8b10-7b1c22727124' => 'Genius',
        'c3ac9c3b-f546-4d15-873f-b294d2c1b708' => 'Review',
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

    $json      = file_get_contents($LOCAL_RELEASE_MUSICBRAINZ_FILE);
    $data      = json_decode($json, true);
    $relations = $data['related_sites']['relations'] ?? $data['release_group']['relations'] ?? [];

    if (empty($relations)) {
        echo "<p>No related sites found in MusicBrainz data.</p>";
        return;
    }

    foreach ($relations as $relation) {
        if (!isset($relation['url']['resource'])) continue;

        $url    = htmlspecialchars($relation['url']['resource']);
        $typeId = $relation['type-id'] ?? null;
        $domain = parse_url($url, PHP_URL_HOST);
        $label  = null;
        $faIcon = "arrow-up-right-from-square";
        $style  = "";

        if ($typeId && isset($type_map[$typeId])) {
            $label = $type_map[$typeId];
        }

        if (str_contains($domain, 'pitchfork'))           { $label = 'Pitchfork'; }
        elseif (str_contains($domain, 'genius.com'))      { $label = 'Genius';    $faIcon = "brain";       $style = "color:darkgrey;background-color:yellow;"; }
        elseif (str_contains($domain, 'rateyourmusic'))   { $label = 'RYM';       $faIcon = "star";        $style = "color:white;background-color:cornflowerblue;"; }
        elseif (str_contains($domain, 'bandcamp.com'))    { $label = 'Bandcamp'; }
        elseif (str_contains($domain, 'soundcloud.com'))  { $label = 'SoundCloud'; }
        elseif (str_contains($domain, 'youtube.com'))     { $label = 'YouTube';   $faIcon = "youtube";     $style = "color:white;background-color:red;"; }
        elseif (str_contains($domain, 'itunes.apple.com')){ $label = 'iTunes';    $faIcon = "itunes-note"; $style = "color:red;background-color:black;"; }
        elseif (str_contains($domain, 'spotify.com'))     { $label = 'Spotify';   $faIcon = "spotify";     $style = "color:black;background-color:green;"; }
        elseif (str_contains($domain, 'myspace.com'))     { $label = 'Myspace'; }
        elseif (str_contains($domain, 'tidal.com'))       { $label = 'Tidal'; }
        elseif (str_contains($domain, 'wikipedia'))       { $label = 'Wikipedia'; $faIcon = 'wikipedia-w'; $style = "color:black;background-color:lightgrey;"; }
        elseif (str_contains($domain, 'allmusic.com'))    { $label = 'AllMusic';  $faIcon = "music";       $style = "color:white;background-color:royalblue;"; }
        elseif (str_contains($domain, 'albumoftheyear'))  { $label = 'AOTY';      $faIcon = "music";       $style = "color:white;background-color:royalblue;"; }
        elseif (str_contains($domain, 'discogs.com'))     { $label = 'Discogs';   $faIcon = "crown";       $style = "background-color:darkgrey"; }
        elseif (str_contains($domain, 'metal-archives'))  { $label = 'Metal Archives'; $faIcon = "guitar"; $style = "background-color:darkred"; }

        if ($label && in_array($label, $allowed_sites)) {
            echo "<a href=\"$url\" class=\"btn btn-sm m-1 border-0\" role=\"button\" style=\"$style\">"
               . "<span class=\"pe-1 border-1 border-mute border-end disabled\">"
               . "<i class=\"text-black fa fa-fw fa-$faIcon\"></i></span> $label</a>";
        }
    }
}


function get_hardcoded_value($item, $sortField)
{
    switch ($sortField) {
        case 'date_added':                      return $item['date_added'] ?? '';
        case 'basic_information.title':         return $item['basic_information']['title'] ?? '';
        case 'basic_information.year':          return $item['basic_information']['year'] ?? '';
        case 'basic_information.artists.0.name':return $item['basic_information']['artists'][0]['name'] ?? '';
        default:                                return '';
    }
}


function get_random_release_id($folder_id)
{
    global $DATA_FILES;

    if (!file_exists($DATA_FILES['releases'])) {
        echo "Collection file not found!";
        return null;
    }

    $data = json_decode(file_get_contents($DATA_FILES['releases']), true);

    if (!isset($data['releases'])) {
        echo "'releases' not found in the JSON!";
        return null;
    }

    $filteredReleases = array_filter($data['releases'], function ($release) use ($folder_id) {
        return ($folder_id == 0 || $release['folder_id'] == $folder_id);
    });

    if (empty($filteredReleases)) {
        echo "No releases found for folder_id: $folder_id";
        return null;
    }

    $randomRelease = $filteredReleases[array_rand($filteredReleases)];
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

    if (!is_dir($LOCAL_RELEASES_DATA_ROOT)) {
        mkdir($LOCAL_RELEASES_DATA_ROOT, 0777, true);
    }

    $releasejson = $DISCOGS_API_URL . "releases/" . $release_id . "?token=" . $DISCOGS_TOKEN;

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

    if (!is_dir($LOCAL_USER_RELEASES_DATA_ROOT)) {
        mkdir($LOCAL_USER_RELEASES_DATA_ROOT, 0777, true);
    }

    $masterreleasejson = $DISCOGS_API_URL . "masters/" . $master_id . "?token=" . $DISCOGS_TOKEN;

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
        . $master_id . "&fmt=json";

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

    if (file_exists($MUSICBRAINZ_DATA_FILE)) {
        $musicbrainzdata = file_get_contents($MUSICBRAINZ_DATA_FILE);
        $musicbrainzinfo = json_decode($musicbrainzdata, true);
        Debug::log($musicbrainzdata);
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
        CURLOPT_HEADER         => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT      => 'RateMyCrate/1.0 (+https://ratemycrate.com)',
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

    $headers_raw = substr($response, 0, $header_size);
    $body        = trim(substr($response, $header_size));

    $headers = [];
    foreach (explode("\r\n", $headers_raw) as $line) {
        if (strpos($line, ':') !== false) {
            list($k, $v) = explode(':', $line, 2);
            $headers[trim($k)] = trim($v);
        }
    }

    if (isset($headers['X-Discogs-Ratelimit-Remaining'])) {
        $remaining = (int) $headers['X-Discogs-Ratelimit-Remaining'];
        if ($remaining <= 1) {
            Debug::log("Approaching rate limit. Sleeping 60s...");
            sleep(60);
        }
    }

    if ($status_code === 429) {
        Debug::log("429 Too Many Requests. Sleeping 60s and retrying...");
        sleep(60);
        return fetch_json_with_throttle($url);
    }

    if (preg_match('/See:\s*(https?:\/\/\S+)/', $body, $matches)) {
        $redirect_url = trim($matches[1]);
        Debug::log("Redirecting to archive.org JSON: $redirect_url");
        return fetch_json_with_throttle($redirect_url);
    }

    $decoded = json_decode($body, true);
    if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
        if (!empty($PARAMS['debug'])) {
            add_debug("Invalid JSON from $url: " . json_last_error_msg());
            add_debug("HTTP status: $status_code");
            add_debug("Raw response body (first 500 chars): " . substr($body, 0, 500));
        }
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
    $sources       = is_array($data_file_json) ? $data_file_json : ['single' => $data_file_json];

    foreach ($sources as $key => $url) {
        Debug::log("Fetching data from: $url");
        $decoded_data = fetch_json_with_throttle($url);

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

    if (file_exists($data_file) && (time() - filemtime($data_file)) < $max_seconds) {
        Debug::log("-> $data_file is not old.");
        $data_file_data = file_get_contents($data_file);
        $data_file_info = json_decode($data_file_data, true);
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
        $myreleaseinfo = check_data_file($LOCAL_USER_MYRELEASE_DATA_FILE, rand(128, 168), $myreleasejson);
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

    $releaseinfo['artists_plain'] = implode(", ", array_column($releasedata['artists'], 'name'));

    $releaseinfo['artists_linked'] = '';
    $releaseinfo['artists_linked'] .= implode(" ", array_map(function ($artist) {
        $artist_url = "https://www.discogs.com/artist/{$artist['id']}";
        return "<a class='display-release-data-indent list-group-item list-group-item-action'"
             . " href=\"{$artist_url}\" target=\"_blank\">{$artist['name']}"
             . " <i class='fa fa-fw fa-arrow-up-right-from-square'></i></a> ";
    }, $releasedata['artists']));

    $releaseinfo['artists_list'] = '<ul class="list-group">';
    $releaseinfo['artists_list'] .= implode(" ", array_map(function ($artist) {
        return "<li class='display-release-data-indent list-group-item list-group-item-action'>"
             . "{$artist['name']}</li> ";
    }, $releasedata['artists']));
    $releaseinfo['artists_list'] .= '</ul>';

    // Tracklist
    $release_tracklist_rows = '';
    if (array_key_exists('tracklist', $releasedata)) {
        $tracklist = $releasedata['tracklist'];
        $release_tracklist_rows = '<tr><th scope="col">Disk/Side</th>'
            . '<th scope="col">Track Name</th><th scope="col">mm:ss</th></tr>';

        foreach ($tracklist as $track) {
            $track_extraartists_list = '';
            if (array_key_exists('extraartists', $track)) {
                $roles = [];
                foreach ($track['extraartists'] as $extraartist) {
                    $roles[$extraartist['role']][] = $extraartist['name'];
                }
                foreach ($roles as $role => $artistss) {
                    $track_extraartists_list .= '<strong>' . $role . ':</strong> '
                        . implode(', ', $artistss) . '<br>';
                }
            }
            $duration = $track['duration'] ?: "--:--";
            $release_tracklist_rows .=
                '<tr>'
                . '<th data-align="left" style="width:1%">'
                . '<p class="text-body-secondary track-list-position">'
                . $track['position'] . ' </p></th>'
                . '<td data-align="left">'
                . '<p class="text-body-primary track-list-titles fw-bold">' . $track['title'] . '</p>'
                . '<p class="display-release-data-indent text-muted track-list-artists fw-light">'
                . $track_extraartists_list . '</p></td>'
                . '<td data-align="left" class="text-body-secondary track-list-position">'
                . $duration . '</td>'
                . '</tr>';
        }
    }
    $releaseinfo['tracklist_rows'] = $release_tracklist_rows;

    // Extra artists
    $extra_artists_rows = '<ul class="list-group">';
    if (array_key_exists('extraartists', $releasedata)) {
        $grouped_artists = [];
        foreach ($releasedata['extraartists'] as $extraartist) {
            $role   = $extraartist['role'];
            $name   = $extraartist['name'];
            $tracks = (isset($extraartist['tracks']) && !empty($extraartist['tracks']))
                ? ' (' . $extraartist['tracks'] . ')' : '';
            $grouped_artists[$role][] = $name . $tracks;
        }
        foreach ($grouped_artists as $role => $artists) {
            $extra_artists_rows .= "<li class=\"list-group-item\"><strong>$role</strong></li>\n";
            foreach ($artists as $artist) {
                $extra_artists_rows .= "<li class=\"display-release-data-indent list-group-item"
                    . " list-group-item-action text-muted track-list-artists fw-light\">$artist</li>\n";
            }
        }
    }
    $extra_artists_rows .= '</ul>' . "\n\n";
    $releaseinfo['extra_artists_rows'] = $extra_artists_rows;

    // Labels
    $labelnames = '';
    if (array_key_exists('labels', $releaseinfo)) {
        foreach ($releasedata['labels'] as $label) {
            $labelnames .= '<li class="display-release-data-indent list-group-item list-group-item-action">';
            if (array_key_exists('name', $label))  $labelnames .= $label['name'];
            if (array_key_exists('catno', $label)) $labelnames .= ', ' . $label['catno'] . '</li>';
        }
    }
    $releaseinfo['label_names'] = $labelnames;

    // Formats
    $formats = '';
    $vinyl_descriptions = '';
    if (array_key_exists('formats', $releasedata)) {
        $number_of_formats = sizeof($releasedata['formats']);
        for ($i = 0; $i < $number_of_formats; $i++) {
            $sep = ($i != 0 && $number_of_formats > 1)
                ? '</li><li class="display-release-data-indent list-group-item list-group-item-action">'
                : '<li class="display-release-data-indent list-group-item list-group-item-action">';
            if (array_key_exists('name', $releasedata['formats'][$i])) {
                $qty = ($releasedata['formats'][$i]['qty'] > 1)
                    ? ' (x ' . $releasedata['formats'][$i]['qty'] . ')' : '';
                $formats .= $sep . '<strong>' . $releasedata['formats'][$i]['name'] . '</strong>' . $qty;
            }
            if (!array_key_exists('text', $releasedata['formats'][$i])
                && !array_key_exists('descriptions', $releasedata['formats'][$i])) {
                $formats .= ' ! ';
            }
            if (array_key_exists('descriptions', $releasedata['formats'][$i])) {
                $formats .= ', ' . implode(", ", $releasedata['formats'][$i]['descriptions']);
                $vinyl_descriptions = implode(", ", $releasedata['formats'][$i]['descriptions']);
            }
            if (!array_key_exists('descriptions', $releasedata['formats'][$i])) $formats .= ' ! ';
            if (array_key_exists('text', $releasedata['formats'][$i]))
                $formats .= ', <i>' . $releasedata['formats'][$i]['text'] . '</i>';
        }
    }
    $formats .= '</li>';
    $releaseinfo['formats']           = $formats;
    $releaseinfo['vinyl_descriptions'] = $vinyl_descriptions;

    $releaseinfo['genres'] = implode(", ", $releasedata['genres']);
    $releaseinfo['styles'] = array_key_exists('styles', $releasedata)
        ? implode(", ", $releasedata['styles']) : '';

    // Identifiers
    $identifier_rows = '<ul class="list-group">';
    if (array_key_exists('identifiers', $releasedata)) {
        foreach ($releasedata['identifiers'] as $id_item) {
            $identifier_rows .= '<li class="list-group-item list-group-item-action"><strong>'
                . $id_item['type'] . ' (' . ($id_item['description'] ?? '') . ')</strong></li>'
                . '<li class="display-release-data-indent list-group-item list-group-item-action'
                . ' text-muted track-list-artists fw-light">' . $id_item['value'] . '</li>';
        }
    }
    $identifier_rows .= '</ul>';
    $releaseinfo['identifier_rows'] = $identifier_rows;

    // Companies
    $list_of_companies_rows = '<ul class="list-group">';
    if (array_key_exists('companies', $releasedata)) {
        $grouped_companies = [];
        foreach ($releasedata['companies'] as $company) {
            $grouped_companies[$company['entity_type_name']][] = $company['name'];
        }
        foreach ($grouped_companies as $entity_type_name => $company_names) {
            $list_of_companies_rows .= '<li class="list-group-item list-group-item-action">'
                . '<strong>' . $entity_type_name . '</strong></li>' . "\n";
            foreach ($company_names as $name) {
                $list_of_companies_rows .= '<li class="display-release-data-indent list-group-item'
                    . ' list-group-item-action text-muted fw-light">' . $name . '</li>' . "\n";
            }
        }
    }
    $list_of_companies_rows .= '</ul>' . "\n\n";
    $releaseinfo['companies_rows'] = $list_of_companies_rows;

    $releaseinfo['releasenotes'] = $releasedata['notes'] ?? '';

    // My release notes
    $my_release_notes_rows = '';
    $my_release_notes      = '';
    if (array_key_exists('notes', $myreleaseinfo['releases'][0] ?? [])) {
        foreach ($myreleaseinfo['releases'][0]['notes'] as $mynotes) {
            if ($mynotes['field_id'] == 1)      { $noteicon = 'fa fa-fw fa-record-vinyl'; $notetype = 'Media'; }
            elseif ($mynotes['field_id'] == 2)  { $noteicon = 'fa fa-fw fa-square';       $notetype = 'Jacket'; }
            elseif ($mynotes['field_id'] == 3)  { $noteicon = 'fa fa-fw fa-clipboard';    $notetype = 'Notes'; }
            elseif ($mynotes['field_id'] == 4)  { $noteicon = 'fa fa-fw fa-clipboard';    $notetype = 'Category'; }
            else                                { $noteicon = 'fa fa-fw fa-clipboard';    $notetype = 'Note'; }

            $my_release_notes_rows .=
                '<li class="card-header list-group-item text-muted">'
                . '<i class="text-muted ' . $noteicon . '"></i> ' . $notetype . '</li>'
                . '<li class="display-release-data-indent list-group-item display-release-data-indent">'
                . $mynotes['value'] . '</li>' . "\n";
        }
    }
    $releaseinfo['rating']               = $releasedata['community']['rating']['average'];
    $releaseinfo['myrating']             = $myreleaseinfo['releases'][0]['rating'] ?? 0;
    $releaseinfo['my_release_notes_rows'] = $my_release_notes_rows;
    $releaseinfo['my_release_notes']      = $my_release_notes;

    // Master release info
    if (isset($releaseinfo['master_id']) && $releaseinfo['master_id']) {
        $masterreleaseinfo = get_release_master_information($releaseinfo['master_id'], $releaseinfo['id']);
        $releaseinfo['master_id']                          = $masterreleaseinfo['id']            ?? "";
        $releaseinfo['master_release_year']                = $masterreleaseinfo['year']          ?? "";
        $releaseinfo['master_uri']                         = $masterreleaseinfo['uri']           ?? "";
        $releaseinfo['master_main_release_id']             = $masterreleaseinfo['main_release']  ?? "";
        $releaseinfo['master_main_release_url']            = $masterreleaseinfo['main_release_url'] ?? "";
        $mbdata = get_musicbrainz_master_release_group($releaseinfo['master_id']);
        $releaseinfo['master_musicbrainz_release_group_id'] = $mbdata ?? "";
    }

    $releaseinfo['musicbrainzid']     = get_musicbrainz_id($releasedata['id']);
    $releaseinfo['most_recent_release'] = $masterreleaseinfo['most_recent_release'] ?? "";

    return $releaseinfo;
}


function render_stars($rating, $max_stars = 5) {
    $full_stars  = floor($rating);
    $half_star   = ($rating - $full_stars) >= 0.25 && ($rating - $full_stars) < 0.75;
    $empty_stars = $max_stars - $full_stars - ($half_star ? 1 : 0);
    $stars_html  = '';
    for ($i = 0; $i < $full_stars; $i++)
        $stars_html .= '<i class="small fa fa-fw fa-solid fa-star text-warning"></i>';
    if ($half_star)
        $stars_html .= '<i class="small fa fa-fw fa-solid fa-star-half-stroke text-warning"></i>';
    for ($i = 0; $i < $empty_stars; $i++)
        $stars_html .= '<i class="small fa fa-fw fa-regular fa-star text-warning"></i>';
    return $stars_html;
}


function make_badge($badgeText = "none", $backgroundColor = "bg-accent_color", $textColor = "text-muted")
{
    $badgeText_lower = strtolower($badgeText);
    if (in_array($badgeText_lower, ["12", "10", "7", "lp", "ep", "single", "mp3", "flac"])) {
        $backgroundColor = "bg-black";
    } elseif ($badgeText_lower === "new") {
        $textColor       = "fg-limegreen";
        $backgroundColor = "";
        $badgeText       = '<i class="fa-solid fa-certificate"></i>';
    } else {
        $textColor       = "text-muted";
        $backgroundColor = "bg-black";
    }
    return "<span class=\"badge badge-pill format-badge-primary $textColor $backgroundColor\">$badgeText</span>";
}


function display_gallery_item($release)
{
    global $IMAGE_PATH_ROOT_URL_PREFIX_200;
    global $IMAGE_PATH_ROOT_URL_SUFFIX;
    global $PARAMS, $IS_WANTLIST;

    $artists       = implode(", ", array_column($release['basic_information']['artists'], "name"));
    $title         = $release['basic_information']['title'];
    $release_id    = $release['basic_information']['id'];
    $primaryFormat = $release['basic_information']['formats'][0]['name'];
    $adddate       = date('m/d/y', strtotime(substr($release['date_added'], 0, 10)));
    $ebaySearchURL = "https://www.ebay.com/sch/i.html?_nkw=";
	$discogsSearchURL = "https://www.discogs.com/sell/release/$release_id";

    $secondaryFormatBadge = '';
    $formatKeywords       = ['Vinyl', 'Lathe', 'Cass', 'LP', 'CD', 'EP', '12', '10', '7', "File", "Flac", "MP3"];

    if (!empty($release['basic_information']['formats'])) {
        foreach ($release['basic_information']['formats'] as $format) {
            $formatName         = $format['name']         ?? '';
            $formatDescriptions = $format['descriptions'] ?? [];
            $allFormats         = $formatName . (!empty($formatDescriptions) ? ', ' . implode(', ', $formatDescriptions) : '');
            foreach ($formatKeywords as $keyword) {
                if (str_contains($allFormats, $keyword)) {
                    $secondaryFormatBadge .= make_badge($keyword);
                }
            }
        }
    }

    $image_url = $IMAGE_PATH_ROOT_URL_PREFIX_200 . get_image_url($release_id);

    $is_new_class = 'border-0';
    $is_new_badge = '';
    if (strtotime($adddate) > strtotime('-14 days')) {
        $is_new_class = ' border-0';
        $is_new_badge = make_badge("New", "text-warning");
    }

    $footer_text = make_badge($adddate, "bg-white", "text-dark");

    $release_urlParams = [
        'release_id' => $release_id,
        'username'   => '', 'type'     => '', 'sort_by'  => '',
        'page'       => '', 'order'    => '', 'per_page' => '', 'artist_id' => '0',
    ];
    $release_url = build_url("/", set_params($PARAMS, $release_urlParams));
    $discogs_url = "https://www.discogs.com/release/$release_id";

    if ($PARAMS['sort_by'] == 'title') {
        $first_line_text  = $title;
        $second_line_text = $artists;
    } else {
        $first_line_text  = $artists;
        $second_line_text = $title;
    }

    ?>
    <!-- Gallery item -->
    <div class="releaseGalleryItem">
        <div class="card bg-dark text-white <?php echo $is_new_class; ?>">
            <a href="<?php echo $IS_WANTLIST ? $discogs_url : $release_url; ?>"
               title="<?php echo $second_line_text; ?> by <?php echo $first_line_text; ?>"
               class="stretched-link">
                <img loading="lazy"
                     src="<?php echo $image_url . $IMAGE_PATH_ROOT_URL_SUFFIX; ?>"
                     class="card-img img-fluid img-thumbnail"
                     alt="<?php echo $second_line_text; ?> by <?php echo $first_line_text; ?>">
                <div class="card-img-overlay blurtop">
                    <h5 class="card-title"><?php echo $first_line_text; ?></h5>
                    <h5 class="card-subtitle"><?php echo $second_line_text; ?></h5>
                </div>
            </a>

            <div class="card-footer second">
                <span class="float-start">
                    <small><?php echo $footer_text; ?> <?php echo $is_new_badge; ?></small>
                </span>
                <span class="float-end">
                    <small><?php echo $secondaryFormatBadge; ?></small>
                <?php if ($IS_WANTLIST) {
                    echo "<a class=\"badge badge-pill format-badge-primary bg-red\""
                       . " href=\"$ebaySearchURL+$first_line_text+$second_line_text+$primaryFormat\""
                       . " title=\"Search for release on eBay\">eBay</a>"
					   . "<a class=\"badge badge-pill format-badge-primary bg-blue\""
                       . " href=\"$discogsSearchURL\""
                      . " title=\"Search for release on Discogs\">Discogs</a>";
                } ?>
				
                </span>
            </div>
        </div>
    </div>
    <!-- End gallery item -->
    <?php
}


function display_release_data($releaseinfo)
{
    global $PARAMS, $SOCIALS, $IMAGE_PATH_ROOT_URL;

    $id                  = $releaseinfo['id'];
    $title               = $releaseinfo['title'];
    $artists_linked      = $releaseinfo['artists_linked'];
    $labelnames          = $releaseinfo['label_names'];
    $formats             = $releaseinfo['formats'];
    $genres              = $releaseinfo['genres'];
    $styles              = $releaseinfo['styles'];
    $year                = $releaseinfo['year'];
    $rating              = $releaseinfo['rating'];
    $myrating            = $releaseinfo['myrating'];
    $releasenotes        = $releaseinfo['releasenotes'];
    $tracklist           = $releaseinfo['tracklist_rows'];
    $identifiers         = $releaseinfo['identifier_rows'];
    $companies           = $releaseinfo['companies_rows'];
    $extra_artists_rows  = $releaseinfo['extra_artists_rows'];
    $my_release_notes_rows = $releaseinfo['my_release_notes_rows'];
    $my_release_notes    = $releaseinfo['my_release_notes'];
    $master_release_url  = $releaseinfo['master_uri']          ?? "";
    $master_year         = $releaseinfo['master_release_year'] ?? "";
    $display_master_year = ($year === $master_year) || (is_numeric($year) && !is_numeric($master_year)) ? 0 : 1;
    $master_id           = $releaseinfo['master_id']           ?? "";
    $artists_plain       = $releaseinfo['artists_plain'];
    $musicbrainzid       = $releaseinfo['musicbrainzid']       ?? "";
    $mb_release_group    = $releaseinfo['master_musicbrainz_release_group_id'] ?? "";
    $resource_url        = "https://api.discogs.com/releases/$id";

    ?>
    <div class="col-md-4 col-sm-6 my-3">
        <div class="accordion" id="accordionExample">
            <div class="accordion-item">
                <h2 class="accordion-header font-weight-bold" id="headingReleaseData">
                    <button class="accordion-button" type="button"
                            data-bs-toggle="collapse" data-bs-target="#collapseReleaseData"
                            aria-expanded="true" aria-controls="collapseReleaseData">
                        <strong>Release Data</strong>
                    </button>
                </h2>
                <div id="collapseReleaseData" class="accordion-collapse collapse show"
                     aria-labelledby="headingReleaseData" data-bs-parent="#accordionExample">
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
                            <?php if ($rating)  echo wrap_list_group_items(render_stars($rating)   . " (Community)"); ?>
                            <?php if ($myrating) echo wrap_list_group_items(render_stars($myrating) . " (Mine)"); ?>
                        </ul>

                        <ul class="list-group cardReleaseData">
                            <li class="card-header list-group-item"><i class="fa fa-fw fa-building"></i> Label(s)</li>
                            <?php if ($labelnames) echo $labelnames; ?>
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
                            <?php if ($styles) echo wrap_list_group_items($styles); ?>
                        </ul>

                        <ul class="list-group cardReleaseData">
                            <li class="card-header list-group-item"><i class="fa fa-fw fa-tags"></i> Release Note(s)</li>
                            <?php if ($my_release_notes_rows) echo $my_release_notes_rows; ?>
                        </ul>

                        <ul class="list-group cardReleaseData">
                            <li class="card-header list-group-item"><i class="text-musicbrainz fa fa-fw fa-brain"></i> MusicBrainz ID</li>
                            <?php if ($musicbrainzid) echo wrap_list_group_items($musicbrainzid); ?>
                            <li class="card-header list-group-item"><i class="text-musicbrainz fa fa-fw fa-brain"></i> MusicBrainz Group ID</li>
                            <?php if ($mb_release_group) echo wrap_list_group_items($mb_release_group); ?>
                        </ul>

                        <ul class="list-group cardReleaseData">
                            <li class="card-header list-group-item"><i class="text-master fa fa-fw fa-crown"></i> Master</li>
                            <?php if ($master_id): ?>
                                <a class='display-release-data-indent list-group-item list-group-item-action'
                                   href='<?php echo $master_release_url; ?>'>
                                    ID #<?php echo $master_id; ?>
                                    <i class='fa fa-fw fa-arrow-up-right-from-square'></i>
                                </a>
                            <?php endif; ?>
                        </ul>

                        <div class="card list-group cardReleaseData">
                            <div class="card-header"><i class="fa fa-fw fa-link"></i> Outbound Links</div>
                            <div class="card-body">
                                <div class="related-sites">
                                    <div class="d-grid gap-2 d-md-block">
                                        <?php display_related_sites(); ?>
                                        <a class="btn btn-sm m-1 border-0 btn-primary"
                                           href="https://www.discogs.com/release/<?php echo $id ?>"
                                           title="View on discogs.com">
                                            <span class="pe-1 border-1 border-mute border-end">
                                                <i class="text-black fa fa-fw fa-record-vinyl"></i>
                                            </span> Discogs
                                        </a>
                                        <?php if ($PARAMS['debug']): ?>
                                            <a class="btn btn-sm m-1 border-0 btn-primary"
                                               href="<?php echo $resource_url; ?>"
                                               title="View JSON data from discogs.com API">
                                                <span class="pe-1 border-1 border-mute border-end">
                                                    <i class="fa fa-fw fa-code"></i>
                                                </span> json
                                            </a>
                                            <a class="btn btn-sm m-1 border-0 btn-primary bg-success"
                                               href="https://musicbrainz.org/ws/2/url?resource=https://www.discogs.com/master/<?php echo $master_id; ?>"
                                               title="View MusicBrainz ID">
                                                <span class="pe-1 border-1 border-mute border-end">
                                                    <i class="fa fa-fw fa-code"></i>
                                                </span> MBID
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($SOCIALS['lastfm']): ?>
                                            <a class="btn btn-sm m-1 border-0 bg-danger"
                                               href="https://openscrobbler.com/scrobble/album/view/dsid/release-<?php echo $id; ?>"
                                               title="Scrobble via last.fm">
                                                <span class="pe-1 border-1 border-mute border-end">
                                                    <i class="fa fa-fw fa-lastfm"></i>
                                                </span> LastFm
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($SOCIALS['bluesky']): ?>
                                            <a class="btn btn-sm m-1 border-0 bg-info"
                                               href="https://bsky.app/intent/compose?text=%23nowspinning%0D%0A%0D%0A<?php echo $title; ?> by <?php echo $artists_plain; ?> %0D%0A%0D%0A%23vinylsky %23vinylrecords %23vinylcommunity #vinylcollection"
                                               target="_blank" rel="noopener noreferrer"
                                               title="Send skeet to BlueSky">
                                                <span class="pe-1 border-1 border-mute border-end">
                                                    <i class="fa fa-fw fa-brands fa-bluesky"></i>
                                                </span> BlueSky
                                            </a>
                                        <?php endif; ?>
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
                <?php echo wrap_accordian_rows('Track List',     wrap_table_rows('', $tracklist),                       'opened'); ?>
                <?php echo wrap_accordian_rows('Release Notes',  '<pre>' . $releasenotes . '</pre>',                    'opened'); ?>
                <?php echo wrap_accordian_rows('My Release Notes', $my_release_notes,                                  'opened'); ?>
                <?php echo wrap_accordian_rows('Credits',        wrap_listgroup_items('Credits',   $extra_artists_rows)); ?>
                <?php echo wrap_accordian_rows('Companies',      wrap_listgroup_items('Companies', $companies)); ?>
                <?php echo wrap_accordian_rows('Identifiers',    wrap_listgroup_items('Identifiers', $identifiers)); ?>
            </div>
        </div>
    </div>
    <?php
}


function css_is_hidden($page_type = "all")
{
    global $PARAMS;
    $displayStatus = "d-none";
    if ($page_type == "all") $page_type = $PARAMS['type'];
    if ($page_type == "single" && $PARAMS['release_id']) $displayStatus = "";
    if ($PARAMS['type'] == $page_type) $displayStatus = "";
    return $displayStatus;
}


function paginate($current, $last, $max = null)
{
    global $PARAMS;
    $max      = ($max === null) ? $last : $max;
    $halfMax  = floor($max / 2);
    $start    = max(1, min($last - $max + 1, $current - $halfMax));
    $end      = min($last, $start + $max - 1);
    $start    = max(1, $end - $max + 1);
    $ellipsisStart = ($start > 1);
    $ellipsisEnd   = ($end < $last);
    $html = '';
    if ($ellipsisStart) {
        $url   = build_url("/", set_param($PARAMS, 'page', '1'));
        $html .= '<li class="page-item"><a class="page-link paginationItem" href="' . $url . '">1</a></li>'
               . '<li class="page-item"><a class="page-link paginationItem disabled" href="#">..</a></li>';
    }
    for ($i = $start; $i <= $end; $i++) {
        $url   = build_url("/", set_param($PARAMS, 'page', $i));
        $html .= ($i == $current)
            ? '<li class="page-item"><a class="page-link paginationItem disabled">' . $i . '</a></li>'
            : '<li class="page-item"><a class="text-accent_color page-link paginationItem" href="' . $url . '">' . $i . '</a></li>';
    }
    if ($ellipsisEnd) {
        $url   = build_url("/", set_param($PARAMS, 'page', $last));
        $html .= '<li class="page-item"><a class="page-link paginationItem disabled">..</a></li>'
               . '<li class="page-item"><a class="text-accent_color page-link paginationItem" href="' . $url . '">' . $last . '</a></li>';
    }
    return $html;
}


function wrap_table_rows($title, $rows)
{
    $table = '<!-- START TABLE ' . $title . ' -->'
           . '<div class="p-1 table-responsive">'
           . '<table class="table table-striped table-bordered"><tbody>';
    if ($title)
        $table .= '<tr><th scope="row" colspan="3" style="width:1%">' . $title . '</th></tr>';
    $table .= $rows;
    $table .= '</tbody></table></div><!-- END ' . $title . ' -->';
    return $table;
}


function wrap_listgroup_items($groupname, $items)
{
    return "\n<!-- START $groupname -->\n" . $items . "<!-- END $groupname -->";
}


function wrap_list_group_items($string, $is_current = 0)
{
    $is_active = ($is_current == 1) ? " active" : "";
    return "<li class='display-release-data-indent list-group-item list-group-item-action$is_active'>$string</li>";
}


function wrap_accordian_rows($header, $data, $open = 0)
{
    $header_no_spaces = str_replace(' ', '', $header);
    $accordian = '<!-- START ' . $header . ' -->'
        . '<div class="accordion-item">'
        . '<h2 class="accordion-header font-weight-bold" id="heading' . $header_no_spaces . '">'
        . '<button class="accordion-button';
    $accordian .= $open ? '' : ' collapsed';
    $accordian .= '" type="button" data-bs-toggle="collapse"'
        . ' data-bs-target="#collapse' . $header_no_spaces . '"'
        . ' aria-expanded="false" aria-controls="collapse' . $header_no_spaces . '">'
        . '<strong>' . $header . '</strong></button></h2>'
        . '<div id="collapse' . $header_no_spaces . '" class="accordion-collapse collapse';
    $accordian .= $open ? ' show' : '';
    $accordian .= '" aria-labelledby="heading' . $header_no_spaces . '">'
        . '<div class="accordion-body">'
        . $data
        . '</div></div></div><!-- END ' . $header . ' -->';
    return $accordian;
}


function get_image_url($release_id, $download_images = null)
{
    global $LOCAL_RELEASES_DATA_ROOT, $IMAGE_PATH_ROOT, $IMAGE_PATH_ROOT_URL, $DOWNLOAD_IMAGES;

    $image_url      = "/img/no-album-art.png";
    $should_download = ($DOWNLOAD_IMAGES == 1 || $download_images == 1);

    if ($should_download) {
        $known_extensions = ['jpg', 'jpeg', 'png', 'webp'];
        foreach ($known_extensions as $ext) {
            $local_path = $IMAGE_PATH_ROOT . $release_id . '.' . $ext;
            $image_url  = $IMAGE_PATH_ROOT_URL . $release_id . '.' . $ext;
            if (file_exists($local_path)) {
                add_debug("Local image already exists: $local_path");
                return $image_url;
            }
        }
    }

    $have_final_image = 0;

    $coverart_path = $LOCAL_RELEASES_DATA_ROOT . $release_id . "_coverartarchive.json";
    if (file_exists($coverart_path)) {
        $cover_data = json_decode(file_get_contents($coverart_path), true);
        $image_url  = $cover_data['images'][0]['image'] ?? '';
        if (filter_var($image_url, FILTER_VALIDATE_URL)) {
            $have_final_image = 1;
        }
    }

    $release_path = $LOCAL_RELEASES_DATA_ROOT . $release_id . ".json";
    if (!$have_final_image && file_exists($release_path)) {
        $release_data = json_decode(file_get_contents($release_path), true);
        $image_url    = $release_data['images'][0]['uri'] ?? '';
        if (filter_var($image_url, FILTER_VALIDATE_URL)) {
            $have_final_image = 1;
        }
    }

    if ($have_final_image && $should_download) {
        $ext = pathinfo(parse_url($image_url, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'jpg';
        $local_image_path = $IMAGE_PATH_ROOT . $release_id . '.' . $ext;
        $image_data = file_get_contents($image_url);
        if ($image_data !== false) {
            file_put_contents($local_image_path, $image_data);
            return $local_image_path;
        }
    }

    return $image_url;
}


function is_valid_image_url($url, $allow_redirects = false)
{
    if (empty($url)) return false;
    $headers = @get_headers($url, 1);
    if (!$headers) return false;
    if (isset($headers[0]) && str_contains(strtolower($headers[0]), '200 ok')) return true;
    if ($allow_redirects && isset($headers['Location'])) {
        $final_url = is_array($headers['Location']) ? end($headers['Location']) : $headers['Location'];
        return is_valid_image_url($final_url, false);
    }
    return false;
}


function filter_collection_by_media($data_file, $media_filters = [])
{
    $data = json_decode(file_get_contents($data_file), true);
    if (empty($media_filters)) return $data['releases'] ?? [];
    return array_filter($data['releases'] ?? [], function ($release) use ($media_filters) {
        foreach ($release['basic_information']['formats'] as $format) {
            if (in_array($format['name'], $media_filters)) return true;
        }
        return false;
    });
}


/**
 * Load only the three summary numbers needed by the footer.
 * Reads statistics_summary.json (a tiny sidecar written by get_statistics())
 * instead of the full statistics.json, avoiding a large JSON decode on
 * every non-stats page load.
 * Falls back to the full file if the sidecar does not exist yet.
 */
function load_statistics_summary()
{
    global $DATA_FILES;

    $summary_file = $DATA_FILES['statistics_summary'];
    $full_file    = $DATA_FILES['statistics'];

    if (file_exists($summary_file)) {
        return json_decode(file_get_contents($summary_file), true) ?? [];
    }
    if (file_exists($full_file)) {
        $full = json_decode(file_get_contents($full_file), true) ?? [];
        return [
            'total_releases' => $full['total_releases'] ?? 0,
            'unique_artists' => $full['unique_artists'] ?? 0,
            'unique_genres'  => $full['unique_genres']  ?? 0,
        ];
    }
    return ['total_releases' => 0, 'unique_artists' => 0, 'unique_genres' => 0];
}


// PULL DISCOGS DATA REGARDING MY COLLECTION
if ($PARAMS['type'] != 'statistics'):
    $getcollection   = get_collection($DATA_FILES[$PARAMS['type']], $PARAMS['type']);
    $collection      = $getcollection[0];
    $total_pages     = $getcollection[1];
    $collection_value = $getcollection[2];
endif;