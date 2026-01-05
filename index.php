<?php
require_once('functions.php');

// Run the fetch functions

 if (!empty($PARAMS['debug'])) add_debug("Fetching data.");
fetchUserProfileData($PARAMS['username']);
fetchUserListsData($PARAMS['username'], $DISCOGS_TOKEN);
fetchCollectionData($PARAMS['username'], $DISCOGS_TOKEN, $DATA_FILES['releases']);
fetchCollectionValueData($PARAMS['username'], $DISCOGS_TOKEN, $DATA_FILES['value']);
fetchCustomFieldsData($PARAMS['username'], $DISCOGS_TOKEN);
fetchFolderData($PARAMS['username'], $DISCOGS_TOKEN, $DATA_FILES['folders']);

if ($IS_WANTLIST)
    fetchWantlistData($PARAMS['username'], $DISCOGS_TOKEN, $DATA_FILES['wants']);

if ($PARAMS['release_id']) : 
    $releaseinfo = get_release_information($PARAMS['release_id']);
    $master_id = $releaseinfo['master_id'] ?? "none";
    fetchMusicbrainzReleaseData($master_id,$LOCAL_RELEASE_MUSICBRAINZ_FILE, $LOCAL_RELEASE_COVERARTARCHIVE_FILE);
    $coverImageUrl = get_image_url($PARAMS['release_id']);
endif;

if ($IS_STATISTICS)
    get_statistics();

?>


 
<!DOCTYPE html>

<html lang="en" data-bs-theme="dark">
<head>


<title><?php echo $PARAMS['username']; ?>'s Discogs Collection Page</title>
<link href="./favicon.ico" rel="icon" type="image/x-icon">

<meta name="viewport" content="width=device-width, initial-scale=.8">
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js" ></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<script src="https://kit.fontawesome.com/7e1a0bb728.js" crossorigin="anonymous"></script>
<script src="https://code.jquery.com/jquery-3.7.1.slim.min.js" integrity="sha256-kmHvs0B+OpCW5GVHUNjv9rOmY0IvSIRcf7zGUDTDQM8=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous">
<link href="https://fonts.googleapis.com/css2?family=League+Gothic&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Anton&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">
 
  
<?php if ($IS_STATISTICS): ?>
    <!-- Load Chart.js v3 (Compatible with Radial Gauge) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-chart-treemap"></script>

    <!-- Load Radial Gauge Plugin (Explicitly Defined for Browser Use) -->osw
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-annotation"></script>



    <script>
        $(document).ready(function(){
            $('[data-bs-toggle="tooltip"]').tooltip();   
        });
    </script>

    <script>

        // Define the plugin globally but apply it only to specific charts
        Chart.register({
            id: 'valueOnTop',
            afterDatasetsDraw(chart, args, options) {
                // Check if the plugin is enabled for this chart
                if (!chart.options.plugins.valueOnTop || !chart.options.plugins.valueOnTop.enabled) {
                    return;
                }

                const { ctx, data, scales } = chart;

                ctx.save();
                ctx.font = chart.options.plugins.valueOnTop.font || '12px Arial';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'bottom';

                data.datasets.forEach((dataset, i) => {
                    const meta = chart.getDatasetMeta(i);

                    meta.data.forEach((bar, index) => {
                        const value = dataset.data[index];
                        ctx.fillStyle = chart.options.plugins.valueOnTop.textColor || 'white'; // Scoped text color
                        const textY = Math.max(bar.y - 5, scales.y.top + 10); // Ensure padding from top
                        ctx.fillText(value, bar.x, textY);
                    });
                });

                ctx.restore();
            }
        });
    </script>

    <script>
        $(document).ready(function(){
            $('[data-toggle="tooltip"]').tooltip();   
        });
    </script>

    <script>
  
    const dataBar = {
      type: "bar",
      data: {
        labels: <?php echo json_encode($labels); ?>,
        datasets: [{
          label: <?php echo $title; ?>,
          data: <?php echo json_encode($data); ?>,
          backgroundColor: ["rgba(66,133,244,0.6)", "rgba(66,133,244,0.6)",
            "rgba(66,133,244,0.6)", "rgba(66,133,244,0.6)"
          ],
        }, ],
      },
    };

    new mdb.Chart(document.getElementById(<?php echo $chartId; ?>), dataBar);

        </script>

<?php endif; // END IF $IS_STATISTICS ?>



<style>

body {
  background-color: bg-dark;
}

.bg-accent_color {
  background-color: <?php echo $ACCENT_COLOR; ?>;
}

.text-accent_color {
  color: <?php echo $ACCENT_COLOR; ?>;
}

.text-musicbrainz {
  color: darkviolet !important;
}

.text-master {
  color: gold !important;
}

.fg-limegreen {
  color: #32CD32;
}

.bg-limegreen {
  background-color: #32CD32;
}

.navbar {
  background-color:black;
}
.nav-item {
  font-family: "Roboto", sans-serif;
  font-size: 1em;
  font-weight: 600;
  text-transform: uppercase;
  color: grey;
  margin: 0 .5em;
}

.nav-item .username {
  font-size: 3em;
}

.list-group-item { 
  word-wrap: break-word; 
}

.visible {
  visibility: visible;
}

.invisible {
  visibility: hidden;
}

