# Discogs Collection Page

This PHP python script will generate a friendly view of your Discogs media collection.

## Current Functiontionality:
* Pulls down list of folders.
* Displays gallery view of album covers, with release and artist names and some additional information.
* Stores JSON data locally to cut down on API calls (updated via cron job).
* Stores cover images locally to cut down on API calls (updated via cron job).

## To Do: 
* Get better at python
* Lots of tweaks to add more information
* Create single release view
* Auto crop images to square format

## To Fix:
* Figure out logic to display Notes in expanded view / release view.


# Requirements:
* PHP 7.4 (Recommended)
* Python 3.9
* Python modules getopt, sys, json, urllib.request, and os.path
* A Discogs personal access token to access the Discogs API: https://www.discogs.com/settings/developers

# Usage:

* Upload the files to your web server in it's own directory off of your public_html. Be sure to create subdirectories named **img** and **json**.
* You can either hardcode your Discogs token and username in the python script, or you can pass them via the --token and/or --username arguments.
* In SSH run the discogs-update.py script with the **updateall** type (-t) argument: `python3 ./discogs-update.py -t updateall`

  Or, if you would like to pass the token on the command line: `python3 ./discogs-update.py -t updateall --username <username> --token <ABC123>`
  
  This will download your list of folders, your releases in each folder sorted by *added* and by *artists* (both ascending and descending for each), as well as all images for the items in your collection. The script will not overwrite images that already exist but it will overwrite data files.
  
* At this point you should have a working website with your collection sortable by artist and by date added.
  
 ![image](https://user-images.githubusercontent.com/2931834/195755371-078b95ce-2621-4110-928c-4754450845eb.png)
 
 * One it's up and running you can use `-t updatefolderlist`, `-t updatefolders`, and `-t updateimages` if you will to avoid running **updateall**.
 * Set up a cron job to run the updateall command as often as you wish (try not to abuse their servers so your account doesn't get blocked.)
