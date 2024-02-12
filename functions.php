<?php

error_reporting(E_ALL);
ini_set('display_errors', 'On');

require('settings.php');

// DEFAULT VALUES FOR ATTRIBUTES
$folder_id = "0";
$sort_by = "added";
$order = "desc";
$artistid = ""; // Currently not used
$page = "1";
$per_page = "25";
$release_id = "";


// GET ATTRIBUTES FROM URL

// GET ATTRIBUTES FROM URL
if (isset($_GET['folder_id'])) $folder_id = $_GET['folder_id'];
if (isset($_GET['sort_by'])) $sort_by = $_GET['sort_by'];
if (isset($_GET['order'])) $order = $_GET['order'];
if (isset($_GET['page'])) $page = $_GET['page'];
if (isset($_GET['artistid'])) $artistid = $_GET['artistid']; // Currently not used
if (isset($_GET['per_page'])) $per_page = $_GET['per_page'];
if (isset($_GET['releaseid'])) $release_id = $_GET['releaseid'];

$options  = array('http' => array('user_agent' => 'DiscogsCollectionPage'));
$context  = stream_context_create($options);

// GET FOLDER DATA FOR NAVIGATION BAR
$folderjson = $DISCOGS_API_URL
	. "/users/"
	. $DISCOGS_USERNAME
	. "/collection/folders?token="
	. $DISCOGS_TOKEN;
// put the contents of the file into a variable
$folderdata = file_get_contents($folderjson, false, $context); 
$folders = json_decode($folderdata,true); // decode the JSON feed

// Get name, ID and number of items of current folder.
foreach ($folders['folders'] as $folder) { 
	if ($folder['id'] == $folder_id) {
		$current_folder_name = $folder['name'];
		$current_folder_count = $folder['count'];
		}
	if ($folder['id'] == 0) {
		$total_collection_count = $folder['count'];
		}
	} 


$DISCOGS_CACHE_FILE = 'collection.json';


function get_collection_cached() {
	
	global $DISCOGS_API_URL, $DISCOGS_USERNAME, $DISCOGS_CACHE_FILE, $folder_id, $sort_by, $order, $page;
	global $per_page, $DISCOGS_TOKEN, $context;
	$pagejson = $DISCOGS_API_URL 
	. "/users/"
	. $DISCOGS_USERNAME
	. "/collection/folders/"
	. $folder_id
	. "/releases?sort="
	. $sort_by
	. "&sort_order="
	. $order
	. "&page="
	. $page
	. "&per_page="
	. $per_page
	. "&token="
	. $DISCOGS_TOKEN;
	// put the contents of the JSON into a variable
		$pagedata = file_get_contents($pagejson, false, $context); 
		file_put_contents($DISCOGS_CACHE_FILE, $pagedata);
	// decode the JSON feed;
	}  

	function get_folder_cached($per_page) {
	
		global $DISCOGS_API_URL, $DISCOGS_USERNAME, $DISCOGS_CACHE_FILE, $folder_id, $sort_by, $order, $page;
		global $per_page, $DISCOGS_TOKEN, $context;
		$pagejson = $DISCOGS_API_URL 
		. "/users/"
		. $DISCOGS_USERNAME
		. "/collection/folders/"
		. $folder_id
		. "/releases?sort="
		. $sort_by
		. "&sort_order="
		. $order
		. "&page="
		. $page
		. "&per_page="
		. $per_page
		. "&token="
		. $DISCOGS_TOKEN;
		// put the contents of the JSON into a variable
			$pagedata = file_get_contents($pagejson, false, $context); 
			file_put_contents($DISCOGS_CACHE_FILE, $pagedata);
		// decode the JSON feed;
		} 

	#get_collection_cached();
#if (time()-filemtime($DISCOGS_CACHE_FILE) > 24 * 3600) {
		// file older than 24 hours
#	get_collection_cached();
#} else {
	// file younger than 24 hours
#	print "//* JSON less than 24 hours old *//";
#  }

