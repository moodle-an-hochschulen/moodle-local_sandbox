moodle-local_sandbox
====================

Changes
-------

### Unreleased

* 2017-12-05 - Added Workaround to travis.yml for fixing Behat tests with TravisCI.
* 2017-11-08 - Updated travis.yml to use newer node version for fixing TravisCI error.

### v3.2-r3

* 2017-05-29 - Add Travis CI support

### v3.2-r2

* 2017-05-05 - Improve README.md

### v3.2-r1

* 2017-01-17 - Check compatibility for Moodle 3.2, no functionality change
* 2017-01-12 - Move Changelog from README.md to CHANGES.md

### v3.1-r2

* 2016-07-21 - Move the plugin's settings page to Site Administration -> Courses because this is where it logically belongs to

### v3.1-r1

* 2016-07-19 - Check compatibility for Moodle 3.1, no functionality change

### Changes before v3.1

* 2016-02-10 - Change plugin version and release scheme to the scheme promoted by moodle.org, no functionality change
* 2016-01-01 - Remove reference to pre-Moodle 2.7 execution time configuration in language pack and README
* 2016-01-01 - Remove the "Secure path configuration in Moodle 2.5+" section from README file since the underlying problem is solved in Moodle core
* 2016-01-01 - Check compatibility for Moodle 3.0, no functionality change
* 2015-08-21 - Add missing event description to language pack
* 2015-08-21 - Add "Method of operation" section to README file
* 2015-08-18 - Check compatibility for Moodle 2.9, no functionality change
* 2015-01-29 - Check compatibility for Moodle 2.8, no functionality change
* 2014-11-24 - Support new backup format ($CFG->enabletgzbackups), Credits to Dimitri Vorona
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
