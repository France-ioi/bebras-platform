The Certificate Generation works the following way : 
- generation requests are inserted into the database
- requests are processed by a continuously running script

This script, "bgScript.sh",  needs to be started only once, through command line.
It will put itself in the background (you can close your session).
It will require a cron task to be set-up : this is mostly useful if the system is restarting, and will restart the script every 5 minutes if it's not running.

Note : the script will only start one instance of itself.