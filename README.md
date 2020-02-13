moodle-local_sandbox
====================

[![Build Status](https://travis-ci.org/moodleuulm/moodle-local_sandbox.svg?branch=master)](https://travis-ci.org/moodleuulm/moodle-local_sandbox)

Moodle plugin which programatically restores courses to predefined course states. It can be used to provide playground moodle courses which will be cleaned periodically


Requirements
------------

This plugin requires Moodle 3.8+


Motivation for this plugin
--------------------------

Providing sandbox courses to your users makes sense for simplifying live training courses or for letting new Moodle users explore the features of Moodle. However, manually resetting sandbox courses after a live training session or after a certain amount of time is a daunting task.

If you want to get rid of this senseless job of resetting courses periodically manually, this plugin is for you.


Installation
------------

Install the plugin like any other plugin to folder
/local/sandbox

See http://docs.moodle.org/en/Installing_plugins for details on installing Moodle plugins


Usage & Settings
----------------

After installing the plugin, it does not do anything to Moodle yet.

To configure the plugin and its behaviour, please visit:
Site administration -> Courses -> Sandbox.

There, you find four sections:

### 1. Execution time

Moodle core supports a system called "Scheduled tasks". The execution time settings of the sandbox plugin is configured in the "Scheduled tasks" system.

By default, sandbox's scheduled task is disabled in the "Scheduled tasks" system. You have to enable it there to make use of this plugin.

By default, sandbox's scheduled task is set to run every sunday on 1:00 GMT in the "Scheduled tasks" system. Please change this time according to your needs.

### 2. Course backups

In this section, you upload the files with the course backup files to use for course restoring. The filepicker accepts files with .mbz filename extensions. For each course you want to reset, upload a Moodle course backup file, named as [shortname].mbz. local_sandbox takes the file's name, searches for a existing course with a shortname equal to the file's name and finally, uses the course backup file to restore / reset this course.

Example:
The filearea contains the file mylittlecourse.mbz. local_sandbox looks at the filearea and finds this file mylittlecourse.mbz and will consider it for restoring a sandbox course. local_sandbox now looks for a existing course with shortname "mylittlecourse". If this course exists, it resets / restores it to the state saved in the mylittlecourse.mbz backup file. If this course doesn't exist, local_sandbox doesn't change anything.

Additionally, in this section, there is an option to set the course start date to today instead of setting it to the date saved in the course backup file. Use this option if you need to provide playground courses in Moodle which pretend to be up-to-date.

Additionally, in this section, there is an option to let local_sandbox keep the course ID when a course is restored. See the "How this plugin works" section below for details.

### 3. Restore settings

Similar to the global restore settings on /admin/settings.php?section=restoregeneralsettings and to the restore settings you will see when restoring a course manually, you can configure the details for restoring the sandbox courses here.

Please note, that local_sandbox does not validate if the uploaded course backup comply with the configured settings. Please make sure that you only check the settings which you really want to restore with local_sandbox. Test your settings properly before running local_sandbox unattendedly. Make sure that you only enable the restore settings which all of your course backup files comply to, otherwise you risk local_sandbox to fail quite graciously when it tries to restore the course backup files. 

### 4. Notifications

As local_sandbox acts automatically, it can inform you when failures or problems occur. In this section, you can define who should be notified and which failures or problems should be reported.


Emails sent by cronjob
----------------------

If you have debugging enabled in your Moodle installation, as soon as local_sandbox is configured and working, there might be an email sent to the webserver administrator (the person who gets stdout output from unix cronjobs) telling something like this:

> instantiating restore controller 0b3123770cffd351d7c7b890a7a0035c

> setting controller status to 100

> loading backup info

> loading controller plan

> setting controller status to 300

> checking plan security

> setting controller status to 600

> saving controller to db

> calculating controller checksum 2d4e16b80c8c8098fab8dd1f397ae0da

> loading controller from db

> setting controller status to 700

> saving controller to db

> calculating controller checksum 061df7eb8a9370826e16b39f86747fc3

> loading controller from db

> setting controller status to 800

> processing file aliases queue

> setting controller status to 1000

> saving controller to db

This output is generated by the restore controller used by local_sandbox and can't be suppressed according to our knowledge. You can simply delete these mails.


Upgrading from previous versions
--------------------------------
On 2018-02-09, we changed the way the local_sandbox plugin works fundamentally. Until then, there was a course backups directory within the Moodledata directory on disk which kept the course backup files. Now, as you know, these files are placed in a filearea within Moodle.

For admins upgrading from a version before this change to a recent version of the plugin, it is important to know:

Within the plugin upgrade process, the course backup files are copied automatically to the new filearea within Moodle. After the plugin has been upgraded, you can delete the legacy course backups directory manually.


How this plugin works
---------------------

The plugin's description states that it restores courses to predefined course states. In reality, this is not exactly true. In fact, the plugin operates by completely deleting a course and creating a new one from the configured backup file.

Normally, this tiny detail is unimportant. However, you should know that each resetted course gets a new course ID. This can produce problems if you have a hardcoded link (from outside or inside of Moodle) pointing to a sandbox course. This link will break with each run of the sandbox plugin. If you want to have a hardcoded link to a sandbox course, you can construct the link's URL like `https://<YOURMOODLE>/course/view.php?name=<COURSE-SHORTNAME>` instead of `https://<YOURMOODLE>/course/view.php?id=<COURSE-ID>`.

If changing IDs are still a real problem for you, with the "Keep course ID" setting, sandbox can be switched to keeping the existing course and to only delete the existing course content before restoring the course content from the course backup files. The restored course will then have the same course ID. However, use this setting at your own risk. Deleting a sandbox course and creating a new one is still the cleanest approach in our point of view.


Theme support
-------------

This plugin is developed and tested on Moodle Core's Boost theme.
It should also work with Boost child themes, including Moodle Core's Classic theme. However, we can't support any other theme than Boost.


Plugin repositories
-------------------

This plugin is published and regularly updated in the Moodle plugins repository:
http://moodle.org/plugins/view/local_sandbox

The latest development version can be found on Github:
https://github.com/moodleuulm/moodle-local_sandbox


Bug and problem reports / Support requests
------------------------------------------

This plugin is carefully developed and thoroughly tested, but bugs and problems can always appear.

Please report bugs and problems on Github:
https://github.com/moodleuulm/moodle-local_sandbox/issues

We will do our best to solve your problems, but please note that due to limited resources we can't always provide per-case support.


Feature proposals
-----------------

Due to limited resources, the functionality of this plugin is primarily implemented for our own local needs and published as-is to the community. We are aware that members of the community will have other needs and would love to see them solved by this plugin.

Please issue feature proposals on Github:
https://github.com/moodleuulm/moodle-local_sandbox/issues

Please create pull requests on Github:
https://github.com/moodleuulm/moodle-local_sandbox/pulls

We are always interested to read about your feature proposals or even get a pull request from you, but please accept that we can handle your issues only as feature _proposals_ and not as feature _requests_.


Moodle release support
----------------------

Due to limited resources, this plugin is only maintained for the most recent major release of Moodle. However, previous versions of this plugin which work in legacy major releases of Moodle are still available as-is without any further updates in the Moodle Plugins repository.

There may be several weeks after a new major release of Moodle has been published until we can do a compatibility check and fix problems if necessary. If you encounter problems with a new major release of Moodle - or can confirm that this plugin still works with a new major relase - please let us know on Github.

If you are running a legacy version of Moodle, but want or need to run the latest version of this plugin, you can get the latest version of the plugin, remove the line starting with $plugin->requires from version.php and use this latest plugin version then on your legacy Moodle. However, please note that you will run this setup completely at your own risk. We can't support this approach in any way and there is a undeniable risk for erratic behavior.


Translating this plugin
-----------------------

This Moodle plugin is shipped with an english language pack only. All translations into other languages must be managed through AMOS (https://lang.moodle.org) by what they will become part of Moodle's official language pack.

As the plugin creator, we manage the translation into german for our own local needs on AMOS. Please contribute your translation into all other languages in AMOS where they will be reviewed by the official language pack maintainers for Moodle.


Right-to-left support
---------------------

This plugin has not been tested with Moodle's support for right-to-left (RTL) languages.
If you want to use this plugin with a RTL language and it doesn't work as-is, you are free to send us a pull request on Github with modifications.


PHP7 Support
------------

Since Moodle 3.4 core, PHP7 is mandatory. We are developing and testing this plugin for PHP7 only.


Copyright
---------

Ulm University
Communication and Information Centre (kiz)
Alexander Bias
