<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	 See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/lib.php');

if ($hassiteconfig) {
	// New settings page
	$page = new admin_settingpage('sandbox', get_string('pluginname', 'local_sandbox'));


	// Execution time
	$page->add(new admin_setting_heading('local_sandbox/cronruntimeheading', get_string('cronruntimeheading', 'local_sandbox'), ''));

	// Create days chooser widget
	$days = array('sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday');
	foreach ($days as $d) {
		$dayschoices[] = get_string($d, 'calendar');
	}
	$page->add(new admin_setting_configmulticheckbox2('local_sandbox/cronrunday', get_string('cronrunday', 'local_sandbox'), get_string('cronrunday_desc', 'local_sandbox'), array(), $dayschoices));

	// Create cron run time widget
	$page->add(new admin_setting_configtime('local_sandbox/cronruntimehour', 'cronruntimemin', get_string('cronruntime', 'local_sandbox'), get_string('cronruntime_desc', 'local_sandbox'), array('h' => 3, 'm' => 0)));


	// Course backups
	$page->add(new admin_setting_heading('local_sandbox/coursebackupsheading', get_string('coursebackupsheading', 'local_sandbox'), ''));

	// Create course backup files directory widget
	$page->add(new admin_setting_configdirectory('local_sandbox/coursebackupsdirectory', get_string('coursebackupsdirectory', 'local_sandbox'), get_string('coursebackupsdirectory_desc', 'local_sandbox'), $CFG->dataroot.'/sandbox'));

	// Create change course start date control widget
	$page->add(new admin_setting_configcheckbox('local_sandbox/adjustcoursestartdate', get_string('adjustcoursestartdate', 'local_sandbox'), get_string('adjustcoursestartdate_desc', 'local_sandbox'), 0));


	// Notifications
	$page->add(new admin_setting_heading('local_sandbox/notifyheading', get_string('notifyheading', 'local_sandbox'), ''));

	// Create user notification chooser widget
	$page->add(new admin_setting_users_with_capability('local_sandbox/notifyonerrors', get_string('notifyonerrors', 'local_sandbox'), get_string('notifyonerrors_desc', 'local_sandbox'), array(), 'moodle/site:config'));

	// Create user notification level widget
	$levels[LEVEL_NOTICE] = get_string('notice', 'core');
	$levels[LEVEL_WARNING] = get_string('warning', 'core');
	$levels[LEVEL_ERROR] = get_string('error', 'core');
	$page->add(new admin_setting_configselect('local_sandbox/notifylevel', get_string('notifylevel', 'local_sandbox'), get_string('notifylevel_desc', 'local_sandbox'), LEVEL_ERROR, $levels));


	// Add settings page to navigation tree
    $ADMIN->add('localplugins', $page);
}