# Discogs Collection Page

This PHP script will generate a friendly view of your Discogs media collection organized by your Discogs folders.

![image](https://github.com/nolageek/Discogs-Collection-Page/assets/2931834/0cfc80cd-f30e-483d-aa65-7fd33f89a7f5)



Current and preview example can be found on my personal site: http://discogs.nolageek.com / http://discogsdev.nolageek.com

## Current Functionality:
## New !!
* Dark / Light mode toggle
* Dynamic search functionality
* Improoved menu bar
## End New :(
* Mobile friendly-ish
* Displays gallery view of album covers, with title and artist name(s).
* Auto paginates (50 per page by default,)
* Gallery images link to single release view with more information about release, including ratings/notes on copy in collection.
* "Random Release" button will display single release view of a random item in your collection (from the current folder.)  Thank you *laminateddenim* for the suggestion!
* Dynamic search of collection.
  
## To Do: 
* Improve and refactor method of initilizing collection and downloading images for the first time.

# Requirements:
* A Discogs personal access token to access the Discogs API: https://www.discogs.com/settings/developers
* PHP 7.4+ (Recommended)

## Setup and Use
* Upload to your web server's home directory.
* Edit both settings.php.sample and discogs-update.php.sample and add your discogs username and API token. Be sure to save as settings.php and discogs-update.py, respectively.
* Create the following directories:
  * ./img/
  * ./jsondata/
* run the following command: python ./update-discogs.py --updateall
  * This will download folder data and all images in your collection. This may take a long time if you have a large collection.*
* Once finished, check your site and see if it worked! :)