.none {
  display: none;
}

.blurtop {
  padding: 10px 0;
  background: rgb(0,0,0);
  background: linear-gradient(180deg, rgba(0,0,0,0.7) 21%, rgba(0,0,0,0) 57%);
}
.google-league-gothic {
  font-family: "League Gothic", sans-serif;
  font-weight: 400;
  font-style: normal;
}

.google-anton {
  font-family: "Anton", sans-serif;
  font-weight: 400;
  font-style: normal;
}

.bebas-neue-regular {
  font-family: "Bebas Neue", sans-serif;
  font-weight: 400;
  font-style: normal;
}

.container-fluid {
  margin-right: auto;
  margin-left: auto;
  max-width: 1200px; /* or 950px */
}
.releaseGallery, .statisticsGallery {
  display: grid;
  grid-template-columns:repeat(auto-fit,minmax(15rem, 1fr));
  grid-gap: 2rem;
  padding: .5rem 1rem;
  @media (max-width: 768px) {
    grid-template-columns: repeat(2, 1fr);
  }
}
.passion-one-regular {
  font-family: "Passion One", sans-serif;
  font-weight: 400;
  font-style: normal;
}

.passion-one-bold {
  font-family: "Passion One", sans-serif;
  font-weight: 700;
  font-style: normal;
}

.passion-one-black {
  font-family: "Passion One", sans-serif;
  font-weight: 900;
  font-style: normal;
}
.statisticsGallery {
  grid-template-columns:repeat(auto-fit,minmax(18rem, 1fr));
  @media (max-width: 768px) {
    grid-template-columns: repeat(1, 1fr);
  }
}
.statisticsGalleryItem, .releaseGalleryItem {
  padding: 0;
  max-width: 280px;
}
.navbar .theme .nav {
z-index: 10000;
}
.bi, .fa, h2 .accordion-header, .card-title, .card-subtitle, .track-list-titles {
  color: <?php echo $ACCENT_COLOR; ?>;
}

.card-header {
  color: white;
  font-weight: 700;
}

pre {
    white-space: pre-wrap;       /* Since CSS 2.1 */
    white-space: -moz-pre-wrap;  /* Mozilla, since 1999 */
    white-space: -pre-wrap;      /* Opera 4-6 */
    white-space: -o-pre-wrap;    /* Opera 7 */
    word-wrap: break-word;       /* Internet Explorer 5.5+ */
}
.paginationItem {

}
.card .list-group {
  margin: .65rem 0 0 0;
}
.card-title, .card-subtitle {
  background-color: transparent !important;
  color: white;
  font-family: "Roboto", sans-serif;
  font-size: 1em;
  text-transform: uppercase;
  margin: -.25rem 0 .5rem .5rem;
}

.card-subtitle {
  background-color: transparent !important;
  color: lightgrey;
  font-size: .75em;
  text-transform: uppercase;
}

.card-footer {
  background-color: transparent !important;
  margin: 0;
  padding: 0;
}
.display-release-data-indent {
  padding-left: 3rem;
}
.canvasPieChart {
  height: 350px;
}
#resize {
    font-size: 10px; 
    font-size: 6vw; 

}
.btn-border-right {
  border-right: solid 1px <?php echo $ACCENT_COLOR; ?> !important;
}
a.stretched-link:before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  height: 100%;
  width: 100%;
}

.second {
  /* required */
  position: relative;
  z-index: 1;
}

.format-badge-secondary {
  width: fit-content;
  min-width: 1rem;
  font-size: 0.5rem;
  padding: 0.15rem;
  align-items: center;  
  justify-content: center;
  aspect-ratio: 1 / 1;
  border-radius: 50%;  
}

.format-badge-primary,.btn-group-xs > .btn, .btn-xs {
  font-size: 0.75rem;
  padding: 0.25rem;
  align-items: center;  
  justify-content: center;
}


/* IF SINGLE */

.btn-allmusic, .Discogs, .Pitchfork, .Genius, .Bandcamp,
            .SoundCloud, .YouTube, .homepage, .Wikipedia,
            .iTunes, .Spotify, .Myspace, .Rateyourmusic, .Tidal {
background-color: aqua;
            }

#release-cover {
  transition: all 0.3s ease;
  overflow: hidden;
  flex: 0 0 33.3333%;
  max-width: 33.3333%;
  opacity: 1;
}

.text-col {
  transition: all 0.3s ease;
  flex: 0 0 66.6667%;
  max-width: 66.6667%;
}

/* MOBILE: stack vertically, collapse image height */
@media (max-width: 767.98px) {
  #release-cover {
    flex: none;
    max-width: 100%;
    width: 100%;
    height: auto;
    max-height: 300px;
  }

  .text-col {
    flex: none;
    width: 100%;
    max-width: 100%;
  }
}


</style>
</head>

<body>

<div class="container-fluid"> <!-- Outer Container -->

<nav class="navbar theme rounded-top navbar-expand-lg p-2 mt-2  border-3 border-bottom border-black">
      <div class="container-fluid justify-content-between">

