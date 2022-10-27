<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');

$DISCOGS_API_URL="https://api.discogs.com";
$DISCOGS_USERNAME="nolageek";
$DISCOGS_TOKEN="ZylBbtsdWZrBEdEwhAdVSBZuuthKdDmqJvllmjBg";

$folder_id = "0";
$sort_by = "added";
$order = "desc";
$artistid = "";
$page = "1";
$per = "50";
$releaseid = "";

// Folder

if(isset($_GET['folder']))
$folder_id = $_GET['folder'];

if(isset($_GET['sort_by']))
$sort_by = $_GET['sort_by'];

if(isset($_GET['order'])) 
$order = $_GET['order'];

if(isset($_GET['page'])) 
$page = $_GET['page'];

if(isset($_GET['artistid']))
$artistid = $_GET['artistid'];

if(isset($_GET['per']))
$per = $_GET['per'];

if(isset($_GET['releaseid']))
$releaseid = $_GET['releaseid'];

$options  = array('http' => array('user_agent' => 'DiscogsCollectionPage'));
$context  = stream_context_create($options);

if($releaseid) {
	$releasejson = $DISCOGS_API_URL . "/releases/" . $releaseid . "?token=" .$DISCOGS_TOKEN;
	$releasedata = file_get_contents($releasejson, false, $context); // put the contents of the file into a variable
	$releaseinfo = json_decode($releasedata,true); // decode the JSON feed
} else {
	$pagejson = $DISCOGS_API_URL. "/users/" . $DISCOGS_USERNAME . "/collection/folders/" . $folder_id . "/releases?sort=" . $sort_by . "&sort_order=" . $order . "&page=" . $page . "&per_page=" . $per . "&token=" . $DISCOGS_TOKEN;
	$pagedata = file_get_contents($pagejson, false, $context); // put the contents of the file into a variable
	$collection = json_decode($pagedata,true); // decode the JSON feed
}

$folderjson = $DISCOGS_API_URL . "/users/" . $DISCOGS_USERNAME . "/collection/folders?token=" .$DISCOGS_TOKEN;
$folderdata = file_get_contents($folderjson, false, $context); // put the contents of the file into a variable
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
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">

<script src="https://kit.fontawesome.com/7e1a0bb728.js" crossorigin="anonymous"></script>
<script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-Fy6S3B9q64WdZWQUiU+q4/2Lc9npb8tCaSX9FK7E8HnRr0Jz8D6OP9dO5Vg3Q9ct" crossorigin="anonymous"></script>

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
          <p class="lead">[<?php echo $currentfoldername;?>] (<?php echo $currentfoldercount;?> items) Sorted by: <?php echo $sort_by ?>, <?php echo $order ?>ending</p>
        </div>
      </div>
    </div>
    <!-- Banner header End -->


<!-- Pagination / Nav / Filter Bar-->
<div class="btn-toolbar d-flex justify-content-center p-3" role="toolbar" aria-label="Toolbar with button groups">

    <div class="btn-group btn-group-sm mr-2 p-1" role="group" aria-label="Pagination">
	<?php if(!$releaseid) { ?>
        <a class="btn btn-primary text-uppercase <?php if($page == 1) echo "disabled"; ?>" href="/?folder=<?php echo $folder_id; ?>&sort_by=<?php echo $sort_by; ?>&order=<?php echo $order; ?>&per=<?php echo $per; ?>&page=<?php if($page != 1) echo (intval($page) - 1); ?>" tabindex="-1">&#12298;</a>
<?php 	$x = 1;
		$pages = $collection['pagination']['pages'];
		while($x <= $pages) {?>
		<a class="btn btn-primary text-uppercase <?php if($page == $x) echo "active disabled"; ?>" href="/?folder=<?php echo $folder_id; ?>&sort_by=<?php echo $sort_by; ?>&order=<?php echo $order; ?>&per=<?php echo $per; ?>&page=<?php echo $x;?>"><?php echo $x;?></a>
		<?php $x++; } 
?>
		<a class="btn btn-primary text-uppercase <?php if($page == $collection['pagination']['pages']) echo "disabled"; ?>" href="/?folder=<?php echo $folder_id; ?>&sort_by=<?php echo $sort_by; ?>&order=<?php echo $order; ?>&per=<?php echo $per; ?>&page=<?php if($page != $pages) echo (intval($page) + 1); ?>" tabindex="-1">&#12299;</a>
	</div>
	
	<div class="btn-group btn-group-sm mr-2 p-1" role="group" aria-label="Per Page">
    <button id="btnGroupDrop1" type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
      <?php echo $per; ?> Per Page
    </button>
    <div class="dropdown-menu" aria-labelledby="btnGroupDrop1">
      <a class="dropdown-item" href="/?folder=<?php echo $folder_id; ?>&sort_by=<?php echo $sort_by; ?>&order=<?php echo $order; ?>&per=25&page=1">25</a>
      <a class="dropdown-item" href="/?folder=<?php echo $folder_id; ?>&sort_by=<?php echo $sort_by; ?>&order=<?php echo $order; ?>&per=50&page=1">50</a>
	  <a class="dropdown-item" href="/?folder=<?php echo $folder_id; ?>&sort_by=<?php echo $sort_by; ?>&order=<?php echo $order; ?>&per=100&page=1">100</a>
    </div>
	
	<?php } else {?>
  <button type="button" class="btn btn-primary text-uppercase" onclick="javascript:history.go(-1)">Back</button>
  <?php } ?>
  </div>

  
	    <div class="btn-group btn-group-sm mr-2 p-1" role="group" aria-label="Folder Navigation">
		
