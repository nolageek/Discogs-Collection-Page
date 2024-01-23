# Discogs Collection Page

This PHP script will generate a friendly view of your Discogs media collection organized by your Discogs folders.

![image](https://user-images.githubusercontent.com/2931834/211911784-cc7f0dbf-b30b-40bb-ade3-1afc05771a85.png)

Current and preview example can be found on my personal site: http://discogs.nolageek.com

## Current Functionality:
* Mobile friendly-ish
* Displays gallery view of album covers, with title and artist name(s).
* Auto paginates (50 per page by default,)
* Gallery images link to single release view with more information about release, including ratings/notes on copy in collection.
* "Random Release" button will display single release view of a random item in your collection (from the current folder.)  Thank you *laminateddenim* for the suggestion!
* Dynamic search of collection.
  
## To Do: 
* Add Dark / Light mode toggle
* Improve dynamic search functionality
* Improove menu bar to make it more usuable with very large collections, and collections with lots of folders.

# Requirements:
* A Discogs personal access token to access the Discogs API: https://www.discogs.com/settings/developers
* PHP 7.4+ (Recommended)
