moodle-local_sandbox
====================
Moodle plugin which programatically restores courses to predefined course states. It can be used to provide playground moodle courses which will be cleaned periodically.


Requirements
============
This plugin requires Moodle 2.4+


Changes
=======
2013-04-23 - Check if we need to specify log events
2013-03-18 - Small code optimization, Code cleanup according to moodle codechecker
2013-02-19 - German language has been integrated into AMOS and was removed from this plugin. Please update your language packs with http://YOURMOODLEURL/admin/tool/langimport/index.php after installing this plugin version
2013-02-18 - Check compatibility for Moodle 2.4, fix language string names to comply with language string name convention
2013-01-21 - Bugfix: Fix flaw in german language pack
2013-01-08 - Initial version


Installation
============
Install the plugin like any other plugin to folder
/local/sandbox

See http://docs.moodle.org/24/en/Installing_plugins for details on installing Moodle plugins


Usage & Settings
================
After installing local_sandbox, the plugin doesn't do anything until it is configured.
To configure the plugin, please visit Plugins -> Local plugins -> Sandbox.

There, you find three sections:

1. Execution time
-----------------
In this section, you define on which days and when on each day local_sandbox should restore the courses.

2. Course backups
-----------------
In this section, you define the directory where the course backup files to use for course restoring are. local_sandbox takes every file in this directory with a .mbz filename extension, takes the file's name, searches for a existing course with a shortname equal to the file's name and finally, uses the course backup file to restore / reset this course.

Example:
The sandbox directory /var/www/files/moodledata/sandbox contains the files foo.bar and mylittlecourse.mbz. local_sandbox looks at the directory and finds two files. File foo.bar is ignored by local_sandbox because it doesn't have the right filename extension. File mylittlecourse.mbz will be considered for restoring a sandbox course. local_sandbox now looks for a existing course with shortname "mylittlecourse". If this course exists, it resets / restores it to the state saved in the mylittlecourse.mbz backup file. If this course doesn't exist, local_sandbox doesn't change anything.

Additionally, in this section, there is an option to set the course start date to today instead of setting it to the date saved in the course backup file. Use this option if you need to provide playground courses in Moodle which pretend to be up-to-date.

3. Notifications
----------------
As local_sandbox acts automatically, it can inform you when failures or problems occur. In this section, you can define who should be notified and which failures or problems should be reported.


Themes
======
The local_sandbox plugin acts behind the scenes, therefore it works with all moodle themes.


Further information
===================
local_sandbox is found in the Moodle Plugins repository: http://moodle.org/plugins/view.php?plugin=local_sandbox

Report a bug or suggest an improvement: https://github.com/abias/moodle-local_sandbox/issues


Moodle release support
======================
Due to limited ressources, local_sandbox is only maintained for the most recent major release of Moodle. However, previous versions of this plugin which work in legacy major releases of Moodle are still available as-is without any further updates in the Moodle Plugins repository.

There may be several weeks after a new major release of Moodle has been published until I can do a compatibility check and fix problems if necessary. If you encounter problems with a new major release of Moodle - or can confirm that local_sandbox still works with a new major relase - please let me know on https://github.com/abias/moodle-local_sandbox/issues


Right-to-left support
=====================
This plugin has not been tested with Moodle's support for right-to-left (RTL) languages.
If you want to use this plugin with a RTL language and it doesn't work as-is, you are free to send me a pull request on github with modifications.


Copyright
=========
Alexander Bias, University of Ulm
