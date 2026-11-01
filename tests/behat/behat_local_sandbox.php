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
 * Local plugin "sandbox" - Behat steps definitions
 *
 * @package    local_sandbox
 * @category   test
 * @copyright  2026 Alexander Bias <bias@alexanderbias.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_local_sandbox extends behat_base {
    // phpcs:disable moodle.Files.LineLength
    /**
     * Backs up the current state of the given course (including all data which can be restored by the sandbox restore
     * settings) and stores the course backup file with the given filename in the sandbox course backups filearea.
     * An existing course backup file with the same filename is replaced.
     *
     * The static course backup file in tests/fixtures does not contain any users, blocks, groups, badges etc.
     * This step is used to create a course backup file with such data on the fly.
     *
     * @Given /^the course "(?P<shortname_string>(?:[^"]|\\")*)" is backed up to the sandbox course backup file "(?P<filename_string>(?:[^"]|\\")*)"$/
     *
     * @param string $shortname The shortname of the course to be backed up.
     * @param string $filename The filename of the course backup file in the sandbox course backups filearea.
     */
    public function the_course_is_backed_up_to_the_sandbox_course_backup_file($shortname, $filename) {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

        $course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);

        // Create the course backup.
        $controller = new \backup_controller(
            \backup::TYPE_1COURSE,
            $course->id,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            get_admin()->id
        );

        // Include all data which can be controlled by the sandbox restore settings.
        $settings = ['users', 'role_assignments', 'activities', 'blocks', 'filters', 'comments', 'badges', 'calendarevents',
                'userscompletion', 'logs', 'grade_histories', 'groups', 'competencies', ];
        $plan = $controller->get_plan();
        foreach ($settings as $settingname) {
            if ($plan->setting_exists($settingname)) {
                $setting = $plan->get_setting($settingname);
                $setting->set_status(\base_setting::NOT_LOCKED);
                $setting->set_value(1);
            }
        }

        $controller->execute_plan();
        $backupfile = $controller->get_results()['backup_destination'];

        // Store the course backup file in the sandbox course backups filearea.
        $fs = get_file_storage();
        $filerecord = [
            'contextid' => \context_system::instance()->id,
            'component' => 'local_sandbox',
            'filearea' => 'coursebackups',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => $filename,
        ];
        $existingfile = $fs->get_file(
            $filerecord['contextid'],
            $filerecord['component'],
            $filerecord['filearea'],
            $filerecord['itemid'],
            $filerecord['filepath'],
            $filerecord['filename']
        );
        if ($existingfile) {
            $existingfile->delete();
        }
        $fs->create_file_from_storedfile($filerecord, $backupfile);

        // Clean up.
        $backupfile->delete();
        $controller->destroy();
    }
}
