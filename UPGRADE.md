Upgrading this plugin
=====================

This is an internal documentation for plugin developers with some notes what has to be considered when updating this plugin to a new Moodle major version.

General
-------

* Generally, this is a quite simple plugin with just one purpose.
* It does not rely on any fluctuating library functions and should remain quite stable between Moodle major versions.
* Thus, the upgrading effort is low.


Upstream changes
----------------

* This plugin inherits from the core restore_controller class in classes/restore_controller.php and overrides apply_defaults() with a modified copy of the core method (see the "MaH MODIFICATION" block). When upgrading, compare it with restore_controller::apply_defaults() in backup/controller/restore_controller.class.php and adopt upstream changes.
* The scheduled task relies on the restore controller's precheck results (the 'errors' and 'warnings' keys of get_precheck_results()) and on backup::STATUS_REQUIRE_CONV. When upgrading, check that these are still provided by core.


Automated tests
---------------

* The plugin has a good coverage with Behat tests which test most of the plugin's user stories.
* The Behat scenarios for the 'Email failures to' and 'Email Threshold' settings need an email catcher (TEST_EMAILCATCHER_MAIL_SERVER and TEST_EMAILCATCHER_API_SERVER in config.php), otherwise they are skipped.
* The plugin has PHPUnit tests which cover the scheduled task including its error handling. To trigger the restore precheck errors and warnings and the unsupported backup format, these tests rely on core restore behaviour. If these tests fail after upgrading, check if core has changed this behaviour before you suspect a bug in the plugin.


Manual tests
------------

* There aren't any manual tests needed to upgrade this plugin.


Visual checks
-------------

* There aren't any additional visual checks in the Moodle GUI needed to upgrade this plugin.
