# Certificate generation scripts

## Installation

You must have both `export` and `log` in 777 mode.

You must have:

- bash
- php
- xvfb
- wkhtmltopdf

## Usage

The Certificate generation works the following way:

- generation requests are inserted into the database
- requests are processed by a continuously running script

This script, `bgScript.sh`, needs to be started only once, through command line (it will detect other instances and kill itself if others are running).
It will put itself in the background (you can close your session).
It will require a cron task to be set-up : this is mostly useful if the system is restarting, and will restart the script every 5 minutes if it's not running.

Logs are present in `/tmp/bgScript.PID.std(err|out)` where `PID` is the PID of the `bgScript.sh` process.

## Crontab example

     */5 * * * * /bin/bash /path/to/certificates/bgScript.sh >/dev/null

## TODO

- change weird permissions
- change stderr logs
- change 