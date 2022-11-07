<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');

$DISCOGS_API_URL="https://api.discogs.com";
$DISCOGS_USERNAME="";
$DISCOGS_TOKEN="";

// DEFAULT VALUES FOR ATTRIBUTES
$folder_id = "0";
$sort_by = "added";
$order = "desc";
//$artistid = "";
$page = "1";
$per_page = "50";
$release_id = "";

// GET ATTRIBUTES FROM URL

if( isset($_GET['folder']) )
	$folder_id = $_GET['folder'];

if( isset($_GET['sort_by']) )
	$sort_by = $_GET['sort_by'];

if( isset($_GET['order']) ) 
	$order = $_GET['order'];

if( isset($_GET['page']) ) 
	$page = $_GET['page'];

//if(isset($_GET['artistid']))
//$artistid = $_GET['artistid'];

if ( isset($_GET['per_page']) )
	$per_page = $_GET['per_page'];

if ( isset($_GET['releaseid']) )
	$release_id = $_GET['releaseid'];

$options  = array('http' => array('user_agent' => 'DiscogsCollectionPage'));
$context  = stream_context_create($options);

// IF THIS IS A SINGLE RELEASE VIEW, GET INFORMATION FROM RELEASE AND FROM USER COLLECTION FOR THAT RELEASE
if ($release_id) {
	// PULL DISCOGS REGARDING THE RELEASE IN MY COLLECTION
	$releasejson = $DISCOGS_API_URL 
		. "/releases/" 
		. $release_id 
		. "?token=" .$DISCOGS_TOKEN;
	// put the contents of the JSON into a variable
	$releasedata = file_get_contents($releasejson, false, $context); 
	// decode the JSON feed
	$releaseinfo = json_decode($releasedata,true); 
	
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

// IF NOT A SINGLE RELEASE VIEW, GET DATA FOR USER'S COLLECTION TO DISPLAY COVER GALLERY.
} else {
	// PULL DISCOGS DATA REGARDING MY COLLECTION, PAGINATED
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
	// decode the JSON feed
	$collection = json_decode($pagedata,true); 
}


// GET FOLDER DATA FOR NAVIGATION BAR
$folderjson = $DISCOGS_API_URL
	. "/users/"
	. $DISCOGS_USERNAME
	. "/collection/folders?token="
	. $DISCOGS_TOKEN;
// put the contents of the file into a variable
$folderdata = file_get_contents($folderjson, false, $context); 
$folders = json_decode($folderdata,true); // decode the JSON feed


// Figure out name, ID and number of items of current folder.
foreach ($folders['folders'] as $folder) { 
	if ($folder['id'] == $folder_id) {
		$currentfoldername = $folder['name'];
		$currentfoldercount = $folder['count'];
		}
	} 
?>

<!DOCTYPE html>
<html>
<head>

<title>Discogs Collection Page</title>
<meta name="viewport" content="width=device-width, initial-scale=.8">

<script src="https://kit.fontawesome.com/7e1a0bb728.js" crossorigin="anonymous"></script>
<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-Zenh87qX5JnK2Jl0vWa8Ck2rdkQ2Bzep5IDxbcnCeuOxjzrPF/et3URy9Bv1WTRi" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-OERcA2EqjJCMA+/3y+gxIOqMEjwtxJY7qPCqsdltbNJuaOe923+mo//f6V8Qbsw3" crossorigin="anonymous"></script>

<style>
/*
*
* ==========================================
* FOR DEMO PURPOSE
* ==========================================
*
*/

body {
  background: #f4f4f4;
}

