<?php

/* ******************************************************** */
/*                 User-Configurable Options                */
/* ******************************************************** */

// Default username for script
$DISCOGS_USERNAME = "nolageek";
// Default API token for script
$DISCOGS_TOKEN = "DUSodsSJCGjrQnuLHksibSjGpIISuajOKmsiOOje";
// Secondary/Accent color
$ACCENT_COLOR = "royalblue";
// Relative local path image directory
$IMAGE_PATH_ROOT = "./img/";

// Root URL for images
$IMAGE_PATH_ROOT_URL = "img/";

// URL Pefixes for use with CDN, eg 'https://username.imgix.net'
// Be careful not to put a forward slash both at the end of these, and at the beginning of the $IMAGE_PATH_ROOT_URL.
// This will cause a double slash and mess up the display of the image
$IMAGE_PATH_ROOT_URL_PREFIX_200 = "https://discogs.nolageek.com/cdn-cgi/image/width=200,height=200,quality=80,fit=cover,slow-connection-quality=50/";
$IMAGE_PATH_ROOT_URL_PREFIX_300 = "https://discogs.nolageek.com/cdn-cgi/image/width=300,height=300,quality=80,fit=cover,slow-connection-quality=50/";
$IMAGE_PATH_ROOT_URL_PREFIX_500 = "https://discogs.nolageek.com/cdn-cgi/image/width=500,height=500,quality=80,fit=cover,slow-connection-quality=50/";

// Suffix to be added after images, for use with CDN, etc. eg '?width=100&aspect=1:1&crop=yes"
$IMAGE_PATH_ROOT_URL_SUFFIX = "";

// Relative local path to json data directory
$LOCAL_DATA_PATH_ROOT = './jsondata/';

// Default max age for files before they will be re-downloaded.
$MAX_AGE_IN_HOURS = 8;

// Download images. When set to 0 images will be hotlinked. THIS CAN/WILL CAUSE PROBLEMS IF NOT USING A CDN
$DOWNLOAD_IMAGES = 1; 

// Download json. When set to 0 json will be fetched for each view. THIS CAN/WILL CAUSE API PROBLEMS.
// Unused at the moment.
$DOWNLOAD_JSON = 1;

// Socials
$SOCIALS = array(
    // add socials media profile links to this array, keeping the format:
    // "site_name"    =>   "https://full-URL-to/profile",
    "twitter"   => "",
    "bluesky"   => "https://bsky.app/profile/nolageek.bsky.social",
    "lastfm"    => "https://www.last.fm/user/nolageek",
    "github"    => "https://github.com/nolageek",
    "instagram" => "https://instagram.com/queerandloathing",
    "threads"   => "",
    "discogs"   => "https://www.discogs.com/user/nolageek",
    "discord"   => "",
    "facebook"  => "",
    "linkedin"  => "",
    "linktree"  => "https://linktr.ee/queerandloathing",
    "pintrest"  => "",
    "tiktok"  => "",
    "whatsapp"  => "",
    "twitch"  => "",
    "tumblr"  => "",
    "telegram"  => "",
    "snapchat"  => "",
    "youtube"  => "",
    "reddit"  => "http://reddit.com/u/nolageek",
    "spotify"  => "https://open.spotify.com/user/nolageek",
);

// Show different pages, unused at the moment
$SHOW_COLLECTION = 1; // Unused
$SHOW_WANTLIST = 1; // Unused
$SHOW_RANDON_RELEASE = 1; // Unused

