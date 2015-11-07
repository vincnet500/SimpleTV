# SimpleTV
A simple console TV application (for french tv stations)

# Prerequisites
Before using this application, you must setup :
* PHP (tested with PHP 5.2) (for windows php executable scripts must be setup)
* VLC (http://www.videolan.org/vlc)
* Livestreamer (http://docs.livestreamer.io/install.html)

It can be used on :
* Windows
* Linux

# Start Application
Just run :
```
php simple-tv.php
```

# Configuration
On top of simple-tv.php, you can find a configuration key to set "$save_path".
Set here the path in which you want SimpleTV could store recorded files.

# Features
* Application display the list of available stations; the source XML file for stations can change often; so it's updated each time application starts
* You can choose your number
* Press "l" to watch station
* Press "e" to record station
* For recording, you can choose a live record or a scheduled one