<?php 

     $topReleasesParams = [
         'username' => '',
         'release_id' => '',
         'artist_id' => '0',
         'page' => '0',
         'per_page' => '0',
         'order' => '0',
         'sort_by' => '0',
         'type' => '0'
     ];
     $topWantsParams = [
         'release_id' => '',
         'type' => 'wants',
         'artist_id' => '0',
         'page' => '1'
     ];

     $topStastisticsParams = [
         'release_id' => '',
         'type' => 'statistics',
         'artist_id' => '',
         'page' => '',
         'sort_by' => '',
         'order' => '',
         'per_page' => ''
     ];

     $random_release_id = get_random_release_id($PARAMS['folder_id']);
     $randomReleaseParams = [
         'release_id' => $random_release_id,
         'type' => '',
         'artist_id' => '',
         'folder_id' => '0',
         'page' => '',
         'sort_by' => '',
         'order' => '',
         'per_page' => ''
     ];

     $site_top_random_release_url = build_url("/", set_params($PARAMS, $randomReleaseParams));
     $site_top_release_url = build_url("/", set_params($PARAMS, $topReleasesParams));
     $site_top_wants_url = build_url("/", set_params($PARAMS, $topWantsParams));
     $site_top_stats_url = build_url("/", set_params($PARAMS, $topStastisticsParams));
?>


    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon paginationItem page-link"></span>
        </button>
   
    <div class="">
          <div class="nav-item username"><a class="nav-link" href="<?php echo $site_top_release_url; ?>"><i class="fa fa-fw fa-record-vinyl text-white"></i> / <span class="text-accent_color"><?php echo $PARAMS['username']; ?></span></a></div>
    </div>
        <div class="collapse navbar-collapse m-auto " id="navbarMain">
          <ul class="navbar-nav m-auto">

          <li class="nav-item">
              <a class="nav-link" href="<?php echo $site_top_release_url; ?>" title="Stuff I Have">Collection<span class="badge badge-danger"><?php echo $userProfile['num_collection']; ?></span></a>
            </li>

            <li class="nav-item">
              <a class="nav-link" href="<?php echo $site_top_wants_url; ?>"  title="Stuff I Want">Wantlist<span class="badge badge-danger"><?php echo $userProfile['num_wantlist']; ?></span></a>
            </li>

            <li class="nav-item">
              <a class="nav-link" href="<?php echo $site_top_stats_url; ?>" title="Statistics">Statistics</a>
            </li>
        
    <!-- Add a Random Button -->
    <li class="nav-item">
        <a class="nav-link" href="<?php echo $site_top_random_release_url; ?>" title="Random Release">Random Release</a>
    </li>
    <!-- End of Random Button-->

        </ul>

          <div class="btn-toolbar my-2" role="toolbar" aria-label="Items per page">
            <div class="input-group input-group-sm" role="group" aria-label="Search Form">

            <input type="text" id="searchInput" class="form-control w-10" placeholder="Search All">
        </div> <!-- End Search Form -->
    </div>

        </div>




      </div>

    </nav>


<!-- Pagination / Nav / Filter Bar-->
<nav class="navbar bg-dark sticky-top p-0 border-5 border-bottom border-black">
   
<?php if (!$IS_SINGLE && !$IS_STATISTICS) { ?>
 
   <?php 
      $next_page = '';
      $prev_page = ''; 

      if ($PARAMS['page'] != 1)
         $prev_page = (intval($PARAMS['page']) - 1); 
      if ($PARAMS['page'] == 1 || $PARAMS['page'] == $total_pages)
         $is_disabled = " disabled";
      if ($PARAMS['page'] != $total_pages)
         $next_page = (intval($PARAMS['page']) + 1); 
    ?>


<div class="col-md-6 col-12">
   <!-- Start Pagination Navigation --> 

   <div class="btn-toolbar" role="toolbar" aria-label="Pagination Navigation">
      <div class="mx-auto" role="group">
         <ul class="pagination my-2">
            <li class="page-item">
               <a class="page-link paginationItem<?php if ($PARAMS['page'] == 1)
                  echo " disabled"; ?>" href="<?php echo build_url("/", set_param($PARAMS, 'page', $prev_page)); ?>" tabindex="-1"><i class="fa fa-fw fa-arrow-left"></i>
               </a>
            </li>
            <?php echo paginate($PARAMS['page'], $total_pages, 6); ?>
            <li class="page-item">
               <a class="page-link paginationItem<?php if ($PARAMS['page'] == $total_pages)
                  echo " disabled"; ?>" href="<?php echo build_url("/", set_param($PARAMS, 'page', $next_page)); ?>" tabindex="-1"><i class="fa fa-fw fa-arrow-right"></i>
               </a>
            </li>
         </ul>
      </div>
   </div>
</div>
<!-- End Pagination Navigation -->
<?php } ?>

 







<?php if ($IS_SINGLE): ?>
<!-- BEGIN SINGLE RELEASE -->

<div class="container-fluid" id="release-header">
  <div class="d-flex flex-column flex-md-row w-100">

    <!-- Image Column -->
    <div id="release-cover" class="d-flex justify-content-center align-items-center">
      <img src="<?php echo $IMAGE_PATH_ROOT_URL_PREFIX_300 . $coverImageUrl; ?>" 
           class="img-fluid rounded p-2"
           alt="<?php echo $releaseinfo['title']. ' by ' . $releaseinfo['artists_plain']; ?>">
    </div>

    <!-- Text Column -->
    <div class="text-col bg-dark d-flex align-items-stretch justify-content-center mb-3">
      <div class="d-flex flex-column justify-content-center h-100">
        <div class="google-league-gothic text-uppercase d-flex align-items-end text-accent_color display-4">
          <?php echo $releaseinfo['title']; ?>
        </div>
        <div class="display-5 google-league-gothic text-muted">
          by <span class="text-uppercase"><?php echo $releaseinfo['artists_plain']; ?></span>
        </div>
      </div>
    </div>

  </div>
