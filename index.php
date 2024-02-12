<?php
require('functions.php')
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>


<title>Discogs Collection Page</title>
<meta name="viewport" content="width=device-width, initial-scale=.8">

<script src="https://kit.fontawesome.com/7e1a0bb728.js" crossorigin="anonymous"></script>
<script src="https://code.jquery.com/jquery-3.7.1.slim.min.js" integrity="sha256-kmHvs0B+OpCW5GVHUNjv9rOmY0IvSIRcf7zGUDTDQM8=" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.9.2/umd/popper.min.js" integrity="sha512-2rNj2KJ+D8s1ceNasTIex6z4HWyOnEYLVC3FigGOmyQCZc2eBXKgOxQmo3oKLHyfcj53uz4QMsRCWNbLd32Q1g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<link href="https://gitcdn.github.io/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css" rel="stylesheet">
<script src="https://gitcdn.github.io/bootstrap-toggle/2.2.2/js/bootstrap-toggle.min.js"></script>



<!--<link href="https://cdn.jsdelivr.net/npm/bootstrap-dark-5@1.1.3/dist/css/bootstrap-night.min.css" rel="stylesheet">-->

<style>
/*
*
* ==========================================
* FOR DEMO PURPOSE
* ==========================================
*
*/

.list-group-item { word-wrap: break-word; }

// Class

.visible {
  visibility: visible;
}
.invisible {
  visibility: hidden;
}
.none {
  display: none;
}
</style>

</head>

<body>

<div class="container-fluid"> <!-- Outer Container -->


    <div class="row">  <!-- Banner header -->
      <div class="col-12 mx-auto my-1">
        <div class="p-2 shadow-sm rounded banner">

          <p><span class="text-primary"><i class="fa-solid fa-circle-dot"></i> Discogs Collection Page for <?php echo $DISCOGS_USERNAME ?></span><br>
          <span class="text-secondary"><?php if ($release_id): get_release_information($release_id);?>
          <i class="fa-solid fa-circle-dot"></i> <?php echo $releaseinfo['title'];?>" <i class="fa-solid fa-user-group"></i> <?php echo implode (", ", array_column($releaseinfo['artists'], "name"));?></span></p>
		  <?php else: ?>
 		  <?php echo '<i class="fa-regular fa-folder-open"></i> <span class="badge text-bg-secondary">' . $current_folder_name;?> <?php echo $current_folder_count;?> items</span> <span class="badge text-bg-success"><?php echo $sort_by ?></span> <span class="badge text-bg-info"><?php echo $order ?>ending</span></span></p>
		  <?php endif; ?>
        </div>
      </div>
    </div> <!-- Banner header End -->

<!-- Pagination / Nav / Filter Bar-->
<nav class="navbar navbar-expand-lg bg-body-tertiary sticky-top p-0">
<div class="container-fluid">
<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
 <div class="btn-toolbar" role="toolbar" aria-label="Toolbar with button groups">
     


