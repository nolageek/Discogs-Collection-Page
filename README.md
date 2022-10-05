# Discogs Collection Page

This PHP and shell script will generate a friendly view of your Discogs media collection.


Current Functiontionality:
* Pulls down list of folders (but currently uses my hardcoded folder ID)
* Displays gallery view of album covers, with release and artist names
* Stores JSON data locally to cut down on API calls (updated via cron job).
* Stores cover images locally to cut down on API calls (updated via cron job).

To Do: 
* Get better at python
* Convert php/base support scripts to a single python script.
* Lots of tweaks to add more information
* Create single release view
* build menu from list of folders pulled from discogs' API
* Auto crop images to square format
* Artist view

To Fix:
* If cover image is missing, it breaks the gallery view for that release.
* Figure out logic to display Notes in expanded view / release view.