</div>
<!-- END SINGLE RELEASE -->
<?php endif; ?>





<div class="d-flex justify-content-center mx-auto col-md-6 col-12">

<?php if (!$IS_STATISTICS) { ?> 
 
  <div class="btn-group" role="group" aria-label="Items per page">
 
      <button class="btn btn-dark rounded-start text-uppercase text-muted dropdown-toggle"  type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="fa fa-fw fa-copy"></i> <?php echo $PARAMS['per_page']; ?></button>
      <ul class="dropdown-menu">
          <li><h6 class="dropdown-header">Items Per-Page</h6></li>	
          <li><a href="<?php echo build_url("/", set_param($PARAMS, 'per_page', 24)); ?>" class="dropdown-item text-capitalize<?php if ($PARAMS['per_page'] == 24)
                    echo ' bg-secondary'; ?>"><i class="fa fa-fw fa-copy"></i> 24 Per-Page</a></li>
          <li><a href="<?php echo build_url("/", set_param($PARAMS, 'per_page', 48)); ?>" class="dropdown-item text-capitalize<?php if ($PARAMS['per_page'] == 48)
                    echo ' bg-secondary'; ?>"><i class="fa fa-fw fa-copy"></i> 48 Per-Page</a></li>
            <li><a href="<?php echo build_url("/", set_param($PARAMS, 'per_page', 96)); ?>" class="dropdown-item text-capitalize<?php if ($PARAMS['per_page'] == 96)
                      echo ' bg-secondary'; ?>"><i class="fa fa-fw fa-copy"></i> 96 Per-Page</a></li>
      </ul>




      <?php if ($IS_RELEASES) { ?>

          <button class="btn btn-dark rounded-0 text-muted text-uppercase dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="fa fa-fw fa-folder-open"></i> <?php echo $current_folder_name; ?>
            </button>
          <ul class="dropdown-menu">
          <li><h6 class="dropdown-header">Available Folders</h6></li>	
          <?php foreach ($folders['folders'] as $folder) {

              $folderid = $folder['id'];
              $foldername = $folder['name'];
              $foldercount = $folder['count'];

              if ($foldercount > 1) { ?>

                <li><a href="<?php echo build_url("/", set_param($PARAMS, 'folder_id', $folderid)); ?>" title="View Folder '<?php echo $foldername; ?>'" class="dropdown-item text-capitalize<?php if ($current_folder_name == $foldername) {
                             echo ' bg-secondary';
                         } ?>"><i class="fa fa-fw fa-folder"></i> <?php echo $foldername; ?> <span class="badge badge-primary"><?php echo $foldercount; ?></span></a></li>

            <?php }
          } ?>
            </ul>

    <?php } ?>


    <?php


    $sort_type = array('added', 'artist', 'title', 'year');
    $sorted_icon = "";

          if ($PARAMS['sort_by'] == 'added') {
              $sorted_icon = "fa fa-fw fa-clock-rotate-left";
          }
          if ($PARAMS['sort_by'] == 'artist') {
              $sorted_icon = "fa fa-fw fa-person";
          }
          if ($PARAMS['sort_by'] == 'title') {
              $sorted_icon = "fa fa-fw fa-quote-right";
          }
          if ($PARAMS['sort_by'] == 'year') {
              $sorted_icon = "fa fa-fw fa-calendar";
          }
          ?>
          <button class="btn btn-dark rounded-0 text-uppercase text-muted dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="<?PHP echo $sorted_icon; ?>"></i> <?PHP echo $PARAMS['sort_by']; ?>
        </button>
      <ul class="dropdown-menu">
      <li><h6 class="dropdown-header">Sort-by Options</h6></li>	
    <?PHP foreach ($sort_type as $sortby) {
        $sorted_icon = "";
        if ($sortby == 'added') {
            $sorted_icon = "fa fa-fw fa-clock-rotate-left";
        }
        if ($sortby == 'artist') {
            $sorted_icon = "fa fa-fw fa-person";
        }
        if ($sortby == 'title') {
            $sorted_icon = "fa fa-fw fa-quote-left";
        }
        if ($sortby == 'year') {
            $sorted_icon = "fa fa-fw fa-calendar";
        }

        ?>
            <li><a href="<?php echo build_url("/", set_param($PARAMS, 'sort_by', $sortby)); ?>" title="Sort by <?PHP echo $sortby; ?>" class="btn btn-dark dropdown-item text-muted text-capitalize<?php if ($sortby == $PARAMS['sort_by'])
                         echo " bg-secondary"; ?>"><i class="<?PHP echo $sorted_icon; ?>"></i> <?PHP echo $sortby; ?></a></li>
        <?PHP } ?>
      </ul>

          <?PHP

          if ($PARAMS['sort_by'] == 'added' || $PARAMS['sort_by'] == 'year') {
              $asc_icon = " fa-arrow-down-1-9";
              $desc_icon = " fa-arrow-down-9-1";
          } elseif ($PARAMS['sort_by'] == 'artist' || $PARAMS['sort_by'] == 'title') {
              $asc_icon = " fa-arrow-down-z-a";
              $desc_icon = " fa-arrow-down-a-z";
          }

          $order_option = "asc";
          $order_icon = $asc_icon;
          if ($PARAMS['order'] == "asc") {
              $order_option = "desc";
              $order_icon = $desc_icon;
          } ?>
        <a href="<?php echo build_url("/", set_param($PARAMS, 'order', $order_option)); ?>" title="Toggle Sort: Ascending/Descending" role="button"  class="btn btn-dark rounded-end text-muted text-uppercase<?php if ($order_option == $PARAMS['order'])
                   echo " bg-secondary"; ?>"><i class="fa fa-fw <?php echo $order_icon; ?>"></i> <?php echo $PARAMS['order'] ?></a>
                   

    </div>
