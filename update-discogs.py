# Notes for python 3.x:
#
# Change all occurances of urllib to urllib.request, including the following
# import statement as well as all instances of urllib.urlretrieve to
# urllib.request.urlretrieve

import getopt, sys, json, urllib, os.path, time

# You can either hardcode your Discogs token and username here
# or you can pass them via the --token and/or --username arguments
DISCOGS_TOKEN = "ZylBbtsdWZrBEdEwhAdVSBZuuthKdDmqJvllmjBg"
DISCOGS_USERNAME = "nolageek"

# As of this release set PER to a value larger than your total collection.
# At one point I may add the ability to create paginated files.
PER = "500"
#
# Setting up default values for variables
# No need to change anything below.
TYPE = ""
DISCOGS_API_URL = "https://api.discogs.com"
DATA_DIR = "./"
ALL_RELEASES_DATA_FILE = "collection.json"
FOLDERS_DATA_FILE = "folders.json"
IMG_DIR = "./img/"


# Remove 1st argument from the
# list of command line arguments
argumentList = sys.argv[1:]

# Options
options = "ht:"

# Long options
long_options = [
    "help",
    "type=",
    "updateall",
    "updatefolderlist",
    "updatefolderdata",
    "updateimages",
    "token=",
    "username=",
]

# Main loop that takes your option and decides what it's going to do next.


def main():
    try:
        # Parsing argument
        arguments, values = getopt.getopt(argumentList, options, long_options)

        # checking each argument
        for currentArgument, currentValue in arguments:

            if currentArgument in ("-h", "--help"):
                print("Displaying Help")
            elif currentArgument in ("--updateall"):
                print("Task: Updating ALL")
                print("Task: Updating Folder LIST")
                download_folder_list()
                print("Task: Updating Folder DATA")
                download_folder_data()
                print("Task: Updating IMAGES")
                update_images()
            elif currentArgument in ("--updatefolderlist"):
                print("Task: Updating Folder LIST")
                download_folder_list()
            elif currentArgument in ("--updatefolderdata"):
                print("Task: Updating Folder LIST")
                download_folder_data()
            elif currentArgument in ("--updateimages"):
                print("Task: Updating IMAGES")
                update_images()
            elif currentArgument in ("--token"):
                DISCOGS_TOKEN = currentValue
                print("Token: ", DISCOGS_TOKEN)
            elif currentArgument in ("--username"):
                DISCOGS_USERNAME = currentValue
                print("Username: ", DISCOGS_USERNAME)

    except getopt.error as err:
        # output error, and return with an error code
        print(str(err))


# This downloads the folder data for the collection of the username specified in json format.
# Each account has a folder id of '0' that contains ALL of their items, and a '1' folder that
# contains all items that are not in any other folders (uncategorized.) All other folders
# have a unique numeric ID.
def download_folder_list():
    if DISCOGS_TOKEN == "" or DISCOGS_USERNAME == "":
        print("Missing Discogs Token or Username")
        exit()
    FOLDERURL = (
        DISCOGS_API_URL
        + "/users/"
        + DISCOGS_USERNAME
        + "/collection/folders?token="
        + DISCOGS_TOKEN
    )
    print("Downloading Folder List To " + DATA_DIR + FOLDERS_DATA_FILE)
    urllib.urlretrieve(FOLDERURL, DATA_DIR + FOLDERS_DATA_FILE)
    
def download_collection_data():
    if DISCOGS_TOKEN == "" or DISCOGS_USERNAME == "":
        print("Missing Discogs Token or Username")
        exit()
    FOLDERURL = (
        DISCOGS_API_URL
        + "/users/"
        + DISCOGS_USERNAME
        + "/collection/folders?token="
        + DISCOGS_TOKEN
    )
    print("Downloading Collection List To " + DATA_DIR + FOLDERS_DATA_FILE)
    urllib.urlretrieve(FOLDERURL, DATA_DIR + ALL_RELEASES_DATA_FILE)


