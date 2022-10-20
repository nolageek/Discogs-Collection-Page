<?php
error_reporting(E_ALL);
ini_set('display_errors', 'On');

$DISCOGS_API_URL="https://api.discogs.com";
$DISCOGS_USERNAME="";
$DISCOGS_TOKEN="";

$folder_id = "0";
$sort_by = "added";
$order = "desc";
$artist = "all";
$page = "1";
$per = "50";


// Folder

if(isset($_GET['folder']))
$folder_id = $_GET['folder'];

if(isset($_GET['sort_by']))
$sort_by = $_GET['sort_by'];

if(isset($_GET['pagenum']))
$page_num = $_GET['page_num'];

if(isset($_GET['order'])) 
$order = $_GET['order'];

if(isset($_GET['page'])) 
$page = $_GET['page'];

if(isset($_GET['artist']))
$artist = $_GET['artist'];

if(isset($_GET['per']))
$per = $_GET['per'];

$options  = array('http' => array('user_agent' => 'DiscogsCollectionPage'));
$context  = stream_context_create($options);
$folderjson = $DISCOGS_API_URL . "/users/" . $DISCOGS_USERNAME . "/collection/folders?token=" .$DISCOGS_TOKEN;
$folderdata = file_get_contents($folderjson, false, $context); // put the contents of the file into a variable
$folders = json_decode($folderdata,true); // decode the JSON feed

$pagejson = $DISCOGS_API_URL. "/users/" . $DISCOGS_USERNAME . "/collection/folders/" . $folder_id . "/releases?sort=" . $sort_by . "&sort_order=" . $order . "&page=" . $page . "&per_page=" . $per . "&token=" . $DISCOGS_TOKEN;
$pagedata = file_get_contents($pagejson, false, $context); // put the contents of the file into a variable
$collection = json_decode($pagedata,true); // decode the JSON feed

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
        <a class="btn btn-primary text-uppercase <?php if($page == 1) echo "disabled"; ?>" href="/?folder=<?php echo $folder_id; ?>&sort_by=<?php echo $sort_by; ?>&order=<?php echo $order; ?>&per=<?php echo $per; ?>&page=<?php if($page != 1) echo (intval($page) - 1); ?>" tabindex="-1">&#12298;</a>
<?php 	$x = 1;
		$pages = $collection['pagination']['pages'];
		while($x <= $pages) {?>
		<a class="btn btn-primary text-uppercase <?php if($page == $x) echo "active disabled"; ?>" href="/?folder=<?php echo $folder_id; ?>&sort_by=<?php echo $sort_by; ?>&order=<?php echo $order; ?>&per=<?php echo $per; ?>&page=<?php echo $x;?>"><?php echo $x;?></a>
		<?php $x++; } 
?>
		<a class="btn btn-primary text-uppercase <?php if($page == $collection['pagination']['pages']) echo "disabled"; ?>" href="/?folder=<?php echo $folder_id; ?>&sort_by=<?php echo $sort_by; ?>&order=<?php echo $order; ?>&per=<?php echo $per; ?>&page=<?php if($page != $pages) echo (intval($page) + 1); ?>" tabindex="-1">&#12299;</a>
	</div>
	    <div class="btn-group btn-group-sm mr-2 p-1" role="group" aria-label="Folder Navigation">
		
<?php

foreach ($folders['folders'] as $folder) { 
$folderid = $folder['id'];
$foldername = $folder['name'];
$foldercount = $folder['count'];

if ($foldercount > 0) {
?>
    <a href="/?folder=<?php echo $folderid; ?>&sort_by=<?php echo $sort_by; ?>&order=<?php echo $order; ?>&per=<?php echo $per; ?>&page=1" class="btn btn-primary text-uppercase<?php if ($folder_id == $folderid) echo " disabled"; ?>"><?php echo $foldername; ?> (<?php echo $foldercount; ?>)</a>
<?php } } ?>
    </div>
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
    </div> <!-- Pagination / Nav / Filter Bar-->

  


<!-- Gallery of Releases -->
  <div class="row">
  

<?php