.banner {
  background: #a770ef;
  background: -webkit-linear-gradient(to right, #a770ef, #cf8bf3, #fdb99b);
  background: linear-gradient(to right, #a770ef, #cf8bf3, #fdb99b);
}

.list-group-item { word-wrap: break-word; }

</style>

</head>

<body>

<div class="container-fluid"> <!-- Outer Container -->

 <!-- Banner header -->
    <div class="row">
      <div class="col-12 mx-auto my-1">
        <div class="text-white p-3 shadow-sm rounded banner">
          <h2 class="display-6">Discogs Collection Page for <?php echo $DISCOGS_USERNAME ?></h2>

		  <?php if ($release_id): ?>
					<p class="lead">
						"<?php echo $releaseinfo['title'];?>" by <?php echo implode (", ", array_column($releaseinfo['artists'], "name"));?>
					</p>
		  <?php else: ?>
					<p class="lead">
						<b><?php echo $currentfoldername;?></b> (<?php echo $currentfoldercount;?> items) Sorted by: <?php echo $sort_by ?>, <?php echo $order ?>ending
					</p>
		  <?php endif; ?>
        </div>
      </div>
    </div>
    <!-- Banner header End -->


<!-- Pagination / Nav / Filter Bar-->
<div class="btn-toolbar d-flex justify-content-center p-3" role="toolbar" aria-label="Toolbar with button groups">

 <div class="btn-group btn-group-sm mr-2 p-1" role="group" aria-label="Pagination">
	<?php if(!$release_id) { ?>
        <a class="btn btn-primary text-uppercase <?php if($page == 1) echo "disabled"; ?>" href="/?folder=<?php echo $folder_id; ?>&sort_by=<?php echo $sort_by; ?>&order=<?php echo $order; ?>&per_page=<?php echo $per_page; ?>&page=<?php if($page != 1) echo (intval($page) - 1); ?>" tabindex="-1">&#12298;</a>
		<?php
		$x = 1;
		$pages = $collection['pagination']['pages'];
		while($x <= $pages) {
		?>
			<a class="btn btn-primary text-uppercase <?php if($page == $x) echo "active disabled"; ?>" href="/?folder=<?php echo $folder_id; ?>&sort_by=<?php echo $sort_by; ?>&order=<?php echo $order; ?>&per_page=<?php echo $per_page; ?>&page=<?php echo $x;?>"><?php echo $x;?></a>
<?php 	$x++; } ?>

		<a class="btn btn-primary text-uppercase <?php if($page == $collection['pagination']['pages']) echo "disabled"; ?>" href="/?folder=<?php echo $folder_id; ?>&sort_by=<?php echo $sort_by; ?>&order=<?php echo $order; ?>&per_page=<?php echo $per_page; ?>&page=<?php if($page != $pages) echo (intval($page) + 1); ?>" tabindex="-1">&#12299;</a>
  </div>
	
  <div class="btn-group btn-group-sm mr-2 p-1" role="group" aria-label="Per Page">
    <button id="btnGroupDrop1" type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
      <?php echo $per_page; ?> Per Page
    </button>
  <div class="dropdown-menu" aria-labelledby="btnGroupDrop1">
      <a class="dropdown-item" href="/?folder=<?php echo $folder_id; ?>&sort_by=<?php echo $sort_by; ?>&order=<?php echo $order; ?>&per_page=25&page=1">25</a>
      <a class="dropdown-item" href="/?folder=<?php echo $folder_id; ?>&sort_by=<?php echo $sort_by; ?>&order=<?php echo $order; ?>&per_page=50&page=1">50</a>
	  <a class="dropdown-item" href="/?folder=<?php echo $folder_id; ?>&sort_by=<?php echo $sort_by; ?>&order=<?php echo $order; ?>&per_page=100&page=1">100</a>
  </div>
	
	<?php } else {?>
	<button type="button" class="btn btn-primary text-uppercase" onclick="javascript:history.go(-1)">Back</button>
    <?php } ?>
</div>

  
  <div class="btn-group btn-group-sm mr-2 p-1" role="group" aria-label="Folder Navigation">
<?php foreach ($folders['folders'] as $folder) { 

		$folderid = $folder['id'];
		$foldername = $folder['name'];
		$foldercount = $folder['count'];

		if ($foldercount > 1) { ?>
    <a href="/?folder=<?php echo $folderid; ?>&sort_by=<?php echo $sort_by; ?>&order=<?php echo $order; ?>&per_page=<?php echo $per_page; ?>&page=1" class="btn btn-primary text-uppercase<?php if ($folder_id == $folderid) echo " disabled"; ?>"><?php echo $foldername; ?> (<?php echo $foldercount; ?>)</a>
<?php } } ?>
  </div>
	
<?php if(!$release_id) { ?>	
  <div class="btn-group btn-group-sm mr-2 p-1" role="group" aria-label="Sort by Artist or Date Added">
    <?php if ($sort_by == "artist") { ?>
    <a href="/?folder=<?php echo $folder_id; ?>&sort_by=added&order=<?php echo $order; ?>&per_page=<?php echo $per_page; ?>&page=<?php echo $page; ?>" class="btn btn-info text-uppercase">Added</A>
    <?php } else { ?>
    <a href="/?folder=<?php echo $folder_id; ?>&sort_by=artist&order=<?php echo $order; ?>&page=<?php echo $page; ?>" class="btn btn-info text-uppercase">Artist</a> 
<?php } ?>
  </div>
  
  <div class="btn-group btn-group-sm  p-1" role="group" aria-label="Ascending or Descending">
    <a href="/?folder=<?php echo $folder_id; ?>&sort_by=<?php echo $sort_by; ?>&order=asc&per_page=<?php echo $per_page; ?>" class="btn btn-secondary text-uppercase<?php if ($order == "asc") echo " disabled"; ?>">ASC</a>
     <a href="/?folder=<?php echo $folder_id; ?>&sort_by=<?php echo $sort_by; ?>&order=desc&per_page=<?php echo $per_page; ?>&page=<?php echo $page; ?>" class="btn btn-secondary text-uppercase<?php if ($order == "desc") echo " disabled"; ?>">DESC</a> 
 </div>
<?php } ?>	
</div> <!-- Pagination / Nav / Filter Bar-->


<div class="row"> <!-- Gallery of Releases -->
  
<?php if ($release_id) {
	  display_release_data($release_id);
	} else {
	  foreach ($collection['releases'] as $release) { 
		display_gallery_item($release);
}
  }	  

?>

</div> <!-- Gallery of Releases End -->


<?php

function wrap_table_rows($title,$rows) {
$table = '<!-- START ' . $title . ' -->
		<div class="p-1 table-responsive">
           <table class="table table-striped">
            <tbody>';
if ( $title ) :
	$table = $table . '<tr><th scope="row" colspan="3" style="width:1%">' . $title . '</th></tr>';
endif;

$table = $table . $rows;

$table = $table . '		</tbody>
		</table>
	</div> <!-- END ' . $title . ' -->';	

return $table;
}


function wrap_accordian_rows($header, $data, $open=0) {

$accordian = '<!-- START ' 
			. $header . ' -->
			<div class="accordion-item">
			<h2 class="accordion-header font-weight-bold" id="heading' 
			. $header 
			. '">
            <button class="accordion-button';
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
			. '</strong></button></h2>
			<div id="collapse' 
			. $header 
			. '" class="accordion-collapse collapse';

	if ( $open ) :
		$accordian = $accordian. ' show';
	endif;

$accordian = $accordian 
			. '" aria-labelledby="heading' 
			. $header 
			. '">
      <div class="accordion-body">';
	  
$accordian = $accordian . $data;

$accordian = $accordian 
		. ' </div>
    </div>
  </div> <!-- END ' 
		. $header 
		. ' -->';	
  
  return $accordian;
}

function display_gallery_item($release) { 

$artists = implode(", ", array_column($release['basic_information']['artists'], "name"));
$title = $release['basic_information']['title'];
$id = $release['basic_information']['id'];
$imageupdatedtext = '';
$imagefile = './img/' . $release["basic_information"]["id"] . 'jpeg'; 
    if ( !file_exists($imagefile) && is_dir( "./img/" ) ):
        $imageupdatedtext = "Missing file has been downloaded from Discogs server.";  
        $imagename = file_get_contents($release['basic_information']['cover_image']);
		file_put_contents($imagefile, $imagename);
    elseif (!file_exists($imagefile) && !is_dir( "./img/" ) ):
        $imageupdatedtext = "Missing file has been hotlinked from Discogs server.";  
        $imagefile = $release['basic_information']['cover_image'];
    endif;

$adddate = date('m/d/y', strtotime(substr($release['date_added'],0,10)));
$todaydate = date("Y-m-d"); 
$is_new_class = '';
$is_new_badge ='';
if(strtotime($adddate) > strtotime('-14 days')) :
$is_new_class = ' border-success';
$is_new_badge = '<div class="badge badge-success px-3 rounded-pill">New</div>';
endif;	
?>

<!-- Gallery item -->
<div class="col-xl-3 col-md-6 col-sm-6 my-3">
 <div class="card h-100<?php echo $is_new_class; ?>">


<a href="/?releaseid=<?php echo $id ?>">
   <img class="card-img-top rounded p-2" src="<?php echo $imagefile; ?>" alt="<?php echo $title; ?>">
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
   
   <div class="card-footer text-muted d-flex justify-content-between"><small>added <?php echo $adddate; ?></small>
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
			. '</br>';
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
			. '</br>';
		if( array_key_exists('descriptions', $releaseinfo['formats'][$i]) )
			$formats = $formats
			. ', ' . implode(", ", $releaseinfo['formats'][$i]['descriptions']);
		if( !array_key_exists('descriptions', $releaseinfo['formats'][$i]) )
			$formats = $formats
			. '</br>';
		if( array_key_exists('text', $releaseinfo['formats'][$i]) )
			$formats = $formats 
			. ', <i>' . $releaseinfo['formats'][$i]['text'] . '</i>'
			. '</br>';
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
				$list_of_companies_rows = $list_of_companies_rows . '<tr><td data-align="left" colspan="3">' 
						. $companies[$i]['entity_type_name'] 
						. ' ' 
						. $companies[$i]['name'] 
						. '</td></tr>
						';
			endfor;
endif;
			
			

$releasenotes = '';
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
if( array_key_exists('tracklist', $releaseinfo) ) :
	$tracklist = $releaseinfo['tracklist'];
	$number_of_release_tracklist_tracks = sizeof($tracklist);
	for($i=0; $i<$number_of_release_tracklist_tracks;$i++)  :
		$release_tracklist_rows = $release_tracklist_rows 
		. '<tr><td data-align="left" style="width:1%">' 
		. $tracklist[$i]['position'] 
		. ": " 
		. '</td><td data-align="left">' 
		.  $tracklist[$i]['title'] 
		.  '</td><td data-align="left">' 
		. $tracklist[$i]['duration'] 
		. '</td></tr>
		';
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
		. '<tr><td  colspan="3">' 
		. $artist_role
		. ': ' 
		. $artist_name 
		. $artist_tracks 
		. '</td></tr>
		';
	endfor;
endif;
?>
			
			

<div class="col-xl-4 col-lg-6 col-md-6 mb-4">
 <div class="bg-white rounded shadow-sm">
 
<div id="carouselExampleControls" class="carousel slide" data-ride="carousel">
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
   
<a class="carousel-control-prev" href="#carouselExampleControls" role="button" data-slide="prev">
    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
    <span class="sr-only">Previous</span>
  </a>

<a class="carousel-control-next" href="#carouselExampleControls" role="button" data-slide="next">
    <span class="carousel-control-next-icon" aria-hidden="true"></span>
    <span class="sr-only">Next</span>
  </a>
  
</div>

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
	
    <tr>
      <th scope="row"></th>
      <td colspan="3" align="right">
       <a class="btn btn-secondary btn-sm" href="https://www.discogs.com/release/<?php echo $id ?>">Discogs <i class="fa-solid fa-arrow-up-right-from-square"></i></a>
           
      </td>      
    </tr>
  </tbody>
 </table>
</div></div></div>		


<div class="col-xl-8 col-lg-6 col-md-6 mb-4">
	<div class="bg-white rounded shadow-sm">
	
<div class="accordion" id="accordionExample">

<?php if ( isset($releasenotes) && ($releasenotes != '') ) :
			echo wrap_accordian_rows('Notes',wrap_table_rows('', $releasenotes),'opened');
 	  endif; ?>	
<?php echo wrap_accordian_rows('TrackList',wrap_table_rows('',$release_tracklist_rows),'opened'); ?>
<?php echo wrap_accordian_rows('Credits',wrap_table_rows('',$extra_artists_rows)); ?>
<?php echo wrap_accordian_rows('Companies',wrap_table_rows('',$list_of_companies_rows)); ?>
<?php echo wrap_accordian_rows('Identifiers',wrap_table_rows('',$identifier_rows)); ?>

</div>

 </div>
</div> 



<?php  #echo $releasedata; 
} ?>


    <div class="py-5 text-center"><a href="#" class="btn btn-dark px-5 py-3 text-uppercase">BACK TO TOP</a>
	</br> Like this page? Run your own: <a href="https://github.com/nolageek/Discogs-Collection-Page"><i class="fa-brands fa-github"></i> / Discogs Collection Page <a></div>
  </div> <!-- Outer Container End -->

</body>
</html>