# This next function parses the above folder data and loops through each folder and downloads json files
# containing the collection sorted by both date-added and by artist (each in ascending and descending order.)
# I was going to add other sorting methods (title for example) but it didn't seem that useful and I wanted
# to cut down on the number of API calls. Each file has a name in the following format:
# <ID>-<sort>-<order>.json
# For example the list(s) of all items in the collection would be 0-added-asc.json, and 0-added-desc.json
def download_folder_data():
    with open(DATA_DIR + FOLDERS_DATA_FILE) as folderdata:
        folders = json.load(folderdata)
    for folder in folders["folders"]:
        print(
            "Downloading Data For Folder "
            + folder["name"]
            + " (ID: "
            + str(folder["id"])
            + ", Count: "
            + str(folder["count"])
            + ")"
        )
        urllib.urlretrieve(
            DISCOGS_API_URL
            + "/users/"
            + DISCOGS_USERNAME
            + "/collection/folders/"
            + str(folder["id"])
            + "/releases?sort=added&sort_order=asc&per_page="
            + PER
            + "&token="
            + DISCOGS_TOKEN,
            DATA_DIR + str(folder["id"]) + " -added-asc.json",
        )
        urllib.urlretrieve(
            DISCOGS_API_URL
            + "/users/"
            + DISCOGS_USERNAME
            + "/collection/folders/"
            + str(folder["id"])
            + "/releases?sort=added&sort_order=desc&per_page="
            + PER
            + "&token="
            + DISCOGS_TOKEN,
            DATA_DIR + str(folder["id"]) + "-added-desc.json",
        )
        urllib.urlretrieve(
            DISCOGS_API_URL
            + "/users/"
            + DISCOGS_USERNAME
            + "/collection/folders/"
            + str(folder["id"])
            + "/releases?sort=artist&sort_order=asc&per_page="
            + PER
            + "&token="
            + DISCOGS_TOKEN,
            DATA_DIR + str(folder["id"]) + "-artist-asc.json",
        )
        urllib.urlretrieve(
            DISCOGS_API_URL
            + "/users/"
            + DISCOGS_USERNAME
            + "/collection/folders/"
            + str(folder["id"])
            + "/releases?sort=artist&sort_order=desc&per_page="
            + PER
            + "&token="
            + DISCOGS_TOKEN,
            DATA_DIR + str(folder["id"]) + "-artist-desc.json",
        )


# This helper function downloads an image when given the url to the image and the release ID (to be used as the file basename.
# The "name" that can be passed to the function is merely for display while the scripts runs.
# Image files are located in the ./img directory and are named after the release ID and use the .jpeg extension. ie: ./image/<ID>.jpeg
def download_image(url, id, name):
    if name == "":
        name = "No name Given"
    print("Downloading image for " + name)
    #urllib.urlretrieve(url, IMG_DIR + str(id) + ".jpeg")


# The following function parses the list of all items and if an image file is not found it will be downloaded using download_image().
def update_images():
    loop = 1
    with open(DATA_DIR + ALL_RELEASES_DATA_FILE) as releasedata:
        releases = json.load(releasedata)
        total_releases = len(releases["releases"])
    for images in releases["releases"]:
        print(str(loop) + "/" + str(total_releases) + ": ")
        if os.path.exists(IMG_DIR + str(images["basic_information"]["id"]) + ".jpeg"):
            print(
                IMG_DIR
                + str(images["basic_information"]["id"])
                + ".jpeg ("
                + images["basic_information"]["title"]
                + ") already exists."
            )
        else:
            download_image(
                images["basic_information"]["cover_image"],
                str(images["basic_information"]["id"]),
                images["basic_information"]["title"],
            )
        if not loop % 10:
            print("Pausing for 5 seconds...")
            time.sleep(5)
        else:
            print("Pausing for 2 seconds...")
            time.sleep(2)
        loop = loop + 1



# Lets run the main() program!
main()