function get_collection() {
	global $DISCOGS_API_URL, $DISCOGS_USERNAME, $DISCOGS_CACHE_FILE, $folder_id, $sort_by, $order, $page;
	global $per_page, $DISCOGS_TOKEN, $context;
		$pagejson = $DISCOGS_API_URL 
		. "/users/"
		. $DISCOGS_USERNAME
		. "/collection/folders/"
		. $folder_id
		. "/releases?sort="
		. $sort_by
		. "&sort_order="
		. $order
		. "&page="
		. $page
		. "&per_page="
		. $per_page
		. "&token="
		. $DISCOGS_TOKEN;
	// put the contents of the JSON into a variable
	#$pagedata = file_get_contents($DISCOGS_CACHE_FILE, false, $context); 
	$pagedata = file_get_contents($pagejson, false, $context);
	
	// decode the JSON feed
	$collection = json_decode($pagedata,true); 
	return $collection;
}

function get_random_release() {
	global $DISCOGS_API_URL, $DISCOGS_USERNAME, $DISCOGS_CACHE_FILE, $folder_id, $sort_by, $order;
	global $per_page, $DISCOGS_TOKEN, $context, $current_folder_count;
		$pagejson = $DISCOGS_API_URL 
		. "/users/"
		. $DISCOGS_USERNAME
		. "/collection/folders/"
		. $folder_id
		. "/releases?sort="
		. $sort_by
		. "&sort_order="
		. $order
		. "&page="
		. rand(1, $current_folder_count)
		. "&per_page=1"
		. "&token="
		. $DISCOGS_TOKEN;
	// put the contents of the JSON into a variable
	$pagedata = file_get_contents($pagejson, false, $context);
	// decode the JSON feed
	$collection = json_decode($pagedata,true); 
	return $collection;
}

function get_release_information($release_id) {
	global $DISCOGS_API_URL, $DISCOGS_USERNAME,$DISCOGS_TOKEN, $context, $DISCOGS_CACHE_FILE;
		// PULL DISCOGS REGARDING THE RELEASE IN MY COLLECTION
	$releasejson = $DISCOGS_API_URL 
		. "/releases/" 
		. $release_id 
		. "?token=" .$DISCOGS_TOKEN;
	// put the contents of the JSON into a variable
	$releasedata = file_get_contents($releasejson, false, $context); 
	// decode the JSON feed
	$releaseinfo = json_decode($releasedata,true); 
	return $releaseinfo;
}
	
function get_my_release_information($release_id) {
	global $DISCOGS_API_URL, $DISCOGS_USERNAME,$DISCOGS_TOKEN, $context;
	// PULL MY DATA REGARDING THE RELEASE IN MY COLLECTION
	$myreleasejson = $DISCOGS_API_URL 
		. "/users/" 
		. $DISCOGS_USERNAME 
		. "/collection/releases/" 
		. $release_id 
		. "?token=" 
		. $DISCOGS_TOKEN;
	// put the contents of the JSON into a variable
	$myreleasedata = file_get_contents($myreleasejson, false, $context); 
	// decode the JSON feed
	$myreleaseinfo = json_decode($myreleasedata,true);
	return $myreleaseinfo;
}

// IF THIS IS A SINGLE RELEASE VIEW, GET INFORMATION FROM RELEASE AND FROM USER COLLECTION FOR THAT RELEASE
if ($release_id) {
	if ($release_id == "random") {
	$random_release = get_random_release();
	$release_id = $random_release['releases'][0]['basic_information']['id'];
	}
	
    //get_release_information($release_id);
	$releaseinfo = get_release_information($release_id);
	$myreleaseinfo = get_my_release_information($release_id);

// IF NOT A SINGLE RELEASE VIEW, GET DATA FOR USER'S COLLECTION TO DISPLAY COVER GALLERY.
} else {
	// PULL DISCOGS DATA REGARDING MY COLLECTION
	$collection = get_collection();
}