<?php } ?>
</div>
</nav> <!-- Pagination / Nav / Filter Bar-->


<?php $isReleaseGalleryClass = ($IS_RELEASE_GALLERY) ? ' releaseGallery' : ''; ?>
<?php $isStatisticsGalleryClass = ($IS_STATISTICS) ? ' statisticsGallery' : ''; ?>

<div id="searchResults" class="row bg-dark container-fluid<?php echo $isReleaseGalleryClass . $isStatisticsGalleryClass; ?>"></div>

<div id="releaseGallery" class="row bg-dark container-fluid<?php echo $isReleaseGalleryClass . $isStatisticsGalleryClass; ?>"> <!-- Gallery of Releases -->
  
<?php if ($IS_SINGLE) {
    ?>
          <?php
          echo "<!-- Single Release for {$PARAMS['release_id']}-->";

          display_release_data($releaseinfo);
      
          echo "<!-- End Single Release for {$PARAMS['release_id']}-->";
} else {

    if ($IS_STATISTICS):
        // Load the statistics data and generate a chart
        $statistics_data = load_statistics_data($DATA_FILES['statistics']);
        $value_data = load_statistics_data($DATA_FILES['value']);
        $folder_data = load_statistics_data($DATA_FILES['folders']);

        echo SimpleDataItemCard($statistics_data['total_releases'], 'Total Releases', );

        echo SimpleDataItemCard($statistics_data['unique_artists'], 'Unique Artists', );

        echo SimpleDataItemCard($statistics_data['unique_genres'], 'Unique Genres', );

        //echo SimpleDataItemCard($statistics_data['average_release_year'], 'Average Release Year', );

        [$labels, $data] = extract_labels_and_data($statistics_data['most_common_release_year']);
        echo SimpleDataArrayCard($labels, $data, 'Most Common Release Year');

        [$labels, $data] = extract_labels_and_data($statistics_data['total_value']);
        echo createRadialGaugeChartCard('total_value', $labels, $data, "Total Value Gauge");

        //[$labels, $data] = extract_labels_and_data($folder_data['folders']);
        //echo createPieChartCard('folders', $labels, $data, 'Folders');

        [$labels, $data] = extract_labels_and_data($statistics_data['releases_added_per_year']);
        echo createBarChartCard('PickupsPerYearChart', $labels, $data, 'Purchases Per Year');

        [$labels, $data] = extract_labels_and_data($statistics_data['most_common_format']);
        echo createPieChartCard('most_common_format', $labels, $data, 'Most Common Formats');

        [$labels, $data] = extract_labels_and_data($statistics_data['top_5_genres']);
        echo createPieChartCard('top_5_genres', $labels, $data, 'Top 5: Genres');

        //[$labels, $data] = extract_labels_and_data($statistics_data['total_value']);
        //echo createBarChartCard('total_value', $labels, $data, 'Total Value');

        [$labels, $data] = extract_labels_and_data($statistics_data['top_5_artists']);
        echo createPieChartCard('top_5_artists', $labels, $data, 'Top 5: Artists');

        [$labels, $data] = extract_labels_and_data($statistics_data['releases_per_artist']);
        echo createTreemapChartCard('releases_per_artist', $labels, $data, 'Releases Per Artist');

        [$labels, $data] = extract_labels_and_data($statistics_data['releases_per_genre']);
        echo createTreemapChartCard('releases_per_genre', $labels, $data, 'Releases Per Genre');

        [$labels, $data] = extract_labels_and_data($statistics_data['releases_per_style']);
        echo createTreemapChartCard('releases_per_style', $labels, $data, 'Releases Per Style');

    else:

        foreach ($collection as $release) {
            $release_id = $release['basic_information']['id'];
            $data_file =  $LOCAL_RELEASES_DATA_ROOT . $release_id . '.json';
            get_release_information($release_id, 0);
            display_gallery_item($release);
        }
    endif;
}

?>


</div> <!-- Gallery of Releases End -->

