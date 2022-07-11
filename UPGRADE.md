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

* This plugin does not inherit or copy anything from upstream sources.


Automated tests
---------------

* The plugin has a good coverage with Behat tests which test most of the plugin's user stories.


Manual tests
------------

* There aren't any manual tests needed to upgrade this plugin.
* However, if you look at the Behat feature file, you will see that there are some scenarios still commented out. If you have time, you should test them manually or write a Behat test for it.


Visual checks
-------------

* There aren't any additional visual checks in the Moodle GUI needed to upgrade this theme.