<?php if (!$release_id) { ?>
<div class="btn-group my-2 mx-1 d-none d-sm-block" role="group" aria-label="Pagination">
<?php $url = '/?folder_id=' . $folder_id .'&sort_by=' . $sort_by . '&order=' . $order .'&per_page=' . $per_page . '&page='; ?>
<a class="btn btn-primary text-uppercase<?php if ($page == 1) echo " disabled"; ?>" href="/?folder_id=<?php echo $folder_id; ?>&sort_by=<?php echo $sort_by; ?>&order=<?php echo $order; ?>&per_page=<?php echo $per_page; ?>&page=<?php if ($page != 1) echo (intval($page) - 1); ?>" tabindex="-1"><i class="fa-solid fa-caret-left"></i></a><?php $total_pages = $collection['pagination']['pages']; echo paginate($page, $total_pages, $url, 5); ?>
<a class="btn btn-primary text-uppercase<?php if ($page == $collection['pagination']['pages']) echo " disabled"; ?>" href="/?folder_id=<?php echo $folder_id; ?>&sort_by=<?php echo $sort_by; ?>&order=<?php echo $order; ?>&per_page=<?php echo $per_page; ?>&page=<?php if ($page != $total_pages) echo (intval($page) + 1); ?>" tabindex="-1"><i class="fa-solid fa-caret-right"></i></a>
</div>
  
<div class="btn-group my-2 mx-1 d-block d-sm-none" role="group" aria-label="Pagination">
<?php $url = '/?folder_id=' . $folder_id .'&sort_by=' . $sort_by . '&order=' . $order .'&per_page=' . $per_page . '&page='; ?>
<a class="btn btn-primary text-uppercase<?php if ($page == 1) echo " disabled"; ?>" href="/?folder_id=<?php echo $folder_id; ?>&sort_by=<?php echo $sort_by; ?>&order=<?php echo $order; ?>&per_page=<?php echo $per_page; ?>&page=<?php if ($page != 1) echo (intval($page) - 1); ?>" tabindex="-1"><i class="fa-solid fa-caret-left"></i></a><?php echo paginate($page, $total_pages, $url, 3); ?>
<a class="btn btn-primary text-uppercase<?php if ($page == $collection['pagination']['pages']) echo " disabled"; ?>" href="/?folder_id=<?php echo $folder_id; ?>&sort_by=<?php echo $sort_by; ?>&order=<?php echo $order; ?>&per_page=<?php echo $per_page; ?>&page=<?php if ($page != $total_pages) echo (intval($page) + 1); ?>" tabindex="-1"><i class="fa-solid fa-caret-right"></i></a>
  </div>
 </div>
 
 <div class="collapse navbar-collapse justify-content-md-between justify-content-sm-start" id="navbarSupportedContent"> 
 <form class="my-2 mx-1">
    <div class="mx-auto ">
        <input type="text" id="searchInput" class="form-control" placeholder="Search...">
    </div>
</form>

 <div class="btn-toolbar" role="toolbar" aria-label="Items per page">
 <div class="btn-group my-2 mx-1" role="group" aria-label="Per-Page">
  <button class="btn btn-primary text-uppercase dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"><?php echo $per_page; ?> Per Page</button>
  <ul class="dropdown-menu">
      <li><a class="dropdown-item" href="/?folder_id=<?php echo $folder_id; ?>&sort_by=<?php echo $sort_by; ?>&order=<?php echo $order; ?>&per_page=25&page=1">25</a></li>
      <li><a class="dropdown-item" href="/?folder_id=<?php echo $folder_id; ?>&sort_by=<?php echo $sort_by; ?>&order=<?php echo $order; ?>&per_page=50&page=1">50</a></li>
	  <li><a class="dropdown-item" href="/?folder_id=<?php echo $folder_id; ?>&sort_by=<?php echo $sort_by; ?>&order=<?php echo $order; ?>&per_page=100&page=1">100</a></li>
  </ul>
	
	<?php
}
else
{ ?>

	<button type="button" class="btn btn-primary text-uppercase" onclick="javascript:history.go(-1)">Back</button>
    <?php
} ?>
</div>

 <div class="btn-group my-2 mx-1" role="group" aria-label="Folder Selection">
  <button class="btn btn-primary text-uppercase dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
      <?php echo $current_folder_name . ' <span class="badge text-bg-secondary">'. $current_folder_count . '</span>'; ?>
    </button>
  <ul class="dropdown-menu">
  <?php foreach ($folders['folders'] as $folder)
{

    $folderid = $folder['id'];
    $foldername = $folder['name'];
    $foldercount = $folder['count'];

    if ($foldercount > 1 && $current_folder_name != $folder['name'])
    { ?>
<li>
    <a href="/?folder_id=<?php echo $folderid; ?>&sort_by=<?php echo $sort_by; ?>&order=<?php echo $order; ?>&per_page=<?php echo $per_page; ?>&page=1" title="View Folder '<?php echo $foldername; ?>'" class="dropdown-item"><?php echo $foldername; ?> <span class="badge text-bg-secondary"><?php echo $foldercount; ?></span></a></li>
<?php
    }
} ?>
    </ul>
</div>
 <div class="btn-group my-2 mx-1" role="group" aria-label="Sorting Options Tool Bar">	
 
<?php if(!$release_id) { ?>	
     <a href="#" class="btn btn-info text-uppercase disabled me-2"><i class="fa-solid fa-gear"></i></a>
     <a href="/?folder_id=<?php echo $folder_id; ?>&sort_by=added&order=<?php echo $order; ?>&per_page=<?php echo $per_page; ?>&page=<?php echo $page; ?>" title="Toggle Sort: Artist / Added" class="btn btn-info text-uppercase me-2<?php if ($sort_by == "added") echo " none"; ?>"><i class="fa-solid fa-table-cells"></i></a>
     <a href="/?folder_id=<?php echo $folder_id; ?>&sort_by=artist&order=<?php echo $order; ?>&page=<?php echo $page; ?>" title="Toggle Sort: Artist / Added" class="btn btn-info text-uppercase me-2<?php if ($sort_by == "artist") echo " none"; ?>"><i class="fa-solid fa-user-group"></i></a> 
     <a href="/?folder_id=<?php echo $folder_id; ?>&sort_by=<?php echo $sort_by; ?>&order=asc&per_page=<?php echo $per_page; ?>" title="Toggle Sort: Ascending/Descending" class="btn btn-secondary text-uppercase me-2<?php if ($order == "asc") echo " none"; ?>"><i class="fa-solid fa-sort-down"></i></a>
     <a href="/?folder_id=<?php echo $folder_id; ?>&sort_by=<?php echo $sort_by; ?>&order=desc&per_page=<?php echo $per_page; ?>&page=<?php echo $page; ?>" title="Ascending/Descending" class="btn btn-secondary text-uppercase me-2<?php if ($order == "desc") echo " none"; ?>"><i class="fa-solid fa-sort-up"></i></a> 

    
<?php } ?>	