function paginate($current, $last, $url, $max = null) {
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
        $html .= "<a class='btn btn-primary text-uppercase' href='" . $url . "1'>1</a><span class='btn btn-primary text-uppercase text-muted px-0'>.</span>";
    }

    for ($i = $start; $i <= $end; $i++) {
        $html .= ($i == $current) ? "<a class='btn btn-primary text-uppercase disabled'>$i</a>" : "<a class='btn btn-primary text-uppercase' href='$url$i'>$i</a>";
    }

    if ($ellipsisEnd) {
        $html .= "<span class='btn btn-primary text-uppercase text-muted px-0'>.</span><a class='btn btn-primary text-uppercase' href='$url$last'>$last</a>";
    }

    return $html;
}

function wrap_table_rows($title,$rows) {

	$table = '<!-- START ' . $title . ' -->' . "\n"
	. '<div class="p-1 table-responsive">' . "\n"
	. '<table class="table table-striped table-bordered">' . "\n"
	. '<tbody>';

	if ( $title ) :
	 $table = $table . '<tr><th scope="row" colspan="3" style="width:1%">' . $title . '</th></tr>' . "\n";
	endif;

	$table = $table . $rows;

	$table = $table 
	. '</tbody>' . "\n"
	. '</table>' . "\n"
	. '</div> <!-- END ' . $title . ' -->' . "\n";	

  return $table;

}

function wrap_listgroup_items($groupname,$items) {

	$table = '<!-- START ' . $groupname . ' -->' . "\n"
	. '<ul class="list-group striped-list">' . "\n";

	if ( $groupname ) :
	 $table = $table . '<li class="list-group-item striped-list">' . $groupname . '</li>' . "\n";
	endif;

	$table = $table . $items;

	$table = $table 
	. '</ul> <!-- END ' . $groupname . ' -->' . "\n";	

  return $table;

}

function wrap_definitionlist_items($groupname,$items) {

	$table = '<!-- START ' . $groupname . ' -->' . "\n"
	. '<dl class="row striped-list px-1 py-3">' . "\n";


	$table = $table . $items;

	$table = $table 
	. '</dl> <!-- END ' . $groupname . ' -->' . "\n";	

  return $table;

}



function wrap_accordian_rows($header, $data, $open=0) {

	$accordian = '<!-- START ' . $header . ' -->' . "\n"
	. '<div class="accordion-item">' . "\n"
	. '<h2 class="accordion-header font-weight-bold" id="heading'
	. $header 
	. '">' . "\n"
	. '<button class="accordion-button';
	
	if ( $open ) :
	 $accordian = $accordian . '';
	else:
	 $accordian = $accordian . ' collapsed';
	endif;
	
	$accordian = $accordian 
	. '" type="button" data-bs-toggle="collapse" data-bs-target="#collapse' 
	. $header 
	. '" aria-expanded="false" aria-controls="collapse' 
	. $header 
	. '"><strong>'
	. $header 
	. '</strong></button></h2>' . "\n"
	. '<div id="collapse'
	. $header
	. '" class="accordion-collapse collapse';

	if ( $open ) :
	 $accordian = $accordian. ' show';
	endif;

	$accordian = $accordian 
	. '" aria-labelledby="heading' 
	. $header 
	. '">' . "\n"
	. '<div class="accordion-body">' . "\n";
	  
	$accordian = $accordian . $data;

	$accordian = $accordian 
	. ' </div>' . "\n"
	. '</div>' . "\n"
	. '</div>' . "\n"
	. '<!-- END ' . $header . ' -->' . "\n";	
  
  return $accordian;
}

function get_percentage_of($number,$total) {
$percentage = round(($number / $total) * 100, 2);
return $percentage;
}


