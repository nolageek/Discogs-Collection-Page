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
</style>
</head>

<body>

<div class="container-fluid"> <!-- Outer Container -->

    <div class="row mx-auto">  <!-- Banner header -->
        
        <div class="p-2 shadow-sm rounded banner my-2 bg-body-tertiary">
          <div class="float-start">
          <i class="fa-fw fa-solid fa-circle-dot bs-success"></i> Discogs Collection Page for <?php echo $DISCOGS_USERNAME ?><br>
          <?php if ($release_id): get_release_information($release_id);?>
          <i class="fa-fw fa-solid fa-quote-right"></i> <?php echo $releaseinfo['title'];?> <i class="fa-fw fa-solid fa-people-group"></i> <?php echo implode (", ", array_column($releaseinfo['artists'], "name"));?>
		  <?php else: ?>
 		  <?php echo '<i class="fa-fw fa-regular fa-folder-open "></i> <span class="badge text-bg-secondary">' . $current_folder_name;?> <?php echo $current_folder_count;?> items</span> <span class="badge text-bg-success"><?php echo $sort_by ?></span> <span class="badge text-bg-info"><?php echo $order ?>ending</span>
		  <?php endif; ?>
        </div>
        <div class="float-end h-100 d-flex align-items-center">
        <button class="btn btn-primary" id="btnSwitch"  data-toggle="button" title="Toggle Dark/Light Mode"><i class="fa-fw fa-solid fa-circle-half-stroke"></i></button>
        </div>
      </div>

    </div> <!-- Banner header End -->

<!-- Pagination / Nav / Filter Bar-->
<nav class="navbar navbar-expand-lg bg-body-tertiary rounded sticky-top p-0 mx-auto">
<div class="container-fluid">


<!-- Start Pagination Navigation -->    
 <div class="btn-toolbar" role="toolbar" aria-label="Pagination Navigation">
<?php if (!$release_id) { ?>
  <button class="navbar-toggler me-2" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
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
 <!-- End Pagination Navigation -->
 

 <div class="collapse navbar-collapse justify-content-md-between justify-content-sm-start" id="navbarSupportedContent"> 
<!-- Start Search Form -->
<div class="btn-toolbar" role="toolbar" aria-label="Items per page">
<div class="input-group my-2 mx-1 col-md-1" role="group" aria-label="Search Form">

        <input type="text" id="searchInput" class="form-control w-10" placeholder="Search All">
</div> <!-- End Search Form -->
</div>

 <div class="btn-toolbar" role="toolbar" aria-label="Items per page">
 <div class="btn-group my-2 mx-1" role="group" aria-label="Per-Page">
  <button class="btn btn-primary text-uppercase dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false"><i class="fa-fw fa-regular fa-copy"></i> <?php echo $per_page; ?></button>
  <ul class="dropdown-menu justify-content-end">
      <li><h6 class="dropdown-header">Items Per-Page</h6></li>	
      <li><a class="dropdown-item" href="/?folder_id=<?php echo $folder_id; ?>&sort_by=<?php echo $sort_by; ?>&order=<?php echo $order; ?>&per_page=25&page=1"><i class="fa-regular fa-copy"></i> 25 Per-Page</a></li>
      <li><a class="dropdown-item" href="/?folder_id=<?php echo $folder_id; ?>&sort_by=<?php echo $sort_by; ?>&order=<?php echo $order; ?>&per_page=50&page=1"><i class="fa-regular fa-copy"></i> 50 Per-Page</a></li>
	  <li><a class="dropdown-item" href="/?folder_id=<?php echo $folder_id; ?>&sort_by=<?php echo $sort_by; ?>&order=<?php echo $order; ?>&per_page=100&page=1"><i class="fa-regular fa-copy"></i> 100 Per-Page</a></li>
  </ul>
	</div>

	<?php
}
else
{ ?>
 
 <div class="btn-toolbar" role="toolbar" aria-label="Items per page">
 <div class="btn-group my-2 mx-1" role="group" aria-label="Per-Page"> 
	<button type="button" class="btn btn-primary text-uppercase" onclick="javascript:history.go(-1)">Back</button>
	</div>

    <?php
} ?>


 <div class="btn-group my-2 mx-1" role="group" aria-label="Folder Selection">
  <button class="btn btn-primary text-uppercase dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
      <?php echo '<i class="fa-fw fa-regular fa-folder-open"></i> ' . $current_folder_name; ?>
    </button>
  <ul class="dropdown-menu">
  <li><h6 class="dropdown-header">Available Folders</h6></li>	
  <?php foreach ($folders['folders'] as $folder)
{

    $folderid = $folder['id'];
    $foldername = $folder['name'];
    $foldercount = $folder['count'];

    if ( $foldercount > 1 )
    { ?>
<li><a href="/?folder_id=<?php echo $folderid; ?>&sort_by=<?php echo $sort_by; ?>&order=<?php echo $order; ?>&per_page=<?php echo $per_page; ?>&page=1" title="View Folder '<?php echo $foldername; ?>'" class="dropdown-item text-capitalize <?php if ($current_folder_name == $foldername) { echo 'disabled'; } ?>"><i class="fa-fw fa-regular fa-folder-closed"></i> <?php echo $foldername; ?> <span class="badge text-bg-light"><?php echo $foldercount; ?></span></a></li>
<?php
    }
} ?>
    </ul>