<!-- Add a Random Button -->
    <a href="/?releaseid=random&folder_id=<?php echo $folder_id ?>" title="Random Release" class="btn btn-info text-uppercase me-2"><i class="fa-solid fa-circle-question"></i></a>
    <button class="btn btn-secondary" id="btnSwitch" title="Toggle Dark/Light Mode"><i class="fa-solid fa-regular fa-moon"></i></button>
    
  </div>
<!-- End of Random Button-->

</div> <!-- collapse -->

</nav> <!-- Pagination / Nav / Filter Bar-->

<div id="searchResults" class="row"></div>

<div id="releaseGallery" class="row"> <!-- Gallery of Releases -->
  
<?php if ($release_id) {
	  display_release_data($release_id);
	} else {
	  foreach ($collection['releases'] as $release) { 
		display_gallery_item($release);
}
  }	  

?>

</div> <!-- Gallery of Releases End -->


    <div class="py-5 text-center"><a href="#" class="btn btn-dark px-5 py-3 text-uppercase">BACK TO TOP</a>
	<br> Like this page? Run your own: <a href="https://github.com/nolageek/Discogs-Collection-Page"><i class="fa-brands fa-github"></i> / Discogs Collection Page </a></div>
	<div class="d-block d-sm-none">xs</div>
<div class="d-none d-sm-block d-md-none">sm</div>
<div class="d-none d-md-block d-lg-none">md</div>
<div class="d-none d-lg-block d-xl-none">lg</div>
<div class="d-none d-xl-block">xl</div>

  </div> <!-- Outer Container End -->

<!-- Add this before the closing </body> tag -->


<script src="https://code.jquery.com/jquery-3.6.0.min.js" crossorigin="anonymous"></script>

<script>
document.getElementById('btnSwitch').addEventListener('click',()=>{
    if (document.documentElement.getAttribute('data-bs-theme') == 'dark') {
        document.documentElement.setAttribute('data-bs-theme','light')
    }
    else {
        document.documentElement.setAttribute('data-bs-theme','dark')
    }
})

$(document).ready(function(){
    $("#searchInput").on("input", function() {
        var searchTerm = $(this).val().toLowerCase();

        // Get references to the divs
        var releaseGalleryDiv = $("#releaseGallery");
        var searchResultsDiv = $("#searchResults");

        // Toggle visibility based on the search term
        if (searchTerm) {
            // If there is a search term, hide releaseGallery and show searchResults
            releaseGalleryDiv.hide();
            searchResultsDiv.show();
        } else {
            // If the search term is empty, show releaseGallery and hide searchResults
            releaseGalleryDiv.show();
            searchResultsDiv.hide();
        }

        // Clear previous search results
        searchResultsDiv.empty();

        // Load JSON data from a local file
        $.getJSON('<?php echo $DISCOGS_CACHE_FILE; ?>', function(data) {
            var filteredReleases = data.releases.filter(function(release) {
                var title = release.basic_information.title.toLowerCase();
                var artists = release.basic_information.artists.map(function(artist) {
                    return artist.name.toLowerCase();
                }).join(", ");
                return title.includes(searchTerm) || artists.includes(searchTerm);
            });

            // Display the search results
            filteredReleases.forEach(function(release) {
                var releaseHtml = '<div class="col-md-4 mb-4">';
                releaseHtml += '<div class="card">';
                releaseHtml += `<a href="/?releaseid=${release.id}"> <img src="<?php echo $IMAGE_PATH_ROOT_URL; ?>${release.id}.jpeg" class="card-img-top" alt="${release.basic_information.title}"></a>`;

                releaseHtml += '<div class="card-body">';
                releaseHtml += '<h5 class="card-title">' + release.basic_information.title + '</h5>';
                releaseHtml += '<p class="card-text">' + release.basic_information.artists.map(function(artist) {
                    return artist.name;
                }).join(", ") + '</p>';
                releaseHtml += '</div></div></div>';
                searchResultsDiv.append(releaseHtml);
            });
        });
    });
});

</script>


</body>
</html>