function get_image_url ($release) {
    global $IMAGE_PATH_ROOT_URL, $IMAGE_PATH_ROOT;
    $id = $release['basic_information']['id'];
    $imageupdatedtext = '';
    $valid_image = 0;
    $image_url = $IMAGE_PATH_ROOT_URL . $id . '.jpeg';
    $image_file = $IMAGE_PATH_ROOT . $id . '.jpeg';
    #$imagefile = $image_path;
	if ( !is_dir( $IMAGE_PATH_ROOT ) ):
        $imageupdatedtext = "Missing ./img directory.";  
        $image_url = '/no-album-art.png';
		$valid_image=1;
	elseif ( !file_exists($image_file) ):
        $imageupdatedtext = "Missing file has been downloaded from Discogs server.";  
        $cover_image = file_get_contents($release['basic_information']['cover_image']);
		file_put_contents($image_file, $cover_image);
		#valid_image=1;
		usleep(500000);
	elseif (filesize($image_file) <= 200 ):
        $imageupdatedtext = filesize($image_file) . " byte file has been downloaded from Discogs server. Hotlinking.";  
        $cover_image = file_get_contents($release['basic_information']['cover_image']);
		file_put_contents($image_file, $cover_image);
		$image_url = $release['basic_information']['cover_image'];
		$valid_image=1;
		usleep(500000);
    endif;
    
    return [$image_url,$imageupdatedtext];
 }
 
 
function display_gallery_item($release) { 
global $IMAGE_PATH_ROOT_URL, $IMAGE_PATH_ROOT;
$artists = implode(", ", array_column($release['basic_information']['artists'], "name"));
$title = $release['basic_information']['title'];
$id = $release['basic_information']['id'];
    
[$image_url,$imageupdatedtext] = get_image_url($release);

$adddate = date('m/d/y', strtotime(substr($release['date_added'],0,10)));
$todaydate = date("Y-m-d"); 
$is_new_class = '';
$is_new_badge ='';
if(strtotime($adddate) > strtotime('-14 days')) :
$is_new_class = ' border-success';
$is_new_badge = '<span class="badge bg-success px-3 rounded-pill">New</span>';
endif;	
?>

<!-- Gallery item -->
<div class="col-xl-3 col-md-6 col-sm-6 my-3">
 <div class="card h-100 <?php echo $is_new_class; ?>">


<a href="/?releaseid=<?php echo $id ?>">
   <img class="card-img-top rounded p-2" loading="lazy" src="<?php echo $image_url; ?>" alt="<?php echo $title; ?>">
</a>
        
   <div class="card-body d-flex flex-column">
   <?php if ( $imageupdatedtext ) : ?>
     <p class="alert alert-warning" role="alert"">
	  <small class="text-muted text-center"><?php echo $imageupdatedtext; ?></small>
	 </p>
   <?php endif; ?>
	<div class="d-flex flex-column mt-auto">
    <h5 class="card-title"><i class="fa-solid fa-quote-right text-muted"></i> <?php echo $title; ?></h5>
    <h6 class="card-title"><i class="fa-solid fa-people-group text-muted"></i> <?php echo $artists; ?></h6>
	</div>
   </div> 
   
   <div class="card-footer d-flex justify-content-between"><small>added <?php echo $adddate; ?></small>
    <?php echo $is_new_badge; ?>
   </div>

  </div>
 </div>

<!-- End gallery Item -->

<?php } // End display_gallery_item() ?>



