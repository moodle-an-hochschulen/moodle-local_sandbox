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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Local plugin "sandbox" - Language pack
 *
 * @package    local_sandbox
 * @copyright  2014 Alexander Bias, Ulm University <alexander.bias@uni-ulm.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['pluginname'] = 'Sandbox';
$string['adjustcoursestartdate'] = 'Adjust course start date';
$string['adjustcoursestartdate_desc'] = 'Set course start date to today instead of setting it to the date saved in the course backup file';
$string['coursebackupsdirectory'] = 'Path to course backups';
$string['coursebackupsdirectory_desc'] = 'Specify the path to the directory where the course backup files to use for course restoring are. Course backup files in this directory must be named with the appropriate course short name and must have the .mbz filename extension.';
$string['coursebackupsheading'] = 'Course backups';
$string['cronrunday'] = 'Execution days';
$string['cronrunday_desc'] = 'Restore sandbox courses on these days';
$string['cronruntime'] = 'Execution time';
$string['cronruntime_desc'] = 'Restore sandbox courses on this time of day';
$string['cronruntimeheading'] = 'Execution time';
$string['cronruntimescheduledtasksmanagement'] = 'Moodle core supports a system called "Scheduled tasks". The execution time settings of the sandbox plugin is configured in the "Scheduled tasks" system.';
$string['cronruntimescheduledtasksactivate'] = 'By default, sandbox\'s scheduled task is disabled in the "Scheduled tasks" system. You have to enable it there to make use of this plugin.';
$string['cronruntimescheduledtasksstandardtime'] = 'By default, sandbox\'s scheduled task is set to run every sunday on 1:00 GMT in the "Scheduled tasks" system. Please change this time according to your needs.';
$string['emailsubjecterror'] = 'ERROR: Sandbox';
$string['emailsubjectnotice'] = 'NOTICE: Sandbox';
$string['emailsubjectwarning'] = 'WARNING: Sandbox';
$string['errordirectorynotexist'] = 'ERROR: Sandbox directory "{$a}" doesn\'t exist or couldn\'t be accessed, exiting...';
$string['errordirectorynotreadable'] = 'ERROR: Sandbox directory "{$a}" couldn\'t be opened for reading, exiting...';
$string['errorlastcronerror'] = 'ERROR: Could not get last sandbox execution timestamp from database, exiting...';
$string['eventcourserestored'] = 'Course restored';
$string['eventcourserestored_desc'] = 'Course \'{$a}\' was restored to its predefined course state';
$string['exitingnoneed'] = 'No need for restoring sandbox courses so far, let\'s wait for next cron run time, exiting...';
$string['noticedaysnotconfigured'] = 'NOTICE: Sandbox execution days are not configured, so there\'s nothing to do, exiting...';
$string['noticedirectorynotconfigured'] = 'NOTICE: Sandbox directory is not configured, so there\'s nothing to do, exiting...';
$string['noticerestorecount'] = 'NOTICE: Sandbox has restored {$a} courses';
$string['notifyheading'] = 'Notifications';
$string['notifylevel'] = 'Email Threshold';
$string['notifylevel_desc'] = 'Email notifications will only be sent for events you wish to be notified of. What do you want to be notified of?';
$string['notifyonerrors'] = 'Email failures to';
$string['notifyonerrors_desc'] = 'If sandbox failures occur, email notifications can be sent out. Who should see these notifications?';
$string['nowprocessing'] = 'NOW: Processing course "{$a}"';
$string['skippingadjuststartdatefailed'] = 'WARNING: Course start date adjustment of course "{$a}" failed, skipping file...';
$string['skippingcreatefailed'] = 'WARNING: Course creation of course "{$a}" failed, skipping file...';
$string['skippingdbupdatefailed'] = 'WARNING: Course database update of course "{$a}" failed, skipping file...';
$string['skippingdeletionfailed'] = 'WARNING: Deletion of existing course "{$a}" failed (partially or completely), skipping file...';
$string['skippingnocourse'] = 'WARNING: There is no existing course with shortname "{$a}", skipping file...';
$string['skippingrestorefailed'] = 'WARNING: Course restore of course "{$a}" failed, skipping file...';
$string['skippingunzipfailed'] = 'WARNING: Unzipping of backup file "{$a}" failed, skipping file...';
$string['successrestored'] = 'SUCCESS: Restored course "{$a}"';
$string['taskrestorecourses'] = 'Restore sandbox courses';
$string['upgrade_notice_2014051200'] = '<strong>UPGRADE NOTE:</strong> This update of the sandbox plugin adds support for Moodle\' scheduled task system. The plugin\'s execution time settings will <strong>not</strong> be migrated to the scheduled tasks system. The plugin\'s scheduled task is disabled after this upgrade and the execution time is set to the plugin\'s default value, please check Moodle\'s scheduled task settings to configure and reenable the plugin according to your needs.';
