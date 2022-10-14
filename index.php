
<html>
<head>
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
<?php
//error_reporting(E_ALL);
//ini_set('display_errors', 'On');

$folder_id = "0";
$sort_by = "added";
$order = "desc";
$artist = "all";
$page_num = "1";

// Folder

if(isset($_GET['folder']))
$folder_id = $_GET['folder'];

if(isset($_GET['sort_by']))
$sort_by = $_GET['sort_by'];

if(isset($_GET['pagenum']))
$page_num = $_GET['page_num'];

if(isset($_GET['order'])) 
$order = $_GET['order'];

if(isset($_GET['artist']))
$artist = $_GET['artist'];


$folderjson = './json/folders.json'; // path to your FOLDER JSON file
$folderdata = file_get_contents($folderjson); // put the contents of the file into a variable
$folders = json_decode($folderdata,true); // decode the JSON feed

foreach ($folders['folders'] as $folder) { 
if ($folder['id'] == $folder_id) {
	
$foldername = $folder['name'];
$foldercount = $folder['count'];
}
}
?>


<div class="container-fluid">
	    <!-- For demo purpose -->

    <div class="row py-2">
      <div class="col-lg-12 mx-auto">
        <div class="text-white p-5 shadow-sm rounded banner">
          <h1 class="display-4">Discogs Collection Page</h1>
          <p class="lead"><?php echo $foldername;?>, <?php echo $foldercount;?> items: Sorted by <?php echo $sort_by ?>, <?php echo $order ?>ending</p>
        </div>
      </div>
    </div>
    <!-- End -->
	
  <div class="px-lg-5">
    <div class="py-5 text-center">

<?php

foreach ($folders['folders'] as $folder) { 
$folderid = $folder['id'];
$foldername = $folder['name'];
$foldercount = $folder['count'];

if ($foldercount > 0) {
?>
    <a href="/?folder=<?php echo $folderid; ?>&sort_by=<?php echo $sort_by; ?>&order=<?php echo $order; ?>" class="btn btn-primary px-2 py-1 text-uppercase<?php if ($folder_id == $folderid) echo " disabled"; ?>"><?php echo $foldername; ?> (<?php echo $foldercount; ?>)</a>
<?php } } ?>

    <?php if ($sort_by == "artist") { ?>
    <a href="/?folder=<?php echo $folder_id; ?>&sort_by=added&order=<?php echo $order; ?>" class="btn btn-info px-2 py-1 text-uppercase">Added</a>
    <?php } else { ?>
    <a href="/?folder=<?php echo $folder_id; ?>&sort_by=artist&order=<?php echo $order; ?>" class="btn btn-info px-2 py-1 text-uppercase">Artist</a> 
<?php } ?>
    <a href="/?folder=<?php echo $folder_id; ?>&sort_by=<?php echo $sort_by; ?>&order=asc" class="btn btn-secondary px-2 py-1 text-uppercase<?php if ($order == "asc") echo " disabled"; ?>">ASC</a>
     <a href="/?folder=<?php echo $folder_id; ?>&sort_by=<?php echo $sort_by; ?>&order=desc" class="btn btn-secondary px-2 py-1 text-uppercase<?php if ($order == "desc") echo " disabled"; ?>">DESC</a> 
     

 <!--    <a href="/?folder=<?php echo $folder_id; ?>-title" class="btn btn-dark px-5 py-3 text-uppercase">Title</a> 
     <a href="/?folder=<?php echo $folder_id; ?>-year" class="btn btn-dark px-5 py-3 text-uppercase">Year (asc)</a>
     <a href="/?folder=vinyl-year&sort=desc" class="btn btn-dark px-5 py-3 text-uppercase">Year (desc)</a>-->

    </div>
	
  </div>


  <div class="row">
  

<?php


$pagejson = 'json/' . $folder_id . "-" . $sort_by . "-" . $order . '.json'; // path to your JSON file
$pagedata = file_get_contents($pagejson); // put the contents of the file into a variable
$collection = json_decode($pagedata,true); // decode the JSON feed

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
$note = "Notes not working.";

?>

<!-- Gallery item -->

	
<div class="col-xl-3 col-lg-4 col-md-6 mb-4">
        <?php $imagefile = "img/" . $release['basic_information']['id'] . ".jpeg"; 
              if (!file_exists($imagefile))
                $imagefile = "no-album-art.png";
              ?>

        <div class="bg-white rounded shadow-sm"><a data-toggle="collapse" href=".multi-collapse<?php echo $release['basic_information']['id']; ?>" role="button" aria-expanded="false" aria-controls="<?php echo $release['basic_information']['id']; ?>A <?php echo $release['basic_information']['id']; ?>B $release['basic_information']['id']; ?>C" ><img src="<?php echo $imagefile; ?>" alt="" class="img-fluid card-img-top"></a>
          <div class="p-4">

            <table class="table table-striped">
            <tbody>
    <tr>
      <th scope="row">Title</th>
      <td><?php echo $title; ?></td>
    </tr>                
    <tr>
      <th scope="row">Artist</th>
      <td><?php echo $artists; ?></td>
    </tr>
    <tr class="collapse multi-collapse<?php echo $id; ?>" id='<?php echo $id; ?>A'>
      <th scope="row">Released</th>
      <td><?php echo $year; ?></td>
    </tr>
    <tr class="collapse multi-collapse<?php echo $id; ?>" id='<?php echo $id; ?>A'>
      <th scope="row">Label</th>
      <td><?php  if( $labelname ) echo $labelname; ?></td>
    </tr>
    <tr class="collapse multi-collapse<?php echo id; ?>" id='<?php echo $id; ?>B'>
      <th scope="row">Format</th>
       <td>
        <?php  if( $formatname ) echo $formatname;
               if( $formattext ) echo ", " . $formattext;  
               if( $formatdesc ) echo ", "  . $formatdesc;
          ?>
        </td>
    </tr>
    <tr class="collapse multi-collapse<?php echo $id; ?>" id='<?php echo $id; ?>C'>
      <th scope="row">Genres</th>
       <td>
         <?php   echo $genres; 
                 if( $styles ) echo ", "  . $styles; ?>
      </td>
      </tr>
    <tr class="collapse multi-collapse<?php echo $id; ?>" id='<?php echo $id; ?>B'>
      <th scope="row">Notes</th>
       <td>
       <?php //foreach ($release['basic_information']["notes"] as $notes) {
         //$note = $notes['value'];
       if($note) echo $note;
     //} ?>
      </td>      
    </tr>
</tbody>
</table>
 
            <div class="d-flex align-items-center justify-content-between rounded-pill bg-light px-3 py-2 mt-4">
              <p class="small mb-0">+<?php $adddate = $release['date_added']; echo date('m/d/y', strtotime(substr($adddate,0,10))) ?></p>
              <?php $todaydate = date("Y-m-d");
               if(strtotime($adddate) > strtotime('-10 days')) {
                ?>
              <div class="badge badge-danger px-3 rounded-pill font-weight-normal">New</div>
              <?php  } ?>
              <div><a class="btn btn-primary btn-sm" href="https://www.discogs.com/release/<?php echo $id ?>">Discogs <i class="fa-solid fa-arrow-up-right-from-square"></i></a></div>
            </div>
          </div>
        </div>
      </div>
	  

    <!-- End gallery Item

<?php } ?>

</div>
    <div class="py-5 text-center"><a href="#" class="btn btn-dark px-5 py-3 text-uppercase">BACK TO TOP</a></div>
  </div>
</div>


</body>
</html>