<?php function display_release_data($release_id) {
global $releaseinfo;
global $myreleaseinfo;

$id = $releaseinfo['id'];
$resource_url = $releaseinfo['resource_url'];

$labelname = '';
if( array_key_exists('labels', $releaseinfo) ) :
	$number_of_labels = sizeof($releaseinfo['labels']);
	for( $i=0; $i<$number_of_labels;$i++ ) :
		if( array_key_exists('name', $releaseinfo['labels'][$i]) )
			$labelname = $labelname 
			. $releaseinfo['labels'][$i]['name'];
		if( array_key_exists('catno', $releaseinfo['labels'][$i]) )
			$labelname = $labelname 
			. ', ' . $releaseinfo['labels'][$i]['catno'] 
			. '<br>';
	endfor;
endif;
$formats = '';
if( array_key_exists('formats', $releaseinfo) ) :
	$number_of_formats = sizeof($releaseinfo['formats']);
	for($i=0; $i<$number_of_formats;$i++) :
		if( array_key_exists('name', $releaseinfo['formats'][$i]) ) :
			$qty = '';
			if ( $releaseinfo['formats'][$i]['qty'] > 1 )
				$qty = $releaseinfo['formats'][$i]['qty'] . ' x ';
			$formats = $formats
			. $qty
			. '<b>' 
			. $releaseinfo['formats'][$i]['name'] 
			. '</b>';
		endif;
		if( !array_key_exists( 'text', $releaseinfo['formats'][$i]) && !array_key_exists('descriptions', $releaseinfo['formats'][$i]) )
			$formats = $formats
			. '<br>';
		if( array_key_exists('descriptions', $releaseinfo['formats'][$i]) )
			$formats = $formats
			. ', ' . implode(", ", $releaseinfo['formats'][$i]['descriptions']);
		if( !array_key_exists('descriptions', $releaseinfo['formats'][$i]) )
			$formats = $formats
			. '<br>';
		if( array_key_exists('text', $releaseinfo['formats'][$i]) )
			$formats = $formats 
			. ', <i>' . $releaseinfo['formats'][$i]['text'] . '</i>'
			. '<br>';
	endfor;
endif;

$genres = implode(", ", $releaseinfo['genres']);

$styles = "";
if( array_key_exists('styles', $releaseinfo) )
	$styles = implode(", ", $releaseinfo['styles']);

$title = $releaseinfo["title"];
$artists = implode(", ", array_column($releaseinfo['artists'], "name"));

$identifier_rows = '';
if( array_key_exists('identifiers', $releaseinfo) ) :
	$identifiers = $releaseinfo['identifiers'];
	$number_of_identifiers = sizeof($identifiers);
	$identifier_rows = '';
	for( $i=0; $i<$number_of_identifiers;$i++ ) :
		$identifier_type = '';
		$identifier_value = '';
		$identifier_description = '';
		$identifier_type = $identifiers[$i]['type'];
		$identifier_value = $identifiers[$i]['value'];
		if ( isset($identifiers[$i]['description']) ) 
			$identifier_description = $identifiers[$i]['description'];
			
			$identifier_rows = $identifier_rows . '<tr><td data-align="left">' 
			. $identifier_type
			. '</td><td>' 
			. $identifier_value 
			. '</td><td>' 
			. @$identifier_description 
			. '</td></tr>
			'; 
		
	endfor;
endif;

$series = $releaseinfo['series'];
if( !empty($series )) :
	$series = $series[0]['name'];
else:
	$series = '';
endif;

$list_of_companies_rows = '';
if( array_key_exists('companies', $releaseinfo) ) :
	$companies = $releaseinfo['companies'];
			for($i=0; $i<sizeof($companies);$i++) :
				$list_of_companies_rows = $list_of_companies_rows . '<li class="list-group-item"><strong>' 
						. $companies[$i]['entity_type_name'] 
						. '</strong> ' 
						. $companies[$i]['name'] 
						. '</li>
						';
			endfor;
endif;
			
			

$releasenotes = '';
$my_release_notes = '';

if( array_key_exists('notes', $releaseinfo) )
	$releasenotes = $releaseinfo['notes'];
$images = $releaseinfo['images'];
$year = '?';
if( array_key_exists('released', $releaseinfo) )
	$year = $releaseinfo['released'];

$my_release_notes_rows = '';
if( array_key_exists('notes', $myreleaseinfo['releases'][0]) ) :
	foreach ($myreleaseinfo['releases'][0]['notes'] as $mynotes) :
		if ( $mynotes['field_id'] == 1 ):
			$noteicon = 'fa-compact-disc';
			$notetype = 'Media';
		elseif ( $mynotes['field_id'] == 2 ):
			$noteicon = 'fa-square-full';
			$notetype = 'Jacket';
		elseif ( $mynotes['field_id'] == 3 ):
			$noteicon = 'fa-clipboard';
			$notetype = 'Notes';
			$my_release_notes = '<tr><td>' . $mynotes['value'] . '</td></tr>';
		elseif ( $mynotes['field_id'] == 4 ):
			$noteicon = 'fa-clipboard';
			$notetype = 'Category';
		endif;
	
		$my_release_notes_rows = $my_release_notes_rows
			. '<tr><th><i class="fa-fw fa-solid ' 
			. $noteicon 
			. '"></i></th><td>' 
			. $notetype 
			. '</td><td>' 
			. $mynotes['value'] 
			.'</td></tr>
			'; 
	endforeach;
endif;
			
$release_tracklist_rows = '';
$track_extraartists_list = '';
if( array_key_exists('tracklist', $releaseinfo) ) :
	$tracklist = $releaseinfo['tracklist'];
	$number_of_release_tracklist_tracks = sizeof($tracklist);
	$release_tracklist_rows = '<tr><th data-align="left" style="width:1%">#</th><td>Track Name</td><td>m:s</td></tr>';
	for($i=0; $i<$number_of_release_tracklist_tracks;$i++)  :
		if( array_key_exists('extraartists', $releaseinfo['tracklist'][$i]) ) :
			$track_extraartists = $tracklist[$i]['extraartists'];
			$number_of_track_extraartists = sizeof($track_extraartists);
			$track_extraartists_list = '';
			for($e=0; $e<$number_of_track_extraartists; $e++) :
				$track_extraartists_list = $track_extraartists_list 
				. $releaseinfo['tracklist'][$i]['extraartists'][$e]['role']
				. ' '
				. '<strong>'
				. $releaseinfo['tracklist'][$i]['extraartists'][$e]['name']
				. '</strong>';
				if ( $e != ($number_of_track_extraartists - 1) ) :
					$track_extraartists_list = $track_extraartists_list 
					. ', ';
				endif;
				
			endfor;
		endif;
		$release_tracklist_rows = $release_tracklist_rows 
		. '<tr><th data-align="left" style="width:1%">' 
		. $tracklist[$i]['position'] 
		. ":  " 
		. '</th><td data-align="left">' 
		.  $tracklist[$i]['title'] 
		. '<br>'
		. $track_extraartists_list
		.  '</td><td data-align="left">' 
		. $tracklist[$i]['duration'] 
		. '</td></tr>
		';
	$track_extraartists_list = '';
	endfor;
endif;

$extra_artists_rows = '';
if( array_key_exists('extraartists', $releaseinfo) ) :
	$extraartists = $releaseinfo['extraartists'];
	$number_of_extra_artists = sizeof($extraartists);
	for($i=0; $i<$number_of_extra_artists;$i++) :
		$artist_role = $extraartists[$i]['role'];
		$artist_name = $extraartists[$i]['name'];
		$artist_tracks = $extraartists[$i]['tracks'];
		if ( $artist_tracks)
			$artist_tracks = ' (' . $artist_tracks . ')';
		
		$extra_artists_rows = $extra_artists_rows
		. '<li class="list-group-item"><strong>' 
		. $artist_role
		. ':</strong> ' 
		. $artist_name 
		. $artist_tracks 
		. '</li>
		';
	endfor;
endif;
?>
			


<div class="  col-sm-6 col-md-6 col-xl-4 my-3">
 <div class="rounded shadow-sm">
 
 <div class="card h-100">
<div id="carouselExampleControls" class="carousel slide" data-bs-ride="carousel">
  <div class="carousel-inner">
    <?php for($i=0; $i<sizeof($images);$i++) {
		echo '<div class="carousel-item'; 
		if($i == 0) { 
			echo " active"; 
		} 
		echo '"><img class="d-block w-100" src="' 
			. $images[$i]['resource_url'] 
			. '" alt="' 
			. $images[$i]['type'] 
			. '"></div>
			'; } ?>
   </div>
   
<button class="carousel-control-prev" data-href="#carouselExampleControls" data-bs-slide="prev">
    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
    <span class="visually-hidden">Previous</span>
  </button>

<button class="carousel-control-next" data-href="#carouselExampleControls" data-bs-slide="next">
    <span class="carousel-control-next-icon" aria-hidden="true"></span>
    <span class="visually-hidden">Next</span>
  </button>
  
</div> <!-- END carouselExampleControls -->

<div class="card-body">
<div class="p-1 table-responsive">
 <table class="table table-striped">
  <tbody>
    <tr>
      <th scope="row" style="width:1%"><i class="fa-fw fa-solid fa-quote-right"></i></th>
      <td style="width:1%">Title</td>
      <td><?php echo $title; ?></td>
    </tr>                
    <tr>
      <th scope="row"><i class="fa-fw fa-solid fa-people-group"></i></th>
      <td>Artist</td>
      <td><?php echo $artists ?></td>
    </tr>
    <tr>
      <th scope="row"><i class="fa-fw fa-solid fa-calendar-days"></i></th>
      <td>Released</td>
      <td><?php echo $year; ?></td>
    </tr>
<?php if (!empty($series)) : ?>
    <tr>
      <th scope="row"><i class="fa-fw fa-solid fa-calendar-days"></i></th>
      <td>Series</td>
      <td><?php if( $series ) echo $series; ?></td>
    </tr>
<?php endif; ?>
    <tr>
      <th scope="row"><i class="fa-fw fa-solid fa-building"></i></th>
      <td>Label</td>
      <td><?php  if( $labelname ) echo $labelname; ?></td>
    </tr>
    <tr>
      <th scope="row"><i class="fa-fw fa-solid fa-compact-disc"></i></th>
      <td>Format</td>
       <td>
        <?php echo $formats; ?>
        </td>
    </tr>
    <tr>
      <th scope="row"><i class="fa-fw fa-solid fa-bars-staggered"></i></th>
      <td>Genres</td>
       <td>
         <?php echo $genres; ?>
      </td>
      </tr>
	  <tr>
        <th scope="row"><i class="fa-fw fa-solid fa-bars-staggered"></i></th>
      <td>Styles</td>
       <td>
         <?php if( $styles ) echo $styles; ?> 
      </td>
      </tr>
    
	
<?php //Release notes are generated as complete rows with headers.
		echo $my_release_notes_rows; 
		?>
	  </tbody>
 </table>
</div>
</div> <!-- END card body -->

      <div class="card-footer d-flex justify-content-between">
       <a class="btn btn-secondary btn-sm" href="https://www.discogs.com/release/<?php echo $id ?>">Discogs <i class="fa-solid fa-arrow-up-right-from-square"></i></a>
	   <a class="btn btn-secondary btn-sm" href="<?php echo $resource_url; ?>">JSON <i class="fa-solid fa-arrow-up-right-from-square"></i></a></div>
	   
	   </div></div></div>	


<div class="col-sm-6 col-md-6 col-xl-8 my-3">
	<div class="bg-white rounded shadow-sm">
	
<div class="accordion" id="accordionExample">

<?php if ( isset($releasenotes) && ($releasenotes != '') ) :
			echo wrap_accordian_rows('Notes',wrap_table_rows('', '<tr><td>' . $releasenotes. '</td></tr>' . $my_release_notes),'opened');
 	  endif; ?>	
<?php echo wrap_accordian_rows('TrackList',wrap_table_rows('',$release_tracklist_rows),'opened'); ?>
<?php echo wrap_accordian_rows('Credits',wrap_listgroup_items('',$extra_artists_rows)); ?>
<?php echo wrap_accordian_rows('Companies',wrap_listgroup_items('',$list_of_companies_rows)); ?>
<?php echo wrap_accordian_rows('Identifiers',wrap_table_rows('',$identifier_rows)); ?>

</div>

 </div>
</div> 



<?php  #echo $releasedata; 
}

?>
