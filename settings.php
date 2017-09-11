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
 * Local plugin "sandbox" - Settings
 *
 * @package    local_sandbox
 * @copyright  2013 Alexander Bias, Ulm University <alexander.bias@uni-ulm.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/lib.php');

if ($hassiteconfig) {
    // New settings page.
    $page = new admin_settingpage('sandbox', get_string('pluginname', 'local_sandbox', null, true));


    if ($ADMIN->fulltree) {
        // Execution time.
        $page->add(new admin_setting_heading('local_sandbox/cronruntimeheading',
                get_string('cronruntimeheading', 'local_sandbox', null, true),
                ''));

        $html = html_writer::tag('p', get_string('cronruntimescheduledtasksmanagement', 'local_sandbox', null, true));
        $html .= html_writer::tag('p', get_string('cronruntimescheduledtasksactivate', 'local_sandbox', null, true));
        $html .= html_writer::tag('p', get_string('cronruntimescheduledtasksstandardtime', 'local_sandbox', null, true));
        $html .= html_writer::start_tag('p');
        $html .= html_writer::link(
                new moodle_url('/admin/tool/task/scheduledtasks.php'), get_string('scheduledtasks', 'tool_task', null, true));
        $html .= html_writer::end_tag('p');
        $page->add(new admin_setting_heading('local_sandbox/cronruntimehint', '', $html));

        // Course backups.
        $page->add(new admin_setting_heading('local_sandbox/coursebackupsheading',
                get_string('coursebackupsheading', 'local_sandbox', null, true),
                ''));

        // Create course backup files directory widget.
        $page->add(new admin_setting_configdirectory('local_sandbox/coursebackupsdirectory',
                get_string('coursebackupsdirectory', 'local_sandbox', null, true),
                get_string('coursebackupsdirectory_desc', 'local_sandbox', null, true),
                $CFG->dataroot.'/sandbox'));

        // Create change course start date control widget.
        $page->add(new admin_setting_configcheckbox('local_sandbox/adjustcoursestartdate',
                get_string('adjustcoursestartdate', 'local_sandbox', null, true),
                get_string('adjustcoursestartdate_desc', 'local_sandbox', null, true),
                0));


        // Notifications.
        $page->add(new admin_setting_heading('local_sandbox/notifyheading',
                get_string('notifyheading', 'local_sandbox', null, true),
                ''));

        // Create user notification chooser widget.
        $page->add(new admin_setting_users_with_capability('local_sandbox/notifyonerrors',
                get_string('notifyonerrors', 'local_sandbox', null, true),
                get_string('notifyonerrors_desc', 'local_sandbox', null, true),
                array(),
                'moodle/site:config'));

        // Create user notification level widget.
        $levels[SANDBOX_LEVEL_NOTICE] = get_string('notice', 'core');
        $levels[SANDBOX_LEVEL_WARNING] = get_string('warning', 'core');
        $levels[SANDBOX_LEVEL_ERROR] = get_string('error', 'core');
        $page->add(new admin_setting_configselect('local_sandbox/notifylevel',
                get_string('notifylevel', 'local_sandbox', null, true),
                get_string('notifylevel_desc', 'local_sandbox', null, true),
                SANDBOX_LEVEL_ERROR,
                $levels));
    }


    // Add settings page to navigation tree.
    $ADMIN->add('courses', $page);
}
