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
 * Local plugin "sandbox" - Task definition
 *
 * @package    local_sandbox
 * @copyright  2014 Alexander Bias, Ulm University <alexander.bias@uni-ulm.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sandbox\task;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../locallib.php');

/**
 * The local_sandbox restore courses task class.
 *
 * @package    local_sandbox
 * @copyright  2014 Alexander Bias, Ulm University <alexander.bias@uni-ulm.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_courses extends \core\task\scheduled_task {

    /**
     * Return localised task name.
     *
     * @return string
     */
    public function get_name() {
        return get_string('taskrestorecourses', 'local_sandbox');
    }


    /**
     * Execute scheduled task
     *
     * @return boolean
     */
    public function execute() {
        global $CFG, $DB;

        require_once($CFG->libdir.'/moodlelib.php');
        require_once($CFG->libdir.'/filestorage/zip_packer.php');
        require_once($CFG->dirroot.'/backup/util/includes/restore_includes.php');

        // Get plugin config.
        $localsandboxconfig = get_config('local_sandbox');

        // Counter for restored courses.
        $count = 0;

        // Fetch the list of course backup files.
        $files = local_sandbox_getfiles();

        // Do only if there are course backup files.
        if (count($files) > 0) {

            // Process each course backup file.
            foreach ($files as $shortname => $file) {
                echo "\n\t" . get_string('nowprocessing', 'local_sandbox', $shortname) . "\n";

                // Get existing course information.
                if ($oldcourse = $DB->get_record('course', array('shortname' => $shortname))) {
                    $oldcourseid = $oldcourse->id;
                    $categoryid = $oldcourse->category;
                    $fullname = $oldcourse->fullname;
                } else {
                    // Output error message for cron listing.
                    echo "\n\t" . get_string('skippingnocourse', 'local_sandbox', $shortname) . "\n";

                    // Inform admin.
                    local_sandbox_inform_admin(get_string('skippingnocourse', 'local_sandbox', $shortname),
                            SANDBOX_LEVEL_WARNING);

                    continue;
                }

                // Do only if the course should be reset into a new course.
                if ($localsandboxconfig->keepcourseid != 1) {
                    // Delete existing course.
                    if (!delete_course($oldcourseid, false)) {
                        // Output error message for cron listing.
                        echo "\n\t" . get_string('skippingdeletionfailed', 'local_sandbox', $shortname) . "\n";

                        // Inform admin.
                        local_sandbox_inform_admin(get_string('skippingdeletionfailed', 'local_sandbox', $shortname),
                                SANDBOX_LEVEL_WARNING);

                        continue;
                    }

                    // Create new course.
                    if (!$newcourseid = \restore_dbops::create_new_course($shortname, $shortname, $categoryid)) {
                        // Output error message for cron listing.
                        echo "\n\t" . get_string('skippingcreatefailed', 'local_sandbox', $shortname) . "\n";

                        // Inform admin.
                        local_sandbox_inform_admin(get_string('skippingcreatefailed', 'local_sandbox', $shortname),
                                SANDBOX_LEVEL_WARNING);

                        continue;
                    }
                    // Otherwise delete the content of the existing course.
                } else {
                    // Remember old course id.
                    $newcourseid = $oldcourseid;

                    // Delete course content.
                    if (!$removecontent = \restore_dbops::delete_course_content($newcourseid)) {
                        echo "\n\t" . get_string('skippingdeletecontentfailed', 'local_sandbox', $shortname) . "\n";

                        // Inform admin.
                        local_sandbox_inform_admin(get_string('skippingdeletecontentfailed', 'local_sandbox',
                                $shortname), SANDBOX_LEVEL_WARNING);

                        continue;
                    }
                }

                // Unzip course backup file to temp directory.
                $filepacker = get_file_packer('application/vnd.moodle.backup');
                $foldername = generate_uuid();
                $temppath = $CFG->dataroot . '/temp/backup/' . $foldername;
                if (!$filepacker->extract_to_pathname($file, $temppath)) {
                    // Output error message for cron listing.
                    echo "\n\t" . get_string('skippingunzipfailed', 'local_sandbox', $file) . "\n";

                    // Inform admin.
                    local_sandbox_inform_admin(get_string('skippingunzipfailed', 'local_sandbox', $shortname),
                            SANDBOX_LEVEL_WARNING);

                    continue;
                }

                // Get admin user for restore.
                $admin = get_admin();
                $restoreuser = $admin->id;

                // Restore course backup file into new course.
                if ($controller = new \local_sandbox_restore_controller($foldername, $newcourseid, \backup::INTERACTIVE_NO,
                        \backup::MODE_SAMESITE, $restoreuser, \backup::TARGET_NEW_COURSE)) {
                    $controller->get_logger()->set_next(new \output_indented_logger(\backup::LOG_INFO, false, true));
                    $controller->execute_precheck();
                    $controller->execute_plan();
                } else {
                    // Output error message for cron listing.
                    echo "\n\t" . get_string('skippingrestorefailed', 'local_sandbox', $shortname) . "\n";

                    // Inform admin.
                    local_sandbox_inform_admin(get_string('skippingrestorefailed', 'local_sandbox', $shortname),
                            SANDBOX_LEVEL_WARNING);

                    continue;
                }

                // Adjust course start date.
                if ($localsandboxconfig->adjustcoursestartdate == true) {
                    if (!$DB->update_record('course', (object)array('id' => $newcourseid, 'startdate' => time()))) {
                        // Output error message for cron listing.
                        echo "\n\t" . get_string('skippingadjuststartdatefailed', 'local_sandbox', $shortname) . "\n";

                        // Inform admin.
                        local_sandbox_inform_admin(
                                get_string('skippingadjuststartdatefailed', 'local_sandbox', $shortname),
                                SANDBOX_LEVEL_WARNING);

                        continue;
                    }
                }

                // Set shortname and fullname back.
                if ($DB->update_record('course',
                        (object)array('id' => $newcourseid, 'shortname' => $shortname, 'fullname' => $fullname))) {
                    // Output info message for cron listing.
                    echo "\n\t" . get_string('successrestored', 'local_sandbox', $shortname) . "\n";

                    // Inform admin.
                    local_sandbox_inform_admin(get_string('successrestored', 'local_sandbox', $shortname),
                            SANDBOX_LEVEL_NOTICE);

                    // Log the event.
                    $logevent = \local_sandbox\event\course_restored::create(array(
                            'objectid' => $newcourseid,
                            'context'  => \context_course::instance($newcourseid)
                    ));
                    $logevent->trigger();

                    // Fire course_updated event.
                    $course = $DB->get_record('course', array('id' => $newcourseid));
                    $ccevent = \core\event\course_created::create(array(
                            'objectid' => $course->id,
                            'context'  => \context_course::instance($course->id),
                            'other'    => array('shortname' => $course->shortname,
                                                'fullname'  => $course->fullname)
                    ));
                    $ccevent->trigger();

                    // Count successfully restored course.
                    $count++;
                } else {
                    // Output error message for cron listing.
                    echo "\n\t" . get_string('skippingdbupdatedfailed', 'local_sandbox', $shortname) . "\n";

                    // Inform admin.
                    local_sandbox_inform_admin(get_string('skippingdbupdatefailed', 'local_sandbox', $shortname),
                            SANDBOX_LEVEL_WARNING);

                    continue;
                }
            }

            // Output info message for cron listing.
            echo "\n\t".get_string('noticerestorecount', 'local_sandbox', $count)."\n";

            // Inform admin.
            local_sandbox_inform_admin(get_string('noticerestorecount', 'local_sandbox', $count), SANDBOX_LEVEL_NOTICE);

            // Otherwise, we don't have to restore anything.
        } else {
            // Output info message for cron listing.
            echo "\n\t".get_string('noticenocoursebackups', 'local_sandbox')."\n";

            // Inform admin.
            local_sandbox_inform_admin(get_string('noticenocoursebackups', 'local_sandbox'), SANDBOX_LEVEL_NOTICE);
        }

        return true;
    }
}
