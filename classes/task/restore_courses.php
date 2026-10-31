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

use Exception;
use restore_controller_exception;

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
     * @throws restore_controller_exception
     * @throws Exception
     */
    public function execute() {
        global $CFG, $DB;

        require_once($CFG->libdir . '/moodlelib.php');
        require_once($CFG->libdir . '/filestorage/zip_packer.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

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
                if ($oldcourse = $DB->get_record('course', ['shortname' => $shortname])) {
                    $oldcourseid = $oldcourse->id;
                    $categoryid = $oldcourse->category;
                    $fullname = $oldcourse->fullname;
                } else {
                    // Output error message for cron listing.
                    echo "\n\t" . get_string('skippingnocourse', 'local_sandbox', $shortname) . "\n";

                    // Inform admin.
                    local_sandbox_inform_admin(
                        get_string('skippingnocourse', 'local_sandbox', $shortname),
                        SANDBOX_LEVEL_WARNING
                    );

                    continue;
                }

                // Do only if the course should be reset into a new course.
                if ($localsandboxconfig->keepcourseid != 1) {
                    // Delete existing course.
                    if (!delete_course($oldcourseid, false)) {
                        // Output error message for cron listing.
                        echo "\n\t" . get_string('skippingdeletionfailed', 'local_sandbox', $shortname) . "\n";

                        // Inform admin.
                        local_sandbox_inform_admin(
                            get_string('skippingdeletionfailed', 'local_sandbox', $shortname),
                            SANDBOX_LEVEL_WARNING
                        );

                        continue;
                    }

                    // Create new course.
                    if (!$newcourseid = \restore_dbops::create_new_course($shortname, $shortname, $categoryid)) {
                        // Output error message for cron listing.
                        echo "\n\t" . get_string('skippingcreatefailed', 'local_sandbox', $shortname) . "\n";

                        // Inform admin.
                        local_sandbox_inform_admin(
                            get_string('skippingcreatefailed', 'local_sandbox', $shortname),
                            SANDBOX_LEVEL_WARNING
                        );

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
                        local_sandbox_inform_admin(get_string(
                            'skippingdeletecontentfailed',
                            'local_sandbox',
                            $shortname
                        ), SANDBOX_LEVEL_WARNING);

                        continue;
                    }
                }

                // Unzip course backup file to temp directory.
                $filepacker = get_file_packer('application/vnd.moodle.backup');
                $backupid = \restore_controller::get_tempdir_name($newcourseid);
                $temppath = make_backup_temp_directory($backupid);
                if (!$filepacker->extract_to_pathname($file, $temppath)) {
                    // Output error message for cron listing.
                    echo "\n\t" . get_string('skippingunzipfailed', 'local_sandbox', $shortname) . "\n";

                    // Inform admin.
                    local_sandbox_inform_admin(
                        get_string('skippingunzipfailed', 'local_sandbox', $shortname),
                        SANDBOX_LEVEL_WARNING
                    );

                    continue;
                }

                // Get admin user for restore.
                $admin = get_admin();
                $restoreuser = $admin->id;

                // Restore course backup file into new course.
                $controller = null;
                $transactionstartedbefore = $DB->is_transaction_started();
                try {
                    $controller = new \local_sandbox_restore_controller(
                        $backupid,
                        $newcourseid,
                        \backup::INTERACTIVE_NO,
                        \backup::MODE_SAMESITE,
                        $restoreuser,
                        \backup::TARGET_NEW_COURSE
                    );

                    // If the backup file is not in the Moodle 2 format, the controller doesn't have a restore plan and
                    // can't be destroyed, so only destroy its logger to close the log file.
                    // Converting the backup file is not supported, so skip it.
                    if ($controller->get_status() == \backup::STATUS_REQUIRE_CONV) {
                        $controller->get_logger()->destroy();
                        $controller = null;
                        throw new \moodle_exception('errorinvalidformat', 'backup');
                    }

                    $controller->get_logger()->set_next(new \output_indented_logger(\backup::LOG_INFO, false, true));
                    if (!$controller->execute_precheck()) {
                        $precheckresults = $controller->get_precheck_results();

                        // If there were precheck warnings, output them, but continue with the restore.
                        if (array_key_exists('warnings', $precheckresults)) {
                            echo "\n\t" . get_string('precheckwarning', 'local_sandbox') . ':';
                            foreach ($precheckresults['warnings'] as $warning) {
                                echo "\n\t\t* " . $warning;
                            }
                            echo "\n";
                        }

                        // If there were precheck errors, the backup file can't be restored, so skip it.
                        if (array_key_exists('errors', $precheckresults)) {
                            $errortext = get_string('skippingprecheckfailed', 'local_sandbox', $shortname) . "\n" .
                                    get_string('precheckerror', 'local_sandbox') . ':';
                            foreach ($precheckresults['errors'] as $error) {
                                $errortext .= "\n* " . $error;
                            }

                            // Output error message for cron listing.
                            echo "\n\t" . str_replace("\n", "\n\t\t", $errortext) . "\n";

                            // Inform admin.
                            local_sandbox_inform_admin($errortext, SANDBOX_LEVEL_WARNING);

                            $controller->destroy();
                            continue;
                        }
                    }
                    $controller->execute_plan();
                    $controller->destroy();
                } catch (Exception $e) {
                    // If the restore was aborted within a database transaction, roll it back before the next file is processed
                    // (like the cron does after a failed task). This is only done if the transaction has been started by the
                    // restore as a forced rollback would also roll back any transaction which has been started by the caller.
                    if (!$transactionstartedbefore && $DB->is_transaction_started()) {
                        $DB->force_transaction_rollback();
                    }

                    if ($controller !== null) {
                        $controller->destroy();
                    }

                    $errortext = get_string('skippingrestorefailed', 'local_sandbox', $shortname) . "\n" . $e->getMessage();

                    // Output error message for cron listing.
                    echo "\n\t" . str_replace("\n", "\n\t\t", $errortext) . "\n";

                    // Inform admin.
                    local_sandbox_inform_admin($errortext, SANDBOX_LEVEL_WARNING);

                    continue;
                }

                // The following course adjustments are written directly to the database instead of using update_course().
                // This is done on purpose, just like the restore itself writes the course record directly:
                // - update_course() is meant for course edits by a user and would trigger additional hooks and a
                //   course_updated event for each adjustment, whereas we fire the appropriate core event once below.
                // - update_course() throws exceptions on validation errors which would have to be caught to keep
                //   processing the remaining backup files.
                // - The values are written right after the restore and before anything has read the course again,
                //   so there aren't any stale caches which would have to be purged by update_course().

                // Adjust course start date.
                if ($localsandboxconfig->adjustcoursestartdate == true) {
                    if (!$DB->update_record('course', (object)['id' => $newcourseid, 'startdate' => time()])) {
                        // Output error message for cron listing.
                        echo "\n\t" . get_string('skippingadjuststartdatefailed', 'local_sandbox', $shortname) . "\n";

                        // Inform admin.
                        local_sandbox_inform_admin(
                            get_string('skippingadjuststartdatefailed', 'local_sandbox', $shortname),
                            SANDBOX_LEVEL_WARNING
                        );

                        continue;
                    }
                }

                // Set shortname and fullname (if configured) back.
                if ($localsandboxconfig->keepcoursename == 1) {
                    $coursedata = ['id' => $newcourseid, 'shortname' => $shortname, 'fullname' => $fullname];
                } else {
                    $coursedata = ['id' => $newcourseid, 'shortname' => $shortname];
                }
                if ($DB->update_record('course', (object)$coursedata)) {
                    // Output info message for cron listing.
                    echo "\n\t" . get_string('successrestored', 'local_sandbox', $shortname) . "\n";

                    // Inform admin.
                    local_sandbox_inform_admin(
                        get_string('successrestored', 'local_sandbox', $shortname),
                        SANDBOX_LEVEL_NOTICE
                    );

                    // Log the event.
                    $logevent = \local_sandbox\event\course_restored::create([
                            'objectid' => $newcourseid,
                            'context' => \context_course::instance($newcourseid),
                    ]);
                    $logevent->trigger();

                    // Fire Moodle core's course_created event if the course has been recreated or
                    // Moodle core's course_updated event if the existing course has been kept
                    // (both are not fired by the restore itself).
                    $course = $DB->get_record('course', ['id' => $newcourseid]);
                    $eventdata = [
                            'objectid' => $course->id,
                            'context' => \context_course::instance($course->id),
                            'other' => ['shortname' => $course->shortname,
                                    'fullname' => $course->fullname, ],
                    ];
                    if ($localsandboxconfig->keepcourseid != 1) {
                        $courseevent = \core\event\course_created::create($eventdata);
                    } else {
                        $courseevent = \core\event\course_updated::create($eventdata);
                    }
                    $courseevent->trigger();

                    // Count successfully restored course.
                    $count++;
                } else {
                    // Output error message for cron listing.
                    echo "\n\t" . get_string('skippingdbupdatefailed', 'local_sandbox', $shortname) . "\n";

                    // Inform admin.
                    local_sandbox_inform_admin(
                        get_string('skippingdbupdatefailed', 'local_sandbox', $shortname),
                        SANDBOX_LEVEL_WARNING
                    );

                    continue;
                }
            }

            // Output info message for cron listing.
            echo "\n\t" . get_string('noticerestorecount', 'local_sandbox', $count) . "\n";

            // Inform admin.
            local_sandbox_inform_admin(get_string('noticerestorecount', 'local_sandbox', $count), SANDBOX_LEVEL_NOTICE);

            // Otherwise, we don't have to restore anything.
        } else {
            // Output info message for cron listing.
            echo "\n\t" . get_string('noticenocoursebackups', 'local_sandbox') . "\n";

            // Inform admin.
            local_sandbox_inform_admin(get_string('noticenocoursebackups', 'local_sandbox'), SANDBOX_LEVEL_NOTICE);
        }

        return true;
    }
}
