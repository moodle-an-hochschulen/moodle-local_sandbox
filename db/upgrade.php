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
 * Local plugin "sandbox" - Upgrade plugin tasks
 *
 * @package    local_sandbox
 * @copyright  2013 Alexander Bias, Ulm University <alexander.bias@uni-ulm.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade steps for this plugin
 * @param int $oldversion the version we are upgrading from
 * @return boolean
 */
function xmldb_local_sandbox_upgrade($oldversion) {
    if ($oldversion < 2014051200) {
        echo html_writer::tag('div',
                get_string('upgrade_notice_2014051200', 'local_sandbox'),
                array('class' => 'alert alert-info'));

        unset_config('cronruntimehour', 'local_sandbox');
        unset_config('cronruntimemin', 'local_sandbox');
        unset_config('cronrunday', 'local_sandbox');
        upgrade_plugin_savepoint(true, 2014051200, 'local', 'sandbox');
    }

    // Fetch course backups from course backups directory and put them into the new course backups filearea.
    if ($oldversion < 2018020902) {
        // Prepare filearea.
        $context = \context_system::instance();
        $fs = get_file_storage();
        $filerecord = array('component' => 'local_sandbox', 'filearea' => 'coursebackups',
                            'contextid' => $context->id, 'itemid' => 0, 'filepath' => '/',
                            'filename' => '');

        // Prepare documents directory.
        $coursebackupsdirectory = get_config('local_sandbox', 'coursebackupsdirectory');
        $handle = @opendir($coursebackupsdirectory);

        if ($handle) {
            // Array to remember file to be deleted from course backups directory.
            $todelete = array();

            // Fetch all files from course backups directory.
            while (false !== ($file = readdir($handle))) {
                // Only process .mbz files.
                $isbackup = strpos($file, '.mbz');
                if (!$isbackup) {
                    continue;
                }

                // Compose file name and path.
                $filerecord['filename'] = $file;
                $fullpath = $coursebackupsdirectory . '/' . $file;

                // Put file into filearea.
                $fs->create_file_from_pathname($filerecord, $fullpath);

                // Remember file to be deleted.
                $todelete[] = $fullpath;
            }

            // Close course backups directory.
            if ($handle) {
                closedir($handle);
            }

            // Show an info message that course backups directory is no longer needed.
            $message = get_string('upgrade_notice_2018020902', 'local_sandbox', $coursebackupsdirectory);
            echo html_writer::tag('div', $message, array('class' => 'alert alert-info'));
        }

        // Remove course backups directory setting because it is not needed anymore.
        set_config('coursebackupsdirectory', null, 'local_sandbox');

        // Remember upgrade savepoint.
        upgrade_plugin_savepoint(true, 2018020902, 'local', 'sandbox');
    }

    return true;
}