<!-- Remove the container if you want to extend the Footer to full width. -->
    


  <!-- Footer -->
  <div class="row bg-dark py-4 my-4 border-top container-fluid">

  <div class="col-md-4 col-sm-6 my-3">
  <div class="accordion" id="accordionCollapseCollectionStats">
  <div class="accordion-item mb-3">
    <h2 class="accordion-header font-weight-bold" id="headingCollectionStats">
      <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCollectionStats" aria-expanded="true" aria-controls="collapseCollectionStats">
      <strong>Collection Stats</strong>
      </button>
    </h2>
    <div id="collapseCollectionStats" class="accordion-collapse collapse show" aria-labelledby="headingCollectionStats" data-bs-parent="#accordionCollapseCollectionStats">
      <div class="card accordion-body">

  <ul class="list-group cardReleaseData <?php echo css_is_hidden("releases"); ?>">
  <li class="card-header list-group-item list-group-item-action">Collection Totals</li>
  <?php $statistics = load_statistics_data($DATA_FILES['statistics']) ?>
  <li class="list-group-item list-group-item-action"><?php echo "<strong>Releases: </strong>" . $statistics['total_releases'] . ", <strong>Artists: </strong>" . $statistics['unique_artists'] . ", <strong>Genres: </strong>" . $statistics['unique_genres'] . "</li>"; ?>
  </ul>

  
  </div>
  </div>
</div>
</div>




  <div class="accordion" id="accordionCollapseCacheData">
  <div class="accordion-item">
    <h2 class="accordion-header font-weight-bold" id="headingCacheData">
      <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCacheData" aria-expanded="true" aria-controls="collapseCacheData">
      <strong>Cache File Ages</strong>
      </button>
    </h2>
    <div id="collapseCacheData" class="accordion-collapse collapse hide" aria-labelledby="headingCacheData" data-bs-parent="#accordionCollapseCacheData">
      <div class="card accordion-body">


  <ul class="list-group cardReleaseData <?php echo css_is_hidden("releases"); ?>">
  <li class="card-header list-group-item list-group-item-action">Releases Cache Age</li>
  <li class="list-group-item list-group-item-action"><?php echo get_age_of($DATA_FILES['releases'], 1) ?></li>
  </ul>

  <ul class="list-group cardReleaseData <?php echo css_is_hidden("single"); ?>">
  <li class="card-header list-group-item list-group-item-action">Current Release Cache Age</li>
  <li class="list-group-item list-group-item-action"><?php echo get_age_of($DATA_FILES['release'], 1) ?></li>
  </ul>

  <ul class="list-group cardReleaseData <?php echo css_is_hidden("single"); ?>">
  <li class="card-header list-group-item list-group-item-action">MusicBrainz Cache Age</li>
  <li class="list-group-item list-group-item-action"><?php echo get_age_of($DATA_FILES['musicbrainz'], 1) ?></li>
  </ul>

  <ul class="list-group cardReleaseData <?php echo css_is_hidden("wants"); ?>">
  <li class="card-header list-group-item list-group-item-action">Wantlist Cache Age</li>
  <li class="list-group-item list-group-item-action"><?php echo get_age_of($DATA_FILES['wants'], 1) ?></li>
  </ul>

  <ul class="list-group cardReleaseData <?php echo css_is_hidden("statistics"); ?>">
  <li class="card-header list-group-item list-group-item-action">Statistics Cache Age</li>
  <li class="list-group-item list-group-item-action"><?php echo get_age_of($DATA_FILES['statistics'], 1) ?></li>
  </ul>

  <ul class="list-group cardReleaseData <?php echo css_is_hidden("all"); ?>">
  <li class="card-header list-group-item list-group-item-action">Folders Cache Age</li>
  <li class="list-group-item list-group-item-action"><?php echo get_age_of($DATA_FILES['folders'], 1) ?></li>
  </ul>

  <ul class="list-group cardReleaseData <?php echo css_is_hidden("all"); ?>">
  <li class="card-header list-group-item list-group-item-action">User Cache Age</li>
  <li class="list-group-item list-group-item-action"><?php echo get_age_of($DATA_FILES['profile'], 1) ?></li>
  </ul>

  </div>
  </div>
</div>
</div>
  </div>







                <div class="col-sm-6 col-md-8 my-3">
                <div class="accordion" id="accordionCollapseUserBio">
  <div class="accordion-item">
    <h2 class="accordion-header font-weight-bold" id="headingUserBio">
      <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseUserBio" aria-expanded="true" aria-controls="collapseUserBio">
      <strong><?php echo $PARAMS['username'] ?>'s Collection</strong>
      </button>
    </h2>
    <div id="collapseUserBio" class="accordion-collapse collapse show" aria-labelledby="headingUserBio" data-bs-parent="#accordionCollapseUserBio">
      <div class="card accordion-body">


      <div class="card">
      <div class="card-header"><?php echo $PARAMS['username'] ?>'s Collection</div>
      <div class="card-body"><?php echo $user_bio ?></div>
      <div class="card-footer"><a href="#" class="btn btn-small m-3 btn-primary">Back To Top</a></div>
      </div>


      


      
    </div>
  </div>

</div>

</div>
</div>
</div>



    <div class="d-flex  justify-content-start py-4 my-4 border-top">
    <?php display_socials($SOCIALS); ?>
    </div>

<?php