</div>



<?php 
    $sort_type = array('added','artist','title','year');
    if(!$release_id) { ?>
  <div class="btn-group my-2 mx-1" role="group" aria-label="Sorting Options Tool Bar">	
  <button class="btn btn-primary text-uppercase dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
    
      <?php
              $sorted_icon = "";
              if ($sort_by == 'added') { $sorted_icon = "fa-clock";}
              if ($sort_by == 'artist') { $sorted_icon = "fa-people-group";}
              if ($sort_by == 'title') { $sorted_icon = "fa-quote-right";}
              if ($sort_by == 'year') { $sorted_icon = "fa-calendar-days";}

      echo "<i class='fa-fw fa-solid " . $sorted_icon . "'></i> Sort"; ?>
    </button>
  <ul class="dropdown-menu">
  <li><h6 class="dropdown-header">Sort-by Options</h6></li>	
<?PHP foreach ($sort_type as $sortby){
        $sorted_icon = "";
        if ($sortby == 'added') { $sorted_icon = "fa-solid fa-clock";}
        if ($sortby == 'artist') { $sorted_icon = "fa-people-group";}
        if ($sortby == 'title') { $sorted_icon = "fa-quote-right";}
        if ($sortby == 'year') { $sorted_icon = "fa-calendar-days";}

  ?>
    <li><a href="/?folder_id=<?php echo $folder_id; ?>&sort_by=<?PHP echo $sortby; ?>&order=<?php echo $order; ?>&per_page=<?php echo $per_page; ?>&page=<?php echo $page; ?>" title="Sort by <?PHP echo $sortby; ?>" class="dropdown-item text-capitalize <?php if ($sortby == $sort_by) echo " active"; ?>"><i class="fa-fw fa-solid <?PHP echo $sorted_icon; ?>"></i> <?PHP echo $sortby; ?></a></li>
    <?PHP } ?>
  </ul>
</div>
<div class="my-2 mx-1" role="group" aria-label="Ascending or Descending">
      <?PHP 
        $sorted_icon = "";
        if ($sort_by == 'added' || $sort_by == 'year') { 
            $asc_icon = "fa-arrow-down-9-1";
            $desc_icon = "fa-arrow-down-1-9";
        } elseif ($sort_by == 'artist' || $sort_by == 'title'){ 
            $asc_icon = "fa-solid fa-arrow-down-z-a";
            $desc_icon = "fa-arrow-down-a-z";
        }

  ?>
     <a href="/?folder_id=<?php echo $folder_id; ?>&sort_by=<?php echo $sort_by; ?>&order=asc&per_page=<?php echo $per_page; ?>" title="Toggle Sort: Ascending/Descending" class="btn btn-primary text-capitalize me-1 <?php if ($order == "asc") echo " none"; ?>"><i class="fa-fw fa-solid <?php echo $asc_icon; ?>"></i></a>
     <a href="/?folder_id=<?php echo $folder_id; ?>&sort_by=<?php echo $sort_by; ?>&order=desc&per_page=<?php echo $per_page; ?>&page=<?php echo $page; ?>" title="Ascending/Descending" class="btn btn-secondary text-capitalize me-1 <?php if ($order == "desc") echo " none"; ?>"><i class="fa-fw fa-solid <?php echo $desc_icon; ?>"></i></a> 
     </div>
<?php } ?>

