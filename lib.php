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
 * Local plugin "sandbox" - Library
 *
 * @package     local
 * @subpackage  local_sandbox
 * @copyright   2013 Alexander Bias, University of Ulm <alexander.bias@uni-ulm.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

define('SANDBOX_LEVEL_NOTICE', 1);
define('SANDBOX_LEVEL_WARNING', 2);
define('SANDBOX_LEVEL_ERROR', 3);

require_once($CFG->dirroot.'/config.php');
require_once($CFG->libdir.'/moodlelib.php');
require_once($CFG->libdir.'/filestorage/zip_packer.php');
require_once($CFG->dirroot.'/backup/util/includes/restore_includes.php');


/**
 * Cron function
 *
 * @return bool
 */
function local_sandbox_cron() {
    global $CFG, $DB;

    // Get plugin config
    $config = get_config('local_sandbox');


    // Get last cron runtime, inform admin when data is not available
    if (!$lastcron = $DB->get_field('config_plugins', 'value', array('plugin' => 'local_sandbox', 'name' => 'lastcron'))) {
        // Output error message for cron listing
        echo "\n\t".get_string('errorlastcronerror', 'local_sandbox')."\n";

        // Inform admin
        inform_admin(get_string('errorlastcronerror', 'local_sandbox'), SANDBOX_LEVEL_ERROR);

        return false;
    }


    // Continue only when sandbox runtime days are configured
    if (strpos($config->cronrunday, "1") === false) {
        // Output info message for cron listing
        echo "\n\t".get_string('noticedaysnotconfigured', 'local_sandbox')."\n";

        // Inform admin
        inform_admin(get_string('noticedaysnotconfigured', 'local_sandbox'), SANDBOX_LEVEL_NOTICE);

        return true;
    }


    // Gets the admin user time relatively to the server time.
    $admin = get_admin();
    $now = time();
    $date = usergetdate($now, $admin->timezone);
    $usertime = mktime($date['hours'], $date['minutes'], $date['seconds'], $date['mon'], $date['mday'], $date['year']);


    // Continue only when today is run day
    $sandboxtoday = substr($config->cronrunday, $date['wday'], 1);
    if ($sandboxtoday == 0) {
        // Output info message for cron listing
        echo "\n\t".get_string('exitingnoneed', 'local_sandbox')."\n";

        return true;
    }


    // Continue only when run time is reached and sandbox hasn't run already today
    $todayruntime = mktime($config->cronruntimehour, $config->cronruntimemin, 0, $date['mon'], $date['mday'], $date['year']);
    if ($now < $todayruntime || $lastcron > $todayruntime) {
        // Output info message for cron listing
        echo "\n\t".get_string('exitingnoneed', 'local_sandbox')."\n";

        return true;
    }


    // Counter for restored courses
    $count = 0;


    // Do only when sandbox directory is configured
    if ($config->coursebackupsdirectory != '') {

        // Do only when sandbox directory exists
        if (is_dir($config->coursebackupsdirectory)) {

            // Open directory and get all .mbz files
            if ($handle = @opendir($config->coursebackupsdirectory)) {
                while (false !== ($file = readdir($handle))) {
                    if (substr($file, -4) == '.mbz' && $file != '.' && $file != '..') {

                        // Get course shortname from filename
                        $shortname = substr($file, 0, -4);
                        echo "\n\t".get_string('nowprocessing', 'local_sandbox', $shortname)."\n";

                        // Get existing course information
                        if ($oldcourse = $DB->get_record('course', array('shortname' => $shortname))) {
                            $oldcourseid = $oldcourse->id;
                            $categoryid = $oldcourse->category;
                            $fullname = $oldcourse->fullname;
                        }
                        else {
                            // Output error message for cron listing
                            echo "\n\t".get_string('skippingnocourse', 'local_sandbox', $shortname)."\n";

                            // Inform admin
                            inform_admin(get_string('skippingnocourse', 'local_sandbox', $shortname), SANDBOX_LEVEL_WARNING);

                            continue;
                        }

                        // Delete existing course
                        if (!delete_course($oldcourseid, false)) {
                            // Output error message for cron listing
                            echo "\n\t".get_string('skippingdeletionfailed', 'local_sandbox', $shortname)."\n";

                            // Inform admin
                            inform_admin(get_string('skippingdeletionfailed', 'local_sandbox', $shortname), SANDBOX_LEVEL_WARNING);

                            continue;
                        }

                        // Unzip course backup file to temp directory
                        $zippacker = new zip_packer();
                        check_dir_exists($CFG->dataroot.'/temp/backup');
                        if (!$zippacker->extract_to_pathname($config->coursebackupsdirectory.'/'.$file, $CFG->dataroot.'/temp/backup/'.$shortname)) {
                            // Output error message for cron listing
                            echo "\n\t".get_string('skippingunzipfailed', 'local_sandbox', $file)."\n";

                            // Inform admin
                            inform_admin(get_string('skippingunzipfailed', 'local_sandbox', $shortname), SANDBOX_LEVEL_WARNING);

                            continue;
                        }

                        // Create new course
                        if (!$newcourseid = restore_dbops::create_new_course($shortname, $shortname, $categoryid)) {
                            // Output error message for cron listing
                            echo "\n\t".get_string('skippingcreatefailed', 'local_sandbox', $shortname)."\n";

                            // Inform admin
                            inform_admin(get_string('skippingcreatefailed', 'local_sandbox', $shortname), SANDBOX_LEVEL_WARNING);

                            continue;
                        }

                        // Get admin user for restore
                        $admin = get_admin();
                        $restoreuser = $admin->id;

                        // Restore course backup file into new course
                        if ($controller = new restore_controller($shortname, $newcourseid, backup::INTERACTIVE_NO, backup::MODE_SAMESITE, $restoreuser, backup::TARGET_NEW_COURSE)) {
                            $controller->get_logger()->set_next(new output_indented_logger(backup::LOG_INFO, false, true));
                            $controller->execute_precheck();
                            $controller->execute_plan();
                        }
                        else {
                            // Output error message for cron listing
                            echo "\n\t".get_string('skippingrestorefailed', 'local_sandbox', $shortname)."\n";

                            // Inform admin
                            inform_admin(get_string('skippingrestorefailed', 'local_sandbox', $shortname), SANDBOX_LEVEL_WARNING);

                            continue;
                        }

                        // Adjust course start date
                        if ($config->adjustcoursestartdate == true) {
                            if (!$DB->update_record('course', (object)array('id' => $newcourseid, 'startdate' => $now))) {
                                // Output error message for cron listing
                                echo "\n\t".get_string('skippingadjuststartdatefailed', 'local_sandbox', $shortname)."\n";

                                // Inform admin
                                inform_admin(get_string('skippingadjuststartdatefailed', 'local_sandbox', $shortname), SANDBOX_LEVEL_WARNING);

                                continue;
                            }
                        }

                        // Set shortname and fullname back
                        if ($DB->update_record('course', (object)array('id' => $newcourseid, 'shortname' => $shortname, 'fullname' => $fullname))) {
                            // Output info message for cron listing
                            echo "\n\t".get_string('successrestored', 'local_sandbox', $shortname)."\n";

                            // Inform admin
                            inform_admin(get_string('successrestored', 'local_sandbox', $shortname), SANDBOX_LEVEL_NOTICE);

                            // Add entry to Moodle log
                            add_to_log($newcourseid, 'local_sandbox', 'course restore'); // TODO: Specify log events in db/log.php, see http://docs.moodle.org/dev/Logging_API#Mod.2F.2A.2Fdb.2Flog.php_Files


                            // Fire event
                            $course = $DB->get_record('course', array('id'=>$newcourseid));
                            events_trigger('course_updated', $course);

                            // Count successfully restored course
                            $count++;
                        }
                        else {
                            // Output error message for cron listing
                            echo "\n\t".get_string('skippingdbupdatedfailed', 'local_sandbox', $shortname)."\n";

                            // Inform admin
                            inform_admin(get_string('skippingdbupdatefailed', 'local_sandbox', $shortname), SANDBOX_LEVEL_WARNING);

                            continue;
                        }
                    }
                }
                closedir($handle);

                // Output info message for cron listing
                echo "\n\t".get_string('noticerestorecount', 'local_sandbox', $count)."\n";

                // Inform admin
                inform_admin(get_string('noticerestorecount', 'local_sandbox', $count), SANDBOX_LEVEL_NOTICE);

                return true;
            }
            else {
                // Output error message for cron listing
                echo "\n\t".get_string('errordirectorynotreadable', 'local_sandbox', $config->coursebackupsdirectory)."\n";

                // Inform admin
                inform_admin(get_string('errordirectorynotreadable', 'local_sandbox', $config->coursebackupsdirectory), SANDBOX_LEVEL_ERROR);

                return false;
            }
        }
        else {
            // Output error message for cron listing
            echo "\n\t".get_string('errordirectorynotexist', 'local_sandbox', $config->coursebackupsdirectory)."\n";

            // Inform admin
            inform_admin(get_string('errordirectorynotexist', 'local_sandbox', $config->coursebackupsdirectory), SANDBOX_LEVEL_ERROR);

            return false;
        }
    }
    else {
        // Output info message for cron listing
        echo "\n\t".get_string('noticedirectorynotconfigured', 'local_sandbox')."\n";

        // Inform admin
        inform_admin(get_string('noticedirectorynotconfigured', 'local_sandbox'), SANDBOX_LEVEL_NOTICE);

        return true;
    }
}



/**
 * Helper function for sending notification mails
 *
 * @param string $message The message
 * @param int $level Notification level
 * @return
 */
function inform_admin($message, $level = SANDBOX_LEVEL_NOTICE) {
    // Get recipients
    $recipients = get_users_from_config(get_config('local_sandbox', 'notifyonerrors'), 'moodle/site:config');

    // If there are no recipients, don't execute.
    if (!is_array($recipients) || count($recipients) <= 0) {
        return false;
    }


    // If message level is below configured notice level, don't execute
    if ($level < get_config('local_sandbox', 'notifylevel')) {
        return false;
    }


    // Get subject
    if ($level > SANDBOX_LEVEL_WARNING) {
        $subject = get_string('emailsubjecterror', 'local_sandbox');
    }
    else if ($level > SANDBOX_LEVEL_NOTICE) {
        $subject = get_string('emailsubjectwarning', 'local_sandbox');
    }
    else {
        $subject = get_string('emailsubjectnotice', 'local_sandbox');
    }


    // Send mail
    foreach ($recipients as $r) {
        // Email the admin directly rather than putting these through the messaging system
        email_to_user($r, generate_email_supportuser(), $subject, $message);
    }
}