foreach ($collection['releases'] as $release) { 
$artists = implode(", ", array_column($release['basic_information']['artists'], "name"));
$title = $release['basic_information']['title'];
$id = $release['basic_information']['id'];
$year = $release['basic_information']['year'];
$resourceurl = $release['basic_information']['resource_url'];
$labelname = implode(", ", array_column($release['basic_information']['labels'], "name"));
$formatname = implode(", ", array_column($release['basic_information']['formats'], "name"));
$formattext = implode("", array_column($release['basic_information']['formats'], "text"));
if ($release['basic_information']['formats'][0]['descriptions'])
  $formatdesc = implode(", ", $release['basic_information']['formats'][0]['descriptions']);
$genres = implode(", ", $release['basic_information']['genres']);
$styles = implode(", ", $release['basic_information']['styles']);
$notes = "Notes not working yet.";
$imageupdatedtext = "";
$imagefile = "./img/" . $release['basic_information']['id'] . ".jpeg"; 
              if (!file_exists($imagefile) && is_dir( "./img/" ) ) {
                $imageupdatedtext = "Missing file has been downloaded from Discogs server.";  
                $imagename = file_get_contents($release['basic_information']['cover_image']);
                file_put_contents($imagefile, $imagename);
              }
?>


<!-- Gallery item -->
<div class="col-xl-3 col-lg-6 col-md-6 mb-4">
    
    
        <?php 
              ?>

        <div class="bg-white rounded shadow-sm"><a data-toggle="collapse" href=".multi-collapse<?php echo $release['basic_information']['id']; ?>" role="button" aria-expanded="false" aria-controls="<?php echo $release['basic_information']['id']; ?>A <?php echo $release['basic_information']['id']; ?>B $release['basic_information']['id']; ?>C" ><img src="<?php echo $imagefile; ?>" alt="" class="img-fluid card-img-top"></a>
        
                        <?php echo $imageupdatedtext; ?>
                
          <div class="p-4">

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
    <tr class="collapse multi-collapse<?php echo $id; ?>" id='<?php echo $id; ?>A'>
      <th scope="row"><i class="fa-fw fa-solid fa-calendar-days"></i></th>
      <td>Released</td>
      <td><?php echo $year; ?></td>
    </tr>
    <tr class="collapse multi-collapse<?php echo $id; ?>" id='<?php echo $id; ?>A'>
      <th scope="row"><i class="fa-fw fa-solid fa-building"></i></th>
      <td>Label</td>
      <td><?php  if( $labelname ) echo $labelname; ?></td>
    </tr>
    <tr class="collapse multi-collapse<?php echo $id; ?>" id='<?php echo $id; ?>B'>
      <th scope="row"><i class="fa-fw fa-solid fa-compact-disc"></i></th>
      <td>Format</td>
       <td>
        <?php  if( $formatname ) echo $formatname;
               if( $formattext ) echo ", " . $formattext;  
               if( $formatdesc ) echo ", "  . $formatdesc;
          ?>
        </td>
    </tr>
    <tr class="collapse multi-collapse<?php echo $id; ?>" id='<?php echo $id; ?>C'>
      <th scope="row"><i class="fa-fw fa-solid fa-bars-staggered"></i></th>
      <td>Genres</td>
       <td>
         <?php   echo $genres; 
                 if( $styles ) echo ", "  . $styles; ?>
      </td>
      </tr>
    <tr class="collapse multi-collapse<?php echo $id; ?>" id='<?php echo $id; ?>B'>
      <th scope="row"><i class="fa-fw fa-solid fa-file-pen"></i></th>
      <td>Notes</td>
       <td>
       <?php if($notes) echo $notes;
      ?>
      </td>      
    </tr>
</tbody>
</table>
 
            <div class="d-flex align-items-center justify-content-between bg-light px-3 py-2 mt-4">
              <p class="small mb-0">+<?php $adddate = $release['date_added']; echo date('m/d/y', strtotime(substr($adddate,0,10))) ?></p>
              <?php $todaydate = date("Y-m-d");
               if(strtotime($adddate) > strtotime('-10 days')) {
                ?>
              <div class="badge badge-danger px-3 rounded-pill font-weight-normal">New</div>
              <?php  } ?>
              <div><a class="btn btn-secondary btn-sm" href="https://www.discogs.com/release/<?php echo $id ?>">Discogs <i class="fa-solid fa-arrow-up-right-from-square"></i></a></div>
            </div>
          </div>
        </div>
      </div>
	  <!-- End gallery Item -->

<?php } ?>

</div> <!-- Gallery of Releases End -->
    <div class="py-5 text-center"><a href="#" class="btn btn-dark px-5 py-3 text-uppercase">BACK TO TOP</a></div>
  </div> <!-- Outer Container End -->



</body>
</html>
