# Description
This plugin allows you to set start and end dates of registration, via the user profile fields.
During the installation, the plugin will create a new profile field category called "Registration duration to the site" with the fields "Start" and "End".
The category will be at the top of all existing categories.
The new category can be renamed or removed, and so are the fields (if you remove both of the fields, the plugin becomes useless, but you can remove one of them if needed).
You can customise the two fields, except for the short names. Indeed, the short names are required for the plugin to work.
If you need to upload users through a CSV file, the start and end dates should be in the UNIX timestamp format (you can use this converter:  https://www.unixtimestamp.com/).

# Functioning
A scheduled task is set to be run every 10 minutes in order to suspend or activate relevant users. This task can be edited in the site administration.