/*
// Set default values for rows and cols
$defaultRows = $_POST['rows'] ?? 3; // Default to 3 rows if no input is provided
$defaultCols = $_POST['cols'] ?? 3; // Default to 3 columns if no input is provided


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form inputs
    $rows = (int) $_POST['rows'];
    $cols = (int) $_POST['cols'];

    // Validate inputs
    if ($rows < 1 || $rows > 10 || $cols < 1 || $cols > 10) {
        die('Invalid grid size. Rows and columns must be between 1 and 10.');
    }

    // Define the grid size and number of albums to fetch
    $gridSize = "{$rows}x{$cols}";
    $numberOfAlbums = $rows * $cols;

    // Load the collection data
    $collection = json_decode(file_get_contents($DATA_FILES['releases']), true)['releases'];

    // Generate the album art grid image
    $outputFile = "albums_art_grid.png"; // You can change this to a unique name if needed
    $generatedFile = createAlbumArtGridImage($collection, $numberOfAlbums, $gridSize, $outputFile);

    // Display the result
    if ($generatedFile) {
        echo "<h1>Album Art Grid Generated</h1>";
        echo "<p>Your album art grid has been successfully created. Click the link below to view or download it:</p>";
        echo "<a href='$generatedFile' target='_blank'>View Album Art Grid</a>";
        echo "<br>";
        echo "<img src='$generatedFile' alt='Album Art Grid' style='max-width:100%; height:auto;'>";
    } else {
        echo "<h1>Error</h1>";
        echo "<p>Failed to generate the album art grid. Please try again.</p>";
    }
    exit();
}

// Default values for the form

/*
// Set default values
$defaultRows = $_POST['rows'] ?? 3;
$defaultCols = $_POST['cols'] ?? 3;
$defaultCoverSize = $_POST['cover_size'] ?? 200;
$defaultWidth = $_POST['width'] ?? $defaultCols * $defaultCoverSize; // Default width is columns * cover size
$defaultHeight = $defaultRows * $defaultCoverSize; // Default height calculation
*/
?>

<!--

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Album Art Grid Generator</title>
    <script>
        function updateValues(changedField) {
            let rows = parseInt(document.getElementById("rows").value) || 1;
            let cols = parseInt(document.getElementById("cols").value) || 1;
            let coverSize = parseInt(document.getElementById("cover_size").value) || 200;
            let width = parseInt(document.getElementById("width").value) || cols * coverSize;

            if (changedField === 'cover_size') {
                width = cols * coverSize;
            } else if (changedField === 'width') {
                coverSize = Math.floor(width / cols);
            } else if (changedField === 'cols') {
                width = cols * coverSize;
            }

            let height = rows * coverSize;

            // Update form values
            document.getElementById("cover_size").value = coverSize;
            document.getElementById("width").value = width;
            document.getElementById("height").value = height;
        }
    </script>
</head>
<body>
    <h1>Generate Album Art Grid</h1>
    <form method="POST" action="">
        <label for="rows">Number of Rows (X):</label>
        <input type="number" id="rows" name="rows" min="1" max="10" value="<?php // echo htmlspecialchars($defaultRows); ?>" required oninput="updateValues('rows')">
        <br>

        <label for="cols">Number of Columns (Y):</label>
        <input type="number" id="cols" name="cols" min="1" max="10" value="<?php // echo htmlspecialchars($defaultCols); ?>" required oninput="updateValues('cols')">
        <br>

        <label for="cover_size">Cover Image Size (pixels):</label>
        <input type="number" id="cover_size" name="cover_size" min="50" max="500" value="<?php // echo htmlspecialchars($defaultCoverSize); ?>" required oninput="updateValues('cover_size')">
        <br>

        <label for="width">Total Width of Image (pixels):</label>
        <input type="number" id="width" name="width" min="300" max="2000" value="<?php // echo htmlspecialchars($defaultWidth); ?>" required oninput="updateValues('width')">
        <br>

        <label for="height">Total Height (pixels):</label>
        <input type="number" id="height" name="height" value="<?php // echo htmlspecialchars($defaultHeight); ?>" readonly>
        <br>

        <button type="submit">Generate Grid</button>
    </form>
</body>
</html>

-->


    
<!-- End of .container -->
  </div> <!-- Outer Container End -->

<!-- Add this before the closing </body> tag -->

<script src="https://code.jquery.com/jquery-3.6.0.min.js" crossorigin="anonymous"></script>

<script>
  document.addEventListener("DOMContentLoaded", function () {
    const releaseCover = document.getElementById('release-cover');
    const textCol = document.querySelector('.text-col');
    const collapseDistance = 200;
    const desktop = window.matchMedia("(min-width: 768px)");

    window.addEventListener('scroll', function () {
      const scrollY = window.scrollY;
      const progress = Math.min(scrollY / collapseDistance, 1);

      if (desktop.matches) {
        // DESKTOP: collapse left-to-right
        const coverWidth = 33.3333 * (1 - progress);
        const textWidth = 100 - coverWidth;

        releaseCover.style.flex = `0 0 ${coverWidth}%`;
        releaseCover.style.maxWidth = `${coverWidth}%`;
        releaseCover.style.opacity = `${1 - progress}`;

        textCol.style.flex = `0 0 ${textWidth}%`;
        textCol.style.maxWidth = `${textWidth}%`;
      } else {
        // MOBILE: collapse top-to-bottom
        const maxHeight = 300;
        releaseCover.style.maxHeight = `${maxHeight * (1 - progress)}px`;
        releaseCover.style.opacity = `${1 - progress}`;
      }
    });
  });
