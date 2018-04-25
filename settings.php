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

require_once(__DIR__ . '/locallib.php');

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

        // Create course backup files filearea widget.
        $page->add(new admin_setting_configstoredfile('local_sandbox/coursebackups',
                get_string('coursebackups', 'local_sandbox', null, true),
                get_string('coursebackups_desc', 'local_sandbox', null, true),
                'coursebackups',
                0,
                array('maxfiles' => -1, 'accepted_types' => '.mbz')));

        // Create change course start date control widget.
        $page->add(new admin_setting_configcheckbox('local_sandbox/adjustcoursestartdate',
                get_string('adjustcoursestartdate', 'local_sandbox', null, true),
                get_string('adjustcoursestartdate_desc', 'local_sandbox', null, true),
                0));

        // Create keep course id control widget.
        $page->add(new admin_setting_configcheckbox('local_sandbox/keepcourseid',
                get_string('keepcourseid', 'local_sandbox', null, true),
                get_string('keepcourseid_desc', 'local_sandbox', null, true),
                0));

        // Restore settings.
        $html = html_writer::tag('p', get_string('restoresettingsdescription', 'local_sandbox', null, true));
        $html .= html_writer::tag('p', get_string('restoresettingswarning', 'local_sandbox', null, true));
        $page->add(new admin_setting_heading('local_sandbox/restoreheading',
                get_string('restoreheading', 'local_sandbox', null, true),
                $html));

        // Create restore settings analogous to the Moodle core settings on /admin/settings/courses.php.
        $page->add(new admin_setting_configcheckbox('local_sandbox/restore_general_users',
                get_string('generalusers', 'backup', null, true),
                get_string('configrestoreusers', 'backup', null, true),
                0));
        $options = [
                // Can not use actual constants here because we'd need to include 100 of backup/restore files.
                // Don't use string lazy loading here because the strings will be directly used and
                // would produce a PHP warning otherwise.
                0/*backup::ENROL_NEVER*/     => get_string('rootsettingenrolments_never', 'backup'),
                1/*backup::ENROL_WITHUSERS*/ => get_string('rootsettingenrolments_withusers', 'backup'),
                2/*backup::ENROL_ALWAYS*/    => get_string('rootsettingenrolments_always', 'backup'),
        ];
        $page->add(new admin_setting_configselect('local_sandbox/restore_general_enrolments',
                get_string('generalenrolments', 'backup', null, true),
                get_string('configrestoreenrolments', 'backup', null, true),
                0,
                $options));
        $page->add(new admin_setting_configcheckbox('local_sandbox/restore_general_role_assignments',
                get_string('generalroleassignments', 'backup', null, true),
                get_string('configrestoreroleassignments', 'backup', null, true),
                0));
        $page->add(new admin_setting_configcheckbox('local_sandbox/restore_general_activities',
                get_string('generalactivities', 'backup', null, true),
                get_string('configrestoreactivities', 'backup', null, true),
                0));
        $page->add(new admin_setting_configcheckbox('local_sandbox/restore_general_blocks',
                get_string('generalblocks', 'backup', null, true),
                get_string('configrestoreblocks', 'backup', null, true),
                0));
        $page->add(new admin_setting_configcheckbox('local_sandbox/restore_general_filters',
                get_string('generalfilters', 'backup', null, true),
                get_string('configrestorefilters', 'backup', null, true),
                0));
        $page->add(new admin_setting_configcheckbox('local_sandbox/restore_general_comments',
                get_string('generalcomments', 'backup', null, true),
                get_string('configrestorecomments', 'backup', null, true),
                0));
        $page->add(new admin_setting_configcheckbox('local_sandbox/restore_general_badges',
                get_string('generalbadges', 'backup', null, true),
                get_string('configrestorebadges', 'backup', null, true),
                0));
        $page->add(new admin_setting_configcheckbox('local_sandbox/restore_general_calendarevents',
                get_string('generalcalendarevents', 'backup', null, true),
                get_string('configrestorecalendarevents', 'backup', null, true),
                0));
        $page->add(new admin_setting_configcheckbox('local_sandbox/restore_general_userscompletion',
                get_string('generaluserscompletion', 'backup', null, true),
                get_string('configrestoreuserscompletion', 'backup', null, true),
                0));
        $page->add(new admin_setting_configcheckbox('local_sandbox/restore_general_logs',
                get_string('generallogs', 'backup', null, true),
                get_string('configrestorelogs', 'backup', null, true),
                0));
        $page->add(new admin_setting_configcheckbox('local_sandbox/restore_general_histories',
                get_string('generalhistories', 'backup', null, true),
                get_string('configrestorehistories', 'backup', null, true),
                0));
        $page->add(new admin_setting_configcheckbox('local_sandbox/restore_general_groups',
                get_string('generalgroups', 'backup', null, true),
                get_string('configrestoregroups', 'backup', null, true),
                0));
        $page->add(new admin_setting_configcheckbox('local_sandbox/restore_general_competencies',
                get_string('generalcompetencies', 'backup', null, true),
                get_string('configrestorecompetencies', 'backup', null, true),
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