<!-- Add a Random Button -->
<div class="btn-group my-2" role="group" aria-label="Sorting Options Tool Bar">	
    <a href="/?releaseid=random&folder_id=<?php echo $folder_id ?>" title="Random Release" class="btn btn-primary text-uppercase"><i class="fa-solid fa-circle-question"></i></a>
</div>
<!-- End of Random Button-->

</div>
</div>
</div> <!-- collapse -->
</nav> <!-- Pagination / Nav / Filter Bar-->

<div id="searchResults" class="row"></div>

<div id="releaseGallery" class="row"> <!-- Gallery of Releases -->
  
<?php if ($release_id) {
	  display_release_data($release_id);
	} else {
  //$currentLetter = null;  
    //$firstLetter = null;
	  foreach ($collection['releases'] as $release) {
		/*    if ($sort_by == "artist") {
	    $artists = implode(", ", array_column($release['basic_information']['artists'], "name"));
	    $firstLetter = strtoupper(substr($artists, 0, 1));
	    }
	    if ($sort_by == "added") {
	    $date_added = date('Y', strtotime(substr($release['date_added'],0,-4)));
	    $firstLetter = $date_added;
	    }

    
    if ($firstLetter !== $currentLetter) {
        // Output the letter heading
        echo '<div class="letter-heading border-bottom border-primary ms-10"><p class="h3">' . $firstLetter . '</p></div>';
        $currentLetter = $firstLetter;
    }*/	    
    
		display_gallery_item($release);
   }
  }	  

?>

</div> <!-- Gallery of Releases End -->


<div class="py-5 text-center"><a href="#" class="btn btn-dark px-5 py-3 text-uppercase">BACK TO TOP</a>
<!-- <br> Like this page? Run your own: <a href="https://github.com/nolageek/Discogs-Collection-Page"><i class="fa-brands fa-github"></i> / Discogs Collection Page </a> --> </div>
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
    function fetchData() {
        var searchTerm = $("#searchInput").val().toLowerCase();
        var releaseGalleryDiv = $("#releaseGallery");
        var searchResultsDiv = $("#searchResults");
	var url = '<?php echo $DISCOGS_ALL_CACHE_FILE; ?>';

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
                    return title.includes(searchTerm) || artists.some(function(artist) {
                        return artist.includes(searchTerm);
                    }) || genres.some(function(genre) {
                        return genre.includes(searchTerm);
                    }) || styles.some(function(style) {
                        return style.includes(searchTerm);
                    });
                });

                if (filteredReleases.length === 0) {
                    searchResultsDiv.append("<div class='container-fluid bg-warning bg-gradient'><div class='row'><div class='mx-auto'>No results found.</div></div></div>");
                } else {
                    filteredReleases.forEach(function(release) {
                        var releaseHtml = '<div class="col-xl-2 col-lg-3 col-md-6 col-sm-6 my-3">';
                        releaseHtml += '<div class="card h-100 new">';
                        releaseHtml += `<a href="/?releaseid=${release.id}"> <img class="card-img-top rounded p-2" loading="lazy" src="<?php echo $IMAGE_PATH_ROOT_URL; ?>${release.id}.jpeg" alt="${release.basic_information.title}"></a>`;

                        releaseHtml += '<div class="card-body d-flex flex-column"><div class="d-flex flex-column mt-auto">';
                        releaseHtml += '<h5 class="card-title">' + release.basic_information.title + '</h5>';
                        releaseHtml += '<p class="card-text">' + release.basic_information.artists.map(function(artist) {
                            return artist.name;
                        }).join(", ") + '</p>';
                        releaseHtml += '</div></div></div>';
                        searchResultsDiv.append(releaseHtml);
                    });
                }
            });
        } //else {
          //  searchResultsDiv.append("<p>Enter a search term to get results.</p>");
     //   }
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