<?php

foreach ($folders['folders'] as $folder) { 
$folderid = $folder['id'];
$foldername = $folder['name'];
$foldercount = $folder['count'];

if ($foldercount > 1) {
?>
    <a href="/?folder=<?php echo $folderid; ?>&sort_by=<?php echo $sort_by; ?>&order=<?php echo $order; ?>&per=<?php echo $per; ?>&page=1" class="btn btn-primary text-uppercase<?php if ($folder_id == $folderid) echo " disabled"; ?>"><?php echo $foldername; ?> (<?php echo $foldercount; ?>)</a>
<?php } } ?>
    </div>
	
<?php if(!$releaseid) { ?>	
	
    <div class="btn-group btn-group-sm mr-2 p-1" role="group" aria-label="Sort by Artist or Date Added">
    <?php if ($sort_by == "artist") { ?>
    <a href="/?folder=<?php echo $folder_id; ?>&sort_by=added&order=<?php echo $order; ?>&per=<?php echo $per; ?>&page=<?php echo $page; ?>" class="btn btn-info text-uppercase">Added</A>
    <?php } else { ?>
    <a href="/?folder=<?php echo $folder_id; ?>&sort_by=artist&order=<?php echo $order; ?>&page=<?php echo $page; ?>" class="btn btn-info text-uppercase">Artist</a> 
<?php } ?>
    </div>
    <div class="btn-group btn-group-sm  p-1" role="group" aria-label="Ascending or Descending">
    <a href="/?folder=<?php echo $folder_id; ?>&sort_by=<?php echo $sort_by; ?>&order=asc&per=<?php echo $per; ?>" class="btn btn-secondary text-uppercase<?php if ($order == "asc") echo " disabled"; ?>">ASC</a>
     <a href="/?folder=<?php echo $folder_id; ?>&sort_by=<?php echo $sort_by; ?>&order=desc&per=<?php echo $per; ?>&page=<?php echo $page; ?>" class="btn btn-secondary text-uppercase<?php if ($order == "desc") echo " disabled"; ?>">DESC</a> 
    </div>
<?php } ?>	
	
    </div> <!-- Pagination / Nav / Filter Bar-->

  



  <div class="row"> <!-- Gallery of Releases -->


  
  <?php 
  	if ($releaseid) {
	  display_release_data($releaseid);
  } else {
	  
  foreach ($collection['releases'] as $release) { 
	display_gallery_item($release);
}
  }	  

  
  
?>

</div> <!-- Gallery of Releases End -->



