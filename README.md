# Discogs Collection Page

These PHP and python scripts will generate a friendly view of your Discogs media collection organized by your Discogs folders.

 ![image](https://user-images.githubusercontent.com/2931834/195755371-078b95ce-2621-4110-928c-4754450845eb.png)
 
## Current Functionality:
* Pulls down list of folders.
* Displays gallery view of album covers, with release and artist names and some additional information.
* Stores JSON data locally to cut down on API calls (updated via cron job).
* Stores cover images locally to cut down on API calls (updated via cron job).
* I do not intend to display your items for sale.

## To Do: 
* Get better at python
* Lots of tweaks to add more information
* Create single release view
* Auto crop images to square format
* Add check to avoid re-downloading folder data that hasn't changed.

## To Fix:
* Figure out logic to display Notes in expanded view / release view.


# Requirements:
* PHP 7.4 (Recommended)
* Python 2.x (See notes below)
* Python modules getopt, sys, json, urllib.request, and os.path
* A Discogs personal access token to access the Discogs API: https://www.discogs.com/settings/developers

## Notes for python 3.x:
 Change all occurances of `urllib` in the python script to `urllib.request`, including the **import** statement as well as all instances of `urllib.urlretrieve` to
 `urllib.request.urlretrieve`.

# Usage:

* Upload the files to your web server in it's own directory off of your public_html. Be sure to create subdirectories named **img** and **json**.
* You can either hardcode your Discogs token and username in the python script, or you can pass them via the *--token* and/or *--username* arguments.
* In SSH run the discogs-update.py script with the **updateall** *--type* (*-t*) argument, ie: `python ./discogs-update.py -t updateall`

  Or, if you would like to pass the token on the command line, ie: `python ./discogs-update.py -t updateall --username <username> --token <ABC123>`
  
  This will download your list of folders, your releases in each folder sorted by *added* and by *artists* (both ascending and descending for each), as well as all images for the items in your collection. The script will not overwrite images that already exist but it will overwrite data files.
  
* At this point you should have a working website with your collection sortable by artist and by date added.
* One it's up and running you can use `-t updatefolderlist`, `-t updatefolders`, and `-t updateimages` if you will to avoid running **updateall**.
* Set up a cron job to run the *updateall* command as often as you wish (try not to abuse their servers so your account doesn't get blocked.)

```
$ python ./discogs-update.py -t updateall
('Type: ', 'updateall')
Downloading Folder List To ./json/folders.json
Downloading Data For Folder All (ID: 0, Count: 321)
Downloading Data For Folder CDs (ID: 3220306, Count: 77)
Downloading Data For Folder Uncategorized (ID: 1, Count: 0)
Downloading Data For Folder Vinyl 10 Inch (ID: 3946258, Count: 2)
Downloading Data For Folder Vinyl 12 Inch (ID: 3220309, Count: 232)
./img/4674050.jpeg (Two Lectures By Nathaniel Branden: The Psychology Of Pleasure - Social Metaphysics) already exists.
./img/19427773.jpeg (Black Pumas) already exists.
./img/556175.jpeg (The Booker T. Set) already exists.
./img/652381.jpeg (Introduce Yourself) already exists.
./img/22700786.jpeg (Get Behind Me Satan) already exists.
...
./img/9212076.jpeg (The Man Who Could Fall Backwards) already exists.
./img/1542894.jpeg (August And Everything After) already exists.
./img/1513456.jpeg (Woodstock 99) already exists.
./img/1386071.jpeg (Tigerlily) already exists.
./img/1248761.jpeg (War All The Time) already exists.
```
