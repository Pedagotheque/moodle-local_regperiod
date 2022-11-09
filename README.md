[![Moodle Plugin CI](https://github.com/Pedagotheque/moodle-local_regperiod/actions/workflows/main.yml/badge.svg)](https://github.com/Pedagotheque/moodle-local_regperiod/actions/workflows/main.yml)

# Description


This plugin allows you to set start and end dates for user activation, via their user profile field. When we specify a start date or end date on a given user profile, the user is suspended when the date is out of range.

You can customise the two fields with all the properties you like.
>*Except for the short names. Indeed, **the short names are required for the plugin to work***.

If you need to upload users through a CSV file, the start and end dates should be in the UNIX timestamp format you can use [this converter](https://www.unixtimestamp.com/).

# Functioning

During the installation, the plugin will create a new profile field category called "Registration duration" to the site with the fields "Start" and "End".

The category will be at the bottom of all existing categories.
The new category can be renamed or removed, and so are the fields
>*If you remove both of the fields, the plugin becomes useless, but you can remove one of them if needed.*

A scheduled task is set to be run every 10 minutes in order to suspend or activate relevant users. This task can be edited in the site administration.
