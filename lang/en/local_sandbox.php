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
$string['coursebackups'] = 'Course backups';
$string['coursebackups_desc'] = 'Upload the .mbz files with the course backup files to use for course restoring here. Course backup files in this filearea must be named with the appropriate course short name and must have the .mbz filename extension. See README file for details.';
$string['keepcourseid'] = 'Keep course ID';
$string['keepcourseid_desc'] = 'By default, to be completely safe when restoring a course from the course backup files, sandbox deletes the course and creates a new one. The restored course will then have a new course ID. With this setting enabled, sandbox will switch to keeping the existing course and will only delete the existing course content before restoring the course content from the course backup files. The restored course will then have the same course ID.';
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
$string['errorlastcronerror'] = 'ERROR: Could not get last sandbox execution timestamp from database, exiting...';
$string['eventcourserestored'] = 'Course restored';
$string['eventcourserestored_desc'] = 'Course \'{$a}\' was restored to its predefined course state';
$string['exitingnoneed'] = 'No need for restoring sandbox courses so far, let\'s wait for next cron run time, exiting...';
$string['noticedaysnotconfigured'] = 'NOTICE: Sandbox execution days are not configured, so there\'s nothing to do, exiting...';
$string['noticenocoursebackups'] = 'NOTICE: Sandbox filearea does not contain any course backups, so there\'s nothing to do, exiting...';
$string['noticerestorecount'] = 'NOTICE: Sandbox has restored {$a} courses';
$string['notifyheading'] = 'Notifications';
$string['notifylevel'] = 'Email Threshold';
$string['notifylevel_desc'] = 'Email notifications will only be sent for events you wish to be notified of. What do you want to be notified of?';
$string['notifyonerrors'] = 'Email failures to';
$string['notifyonerrors_desc'] = 'If sandbox failures occur, email notifications can be sent out. Who should see these notifications?';
$string['nowprocessing'] = 'NOW: Processing course "{$a}"';
$string['privacy:metadata'] = 'The sandbox plugin provides extended functionality to Moodle admins, but does not store any personal data.';
$string['restoreheading'] = 'Restore settings';
$string['restoresettingsdescription'] = 'Similar to the global restore settings in Moodle and to the restore settings you will see when restoring a course manually, you can configure the details for restoring the sandbox courses here.';
$string['restoresettingswarning'] = 'Please note, that the sandbox does not validate if the uploaded course backup comply with the configured settings. Please make sure that you only check the settings which you really want to restore with the sandbox. Test your settings properly before running the sandbox unattendedly. Make sure that you only enable the restore settings which all of your course backup files comply to, otherwise you risk the sandbox to fail quite graciously when it tries to restore the course backup files.';
$string['skippingadjuststartdatefailed'] = 'WARNING: Course start date adjustment of course "{$a}" failed, skipping file...';
$string['skippingcreatefailed'] = 'WARNING: Course creation of course "{$a}" failed, skipping file...';
$string['skippingdbupdatefailed'] = 'WARNING: Course database update of course "{$a}" failed, skipping file...';
$string['skippingdeletionfailed'] = 'WARNING: Deletion of existing course "{$a}" failed (partially or completely), skipping file...';
$string['skippingdeletecontentfailed'] = 'WARNING: Deletion of existing course content in course "{$a}" failed (partially or completely), skipping file...';
$string['skippingnocourse'] = 'WARNING: There is no existing course with shortname "{$a}", skipping file...';
$string['skippingrestorefailed'] = 'WARNING: Course restore of course "{$a}" failed, skipping file...';
$string['skippingunzipfailed'] = 'WARNING: Unzipping of backup file "{$a}" failed, skipping file...';
$string['successrestored'] = 'SUCCESS: Restored course "{$a}"';
$string['taskrestorecourses'] = 'Restore sandbox courses';
$string['upgrade_notice_2014051200'] = '<strong>UPGRADE NOTE:</strong> This update of the sandbox plugin adds support for Moodle\' scheduled task system. The plugin\'s execution time settings will <strong>not</strong> be migrated to the scheduled tasks system. The plugin\'s scheduled task is disabled after this upgrade and the execution time is set to the plugin\'s default value, please check Moodle\'s scheduled task settings to configure and reenable the plugin according to your needs.';
$string['upgrade_notice_2018020902'] = '<strong>UPGRADE NOTICE:</strong> The course backup files were moved to the new filearea within Moodle. You can delete the legacy course backups directory {$a} now. For more upgrade instructions see README file.';