</script>




<?php if ($IS_STATISTICS): ?>
    <script>
            // Get data from PHP
            const labels = <?php echo $labels; ?>;
            const values = <?php echo $values; ?>;

            // Create the Chart.js lollipop chart (bar + line chart)
            const ctx = document.getElementById('recordsLollipopChart').getContext('2d');
            const recordsLollipopChart = new Chart(ctx, {
                type: 'bar', // Start with bar chart
                data: {
                    labels: labels,
                    datasets: [{
                      type: 'line', // Lollipop line
                        label: 'Records Added',
                        data: values,
                        borderColor: 'rgba(54, 162, 235, 1)',
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        fill: false,
                        pointRadius: 10,
                        pointBackgroundColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 0
                    },
                    {
                        type: 'bar', // Bar chart for each month
                        label: 'Records Added',
                        data: values,
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 25,
                        categoryPercentage: 0.1, // Percentage of available width for each category (50% of the total space)
                        barPercentage: 0.1, // Percentage of the category space that the bar should take up
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Records'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Month'
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false // Hide legend if unnecessary
                        }
                    }
                }

            });
        </script>
<?php endif; // IF $IS_STATISTICS ?>
<script>
$(document).ready(function(){
    function fetchData() {
        var searchTerm = $("#searchInput").val().toLowerCase();
        var releaseGalleryDiv = $("#releaseGallery");
        var searchResultsDiv = $("#searchResults");

        var url = '<?php echo $DATA_FILES['releases']; ?>';

        releaseGalleryDiv.toggle(!searchTerm);  // Hide releaseGalleryDiv if searchTerm is not empty

        searchResultsDiv.empty();

        console.log("Search term:", searchTerm);

        if (searchTerm) {
            $.getJSON(url, function(data) {
                var filteredReleases = data.releases.filter(function(release) {
                    var title = release.basic_information.title.toLowerCase();
                    var artists = release.basic_information.artists.map(function(artist) {
                        return artist.name.toLowerCase();
                    });
                    var genres = release.basic_information.genres.map(function(genre) {
                        return genre.toLowerCase();
                    });
                    var styles = release.basic_information.styles.map(function(style) {
                        return style.toLowerCase();
                    });

                    console.log("Title:", title);
                    console.log("Artists:", artists);
                    console.log("Genres:", genres);
                    console.log("Styles:", styles);

                    // Check if the entire search term is found in the title or any artist name
                    var foundIn = new Set();  // Using Set to automatically remove duplicates
                    if (title.includes(searchTerm)) {
                        foundIn.add("Title");
                    }
                    artists.forEach(function(artist) {
                        if (artist.includes(searchTerm)) {
                            foundIn.add("Artist");
                        }
                    });
                    genres.forEach(function(genre) {
                        if (genre.includes(searchTerm)) {
                            foundIn.add("Genre");
                        }
                    });
                    styles.forEach(function(style) {
                        if (style.includes(searchTerm)) {
                            foundIn.add("Style");
                        }
                    });

                    // Convert Set back to an array
                    release.foundIn = Array.from(foundIn);

                    return foundIn.size > 0 ? true : false;
                });

                if (filteredReleases.length === 0) {
                    searchResultsDiv.append("<div class='container-fluid bg-error'><div class='row'><div class='mx-auto'>No results found.</div></div></div>");
                } else {
                    filteredReleases.forEach(function(release) {
                      var releaseHtml = '<!-- Gallery item -->';           
                        releaseHtml += '<div class="releaseGalleryItem">';
                        releaseHtml += '<div class="card bg-dark text-white">';
                        releaseHtml += '<img src="<?php echo $IMAGE_PATH_ROOT_URL; ?>' + release.id + '.jpeg<?php echo $IMAGE_PATH_ROOT_URL_SUFFIX; ?>" class="card-img" alt="' + release.basic_information.title + '">';
                        releaseHtml += '<a href="/?release_id=' + release.id + '"  title="' + release.basic_information.title + ' by ' + release.basic_information.artists.map(function(artist) { return artist.name; }).join(", ") + '">';
                        releaseHtml += '<div class="card-img-overlay blurtop">';
                        releaseHtml += '<h5 class="card-title google-league-gothic">' + release.basic_information.title + '</h5>';
                        releaseHtml += '<h5 class="card-subtitle text-muted google-league-gothic">' + release.basic_information.artists.map(function(artist) { return artist.name; }).join(", ") + '</h5>';
                        releaseHtml += '</div>';
                        releaseHtml += '</a>';
                        if (release.foundIn) {
                                          releaseHtml += '<div class="card-footer"><small><i class="fa fa-fw fa-search"></i> <span class="badge bg-info ms-2 px-2 rounded-pill">' + release.foundIn.join(", ") + '<span></small></div>';
                        }
                        releaseHtml += '</div>';
                        releaseHtml += '</div>';
                        searchResultsDiv.append(releaseHtml);
                    });
                }
            });
        }
    }

    // Call fetchData() when the search input changes
    $("#searchInput").on("keyup", fetchData);

    // Call fetchData() when the checkbox state changes
    $("#SearchAllCheckbox").change(fetchData);

    // Call fetchData() initially to fetch the initial data based on the initial state of the checkbox
    fetchData();
    });



    </script>


</body>
</html>
