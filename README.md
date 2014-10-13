moodle-local_sandbox
====================
Moodle plugin which programatically restores courses to predefined course states. It can be used to provide playground moodle courses which will be cleaned periodically.


Requirements
------------
This plugin requires Moodle 2.7+


Changes
-------
* 2014-10-13 - Support new events API
* 2014-10-13 - Bugfix: Sandbox sometimes couldn't finish the scheduled task due to a coding error
* 2014-09-16 - Bugfix: Sandbox didn't restore courses when option to set the course start date to today was set to on
* 2014-09-16 - Fix typo in english language pack
* 2014-09-16 - Update README file
* 2014-08-29 - Update README file
* 2014-08-25 - Support new task API, remove legacy cron functions - Existing execution time settings have _not_ been migrated to the scheduled tasks system. The plugin's scheduled task is disabled after the upgrade and the execution time is set to the plugin's default value, please check Moodle's scheduled task settings to configure and reenable the plugin according to your needs
* 2014-06-30 - Add plugin's name as prefix to function names
* 2014-06-30 - Support new logging API, remove legacy logging
* 2014-06-30 - Check compatibility for Moodle 2.7, no functionality change
* 2014-01-31 - Check compatibility for Moodle 2.6, no functionality change
* 2013-07-30 - Transfer Github repository from github.com/abias/... to github.com/moodleuulm/...; Please update your Git paths if necessary
* 2013-07-30 - Check compatibility for Moodle 2.5, fixed a timezone flaw which caused sandbox to run at the wrong hour when system timezone was not GMT
* 2013-04-23 - Check if we need to specify log events
* 2013-03-18 - Small code optimization, Code cleanup according to moodle codechecker
* 2013-02-19 - German language has been integrated into AMOS and was removed from this plugin. Please update your language packs with http://YOURMOODLEURL/admin/tool/langimport/index.php after installing this plugin version
* 2013-02-18 - Check compatibility for Moodle 2.4, fix language string names to comply with language string name convention
* 2013-01-21 - Bugfix: Fix flaw in german language pack
* 2013-01-08 - Initial version


Installation
------------
Install the plugin like any other plugin to folder
/local/sandbox

See http://docs.moodle.org/en/Installing_plugins for details on installing Moodle plugins


Usage & Settings
----------------
After installing local_sandbox, the plugin doesn't do anything until it is configured.
To configure the plugin, please visit Plugins -> Local plugins -> Sandbox.

There, you find three sections:

### 1. Execution time

From Moodle 2.7 on, Moodle core supports a system called "Scheduled tasks". The execution time settings of the sandbox plugin, which have been configured in this section until Moodle 2.6, can be now configured in the "Scheduled tasks" system.

By default, sandbox's scheduled task is disabled in the "Scheduled tasks" system. You have to enable it there to make use of this plugin.

By default, sandbox's scheduled task is set to run every sunday on 1:00 GMT in the "Scheduled tasks" system. Please change this time according to your needs.

### 2. Course backups

In this section, you define the directory where the course backup files to use for course restoring are. local_sandbox takes every file in this directory with a .mbz filename extension, takes the file's name, searches for a existing course with a shortname equal to the file's name and finally, uses the course backup file to restore / reset this course.

Example:
The sandbox directory /var/www/files/moodledata/sandbox contains the files foo.bar and mylittlecourse.mbz. local_sandbox looks at the directory and finds two files. File foo.bar is ignored by local_sandbox because it doesn't have the right filename extension. File mylittlecourse.mbz will be considered for restoring a sandbox course. local_sandbox now looks for a existing course with shortname "mylittlecourse". If this course exists, it resets / restores it to the state saved in the mylittlecourse.mbz backup file. If this course doesn't exist, local_sandbox doesn't change anything.

Additionally, in this section, there is an option to set the course start date to today instead of setting it to the date saved in the course backup file. Use this option if you need to provide playground courses in Moodle which pretend to be up-to-date.

### 3. Notifications

As local_sandbox acts automatically, it can inform you when failures or problems occur. In this section, you can define who should be notified and which failures or problems should be reported.


Themes
------
The local_sandbox plugin acts behind the scenes, therefore it works with all moodle themes.


Secure path configuration in Moodle 2.5+
----------------------------------------
Since Moodle 2.5, it is possible to disable setting executable paths in config.php with $CFG->preventexecpath = true (please have a look at your config-dist.php for documentation about this feature).

If (and only if) you use this option, it is not possible to configure the paths to the course backup directory on the Moodle settings pages.
For configuring the paths to the course backup directory, please set $CFG->preventexecpath = false in your config.php temporarily.

(Related Moodle tracker tickets: https://tracker.moodle.org/browse/MDL-41593 and https://tracker.moodle.org/browse/MDL-40949)


Emails sent by cronjob
----------------------
As soon as local_sandbox is configured and working, there is an email sent to the webserver administrator (the person who gets stdout output from unix cronjobs) telling something like this:
    instantiating restore controller 0b3123770cffd351d7c7b890a7a0035c
    setting controller status to 100
    loading backup info
    loading controller plan
    setting controller status to 300
    checking plan security
    setting controller status to 600
    saving controller to db
    calculating controller checksum 2d4e16b80c8c8098fab8dd1f397ae0da
    loading controller from db
    setting controller status to 700
    saving controller to db
    calculating controller checksum 061df7eb8a9370826e16b39f86747fc3
    loading controller from db
    setting controller status to 800
    processing file aliases queue
    setting controller status to 1000
    saving controller to db
This output is generated by the restore controller used by local_sandbox and can't be suppressed according to our knowledge. You can simply delete these mails.


Further information
-------------------
local_sandbox is found in the Moodle Plugins repository: http://moodle.org/plugins/view.php?plugin=local_sandbox

Report a bug or suggest an improvement: https://github.com/moodleuulm/moodle-local_sandbox/issues


Moodle release support
----------------------
Due to limited ressources, local_sandbox is only maintained for the most recent major release of Moodle. However, previous versions of this plugin which work in legacy major releases of Moodle are still available as-is without any further updates in the Moodle Plugins repository.

There may be several weeks after a new major release of Moodle has been published until we can do a compatibility check and fix problems if necessary. If you encounter problems with a new major release of Moodle - or can confirm that local_sandbox still works with a new major relase - please let us know on https://github.com/moodleuulm/moodle-local_sandbox/issues


Right-to-left support
---------------------
This plugin has not been tested with Moodle's support for right-to-left (RTL) languages.
If you want to use this plugin with a RTL language and it doesn't work as-is, you are free to send me a pull request on github with modifications.


Copyright
---------
University of Ulm
kiz - Media Department
Team Web & Teaching Support
Alexander Bias

