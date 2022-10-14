#download most recent single release
#if added date is newer than current files:
#    update all data files
#    download all new images
# https://www.geeksforgeeks.org/command-line-arguments-in-python/

# install validators module
# pip install validators

import getopt, sys, json, urllib.request, os.path

# Setting up default values for arguments
# 
# You can either hardcode your Discogs token and username here
# or you can pass them via the --token and/or --username arguments
DISCOGS_TOKEN = ""
DISCOGS_USERNAME = ""
# As of this release set PER to a value larger than your total collection.
# At one point I may add the ability to create paginated files.
PER = "500"

DISCOGS_API_URL = "https://api.discogs.com"
DATA_DIR = "./json/"
ALL_RELEASES_DATA_FILE = "0-added-desc.json"
FOLDERS_DATA_FILE = "folders.json"
IMG_DIR = "./img/"


# Remove 1st argument from the
# list of command line arguments
argumentList = sys.argv[1:]
 
# Options
options = "ht:"
 
# Long options
long_options = ["help", "type=","token=", "username="]
 
try:
    # Parsing argument
    arguments, values = getopt.getopt(argumentList, options, long_options)
     
    # checking each argument
    for currentArgument, currentValue in arguments:
        
 
        if currentArgument in ("-h", "--help"):
            print ("Displaying Help")
        elif currentArgument in ("-t", "--type"):
            TYPE = currentValue
            print ("Type: ", TYPE)
        elif currentArgument in ("--per"):
            PER = currentValue
            print ("Per: ", TYPE)
        elif currentArgument in ("--token"):
            DISCOGS_TOKEN = currentValue
            print ("Token: ", DISCOGS_TOKEN)
        elif currentArgument in ("--username"):
            DISCOGS_USERNAME = currentValue
            print ("Username: ", DISCOGS_USERNAME)
			
except getopt.error as err:
    # output error, and return with an error code
    print (str(err))
   
def main():
	if TYPE == "updateall":
			download_folder_list()
			download_folder_data()
			update_images()
			
	elif TYPE == "updatefolderlist":
			download_folder_list()
				
	elif TYPE == "updatefolders":
			download_folder_list()
			download_folder_data()
			
	elif TYPE == "updateimages":		
			update_images()
	
	else:
			print("Invalid action type")
		
def download_folder_list():
	if DISCOGS_TOKEN == "" or DISCOGS_USERNAME == "":
		print("Missing Discogs Token or Username")
		exit()
	FOLDERURL = DISCOGS_API_URL + "/users/" + DISCOGS_USERNAME + "/collection/folders?token=" + DISCOGS_TOKEN
	print("Downloading Folder List To " + DATA_DIR + FOLDERS_DATA_FILE)
	urllib.request.urlretrieve(FOLDERURL, DATA_DIR + FOLDERS_DATA_FILE)
	
def download_folder_data():
	with open(DATA_DIR + FOLDERS_DATA_FILE) as folderdata:
		folders = json.load(folderdata)
	for folder in folders['folders']:
		print("Downloading Data For Folder " + folder['name'] + " (ID: " + str(folder['id']) + ", Count: " + str(folder['count']) + ")")
		urllib.request.urlretrieve(DISCOGS_API_URL + "/users/" + DISCOGS_USERNAME + "/collection/folders/" + str(folder['id']) + "/releases?sort=added&sort_order=asc&per_page=500&token=" + DISCOGS_TOKEN,DATA_DIR + str(folder['id']) + " -added-asc.json")
		urllib.request.urlretrieve(DISCOGS_API_URL + "/users/" + DISCOGS_USERNAME + "/collection/folders/" + str(folder['id']) + "/releases?sort=added&sort_order=desc&per_page=500&token=" + DISCOGS_TOKEN,DATA_DIR + str(folder['id']) + "-added-desc.json")
		urllib.request.urlretrieve(DISCOGS_API_URL + "/users/" + DISCOGS_USERNAME + "/collection/folders/" + str(folder['id']) + "/releases?sort=artist&sort_order=asc&per_page=500&token=" + DISCOGS_TOKEN,DATA_DIR + str(folder['id']) + "-artist-asc.json")
		urllib.request.urlretrieve(DISCOGS_API_URL + "/users/" + DISCOGS_USERNAME + "/collection/folders/" + str(folder['id']) + "/releases?sort=artist&sort_order=desc&per_page=500&token=" + DISCOGS_TOKEN,DATA_DIR + str(folder['id']) + "-artist-desc.json")

def download_image(url,id, name):
	if name == "":
		name = "No name Given"
	print("Downloading image for " + name)
	urllib.request.urlretrieve(url, IMG_DIR + str(id) + ".jpeg")
	
def update_images():
	with open(DATA_DIR + ALL_RELEASES_DATA_FILE) as releasedata:
		releases = json.load(releasedata)
	for images in releases['releases']:
		if os.path.exists(IMG_DIR + str(images['basic_information']['id']) + ".jpeg"):
			print(IMG_DIR + str(images['basic_information']['id']) + ".jpeg (" + images['basic_information']['title'] + ") already exists.")
		else:
			download_image(images['basic_information']['cover_image'],str(images['basic_information']['id']),images['basic_information']['title'])

main()