<?php
function display_gallery_item($release) { 

$artists = implode(", ", array_column($release['basic_information']['artists'], "name"));
$title = $release['basic_information']['title'];
$id = $release['basic_information']['id'];
$year = $release['basic_information']['year'];
$resourceurl = $release['basic_information']['resource_url'];
$labelname = implode(", ", array_column($release['basic_information']['labels'], "name"));
$formatname = implode(", ", array_column($release['basic_information']['formats'], "name"));
$formattext = implode("", array_column($release['basic_information']['formats'], "text"));
if (@$release['basic_information']['formats'][0]['descriptions'])
  $formatdesc = implode(", ", $release['basic_information']['formats'][0]['descriptions']);
$genres = implode(", ", $release['basic_information']['genres']);
$styles = implode(", ", $release['basic_information']['styles']);
$notes = sizeof(array_column($release['basic_information'],'notes'));
$imageupdatedtext = "";
$imagefile = "./img/" . $release['basic_information']['id'] . ".jpeg"; 
              if (!file_exists($imagefile) && is_dir( "./img/" ) ) {
                $imageupdatedtext = "Missing file has been downloaded from Discogs server.";  
                $imagename = file_get_contents($release['basic_information']['cover_image']);
                file_put_contents($imagefile, $imagename);
              } elseif (!file_exists($imagefile) && !is_dir( "./img/" ) ) {
                $imageupdatedtext = "Missing file has been hotlinked from Discogs server.";  
                $imagefile = $release['basic_information']['cover_image'];
              }
			  
?>


<!-- Gallery item -->
<div class="col-xl-3 col-lg-6 col-md-6 mb-4">

        <div class="bg-white rounded shadow-sm">
<!--<a data-toggle="collapse" href=".multi-collapse<?php echo $release['basic_information']['id']; ?>" role="button" aria-expanded="false" aria-controls="<?php //echo $release['basic_information']['id']; ?>A <?php //echo $release['basic_information']['id']; ?>B $release['basic_information']['id']; ?>C" > -->

<a href="/?releaseid=<?php echo $id ?>">

  <img src="<?php echo $imagefile; ?>" class="figure-img img-fluid rounded" alt="<?php echo $title; ?>"></a>
  <?php if ($imageupdatedtext) { ?>
  <div class="d-flex align-items-center justify-content-between bg-light px-3 py-2"><small class="text-muted text-center"><?php echo $imageupdatedtext; ?></small></div>
  <?php } ?>
        
          <div class="p-1">

            <table class="table table-striped">
            <tbody>
    <tr>
      <th scope="row"><i class="fa-fw fa-solid fa-quote-right"></i></th>
      <td>Title</td>
      <td><?php echo $title; ?></td>
    </tr>                
    <tr>
      <th scope="row"><i class="fa-fw fa-solid fa-people-group"></i></th>
      <td>Artist</td>
      <td><?php echo $artists; ?></td>
    </tr>
    <tr class="collapse multi-collapse<?php echo $id; ?>" id='<?php echo $id; ?>B'>
      <th scope="row"><i class="fa-fw fa-solid fa-file-pen"></i></th>
      <td>Notes</td>
       <td>
	   
       <?php foreach ($release['basic_information']['notes'] as $notes) {
	   echo $notes['field_id']; }
	  
		   
	   if($notes) echo $notes;
      ?>
      </td>      
    </tr>
    <tr class="collapse multi-collapse<?php echo $id; ?>" id='<?php echo $id; ?>B'>
      <th scope="row"><i class="fa-fw fa-solid fa-file-pen"></i></th>
      <td colspan="2" align="right"><a class="btn btn-secondary btn-sm" href="/?releaseid=<?php echo $id ?>">More</a>
       <a class="btn btn-secondary btn-sm" href="https://www.discogs.com/release/<?php echo $id ?>">Discogs <i class="fa-solid fa-arrow-up-right-from-square"></i></a>
           
      </td>      
    </tr>
</tbody>
</table>
 
            <div class="d-flex align-items-center justify-content-between bg-light px-3 py-2 mt-4">
              <p class="small mb-0 text-muted">added <?php $adddate = $release['date_added']; echo date('m/d/y', strtotime(substr($adddate,0,10))) ?></p>
              <?php $todaydate = date("Y-m-d");
               if(strtotime($adddate) > strtotime('-10 days')) {
                ?>
              <div class="badge badge-danger px-3 rounded-pill font-weight-normal">New</div>
              <?php  } ?>
             </div>
          </div>
        </div>
      </div>
	  <!-- End gallery Item -->

<?php } ?>



<?php function display_release_data($releaseid) {
global $releaseinfo;

#$resourceurl = $release['basic_information']['resource_url'];
$labelname = implode(", ", array_column($releaseinfo['labels'], "name"));
$formatname = implode(", ", array_column($releaseinfo['formats'], "name"));
$formattext = implode("", array_column($releaseinfo['formats'], "text"));
if ($releaseinfo['formats'][0]['descriptions'])
  $formatdesc = implode(", ", $releaseinfo['formats'][0]['descriptions']);
$genres = implode(", ", $releaseinfo['genres']);
$styles = "";
@$styles = implode(", ", $releaseinfo['styles']);


$title = $releaseinfo['title'];
//$artists = $releaseinfo['artists'];
$artists = implode(", ", array_column($releaseinfo['artists'], "name"));
$identifiers = $releaseinfo['identifiers'];
$tracklist = $releaseinfo['tracklist'];
$extraartists = $releaseinfo['extraartists'];
$releasenotes = $releaseinfo['notes'];
$images = $releaseinfo['images'];
$year = $releaseinfo['released'];
$notes = "Notes not dworking yet.";

?>
<div class="col-xl-3 col-lg-6 col-md-6 mb-4">
 <div class="bg-white rounded shadow-sm">
 
<div id="carouselExampleControls" class="carousel slide" data-ride="carousel">
  <div class="carousel-inner">
  
  <?php for($i=0; $i<sizeof($images);$i++) { echo '<div class="carousel-item'; if($i == 0) { echo " active"; } echo '"><img class="d-block w-100" src="' . $images[$i]['resource_url'] . '" alt="' . $images[$i]['type'] . '">
  </div>'; } ?>
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
      <th scope="row"><i class="fa-fw fa-solid fa-quote-right"></i></th>
      <td>Title</td>
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
    <tr>
      <th scope="row"><i class="fa-fw fa-solid fa-building"></i></th>
      <td>Label</td>
      <td><?php  if( $labelname ) echo $labelname; ?></td>
    </tr>
    <tr>
      <th scope="row"><i class="fa-fw fa-solid fa-compact-disc"></i></th>
      <td>Format</td>
       <td>
        <?php  if( $formatname ) echo $formatname;
               if( $formattext ) echo ", " . $formattext;  
               if( $formatdesc ) echo ", "  . $formatdesc;
          ?>
        </td>
    </tr>
    <tr>
      <th scope="row"><i class="fa-fw fa-solid fa-bars-staggered"></i></th>
      <td>Genres</td>
       <td>
         <?php   echo $genres; 
                 if( $styles ) echo ", "  . @$styles; ?>
      </td>
      </tr>
    <tr>
      <th scope="row"><i class="fa-fw fa-solid fa-file-pen"></i></th>
      <td>Notes</td>
       <td>
       <?php if($notes) echo $notes;
      ?>
      </td>      
    </tr>
    <tr>
      <th scope="row"><i class="fa-fw fa-solid fa-file-pen"></i></th>
      <td colspan="2" align="right"><a class="btn btn-secondary btn-sm" href="/?releaseid=<?php echo $id ?>">More</a>
       <a class="btn btn-secondary btn-sm" href="https://www.discogs.com/release/<?php echo $id ?>">Discogs <i class="fa-solid fa-arrow-up-right-from-square"></i></a>
           
      </td>      
    </tr>
</tbody>
</table>
</div></div></div>		

<div class="col-xl-3 col-lg-6 col-md-6 mb-4">
	<div class="bg-white rounded shadow-sm">
		<div class="p-1 table-responsive">
           <table class="table table-striped">
            <tbody>
				<tr><th scope="row" colspan = "3">Identifiers</th></tr>
					<?php for($i=0; $i<sizeof($identifiers);$i++) echo '<tr><td data-align="left">' . @$identifiers[$i]['type'] . '</td><td data-align="left">' . @$identifiers[$i]['value'] . '</td><td data-align="left">' .  @$identifiers[$i]['description'] . '</td></tr>
					' ?>
					<?php if ($releasenotes) echo '<tr><td colspan="3" data-align="left">' . @$releasenotes . '</td></tr>
					'; ?>
			</tbody>
		  </table>
	
		</div>
	</div>
</div>		

<div class="col-xl-3 col-lg-6 col-md-6 mb-4">
	<div class="bg-white rounded shadow-sm">
		<div class="p-1 table-responsive">
           <table class="table table-striped">
            <tbody>
				<tr><th scope="row" colspan="3">Tracklist</th></tr>
					<?php for($i=0; $i<sizeof($tracklist);$i++) echo '<tr><td data-align="left">' . $tracklist[$i]['position'] . ": " . '</td><td data-align="left">' .  $tracklist[$i]['title'] .  '</td><td data-align="left">' . $tracklist[$i]['duration'] . '</td></tr>
					'; ?>
			</tbody>
		  </table>
	
		</div>
	</div>
</div>		

<div class="col-xl-3 col-lg-6 col-md-6 mb-4">
	<div class="bg-white rounded shadow-sm">
		<div class="p-1 table-responsive">
           <table class="table table-striped">
            <tbody>
				<th scope="row" colspan = "3">Extra Artists</th></tr>
					<?php for($i=0; $i<sizeof($extraartists);$i++) echo '<tr><td>' . $extraartists[$i]['role'] . ": " .  "</td><td> " . $extraartists[$i]['name'] .  "</td><td> " . '</td></tr>
					'; ?>
			</tbody>
		  </table>
	
		</div>
	</div>
</div>	

<?php  #echo $releasedata; 
} ?>


    <div class="py-5 text-center"><a href="#" class="btn btn-dark px-5 py-3 text-uppercase">BACK TO TOP</a></div>
  </div> <!-- Outer Container End -->



</body>
</html>
