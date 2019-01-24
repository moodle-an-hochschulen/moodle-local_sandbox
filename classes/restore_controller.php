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
 * Local plugin "sandbox" - Class definition
 *
 * @package    local_sandbox
 * @copyright  2014 Alexander Bias, Ulm University <alexander.bias@uni-ulm.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * This class inherits basically everything from restore_controller except the fact that it applies the plugin's restore settings.
 *
 * @package    local_sandbox
 * @copyright  2014 Alexander Bias, Ulm University <alexander.bias@uni-ulm.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_sandbox_restore_controller extends restore_controller {

    /**
     * Apply the plugin-specific restore settings
     */
    protected function apply_defaults() {
        $this->log('applying restore defaults', backup::LOG_DEBUG);
        restore_controller_dbops::apply_config_defaults($this);

        /* KIZ MODIFICATION START
           REASON: Overwrite default restore settings with plugin-specific restore settings. */
        $settings = array(
                'restore_general_users'              => 'users',
                'restore_general_enrolments'         => 'enrolments',
                'restore_general_role_assignments'   => 'role_assignments',
                'restore_general_activities'         => 'activities',
                'restore_general_blocks'             => 'blocks',
                'restore_general_filters'            => 'filters',
                'restore_general_comments'           => 'comments',
                'restore_general_badges'             => 'badges',
                'restore_general_calendarevents'     => 'calendarevents',
                'restore_general_userscompletion'    => 'userscompletion',
                'restore_general_logs'               => 'logs',
                'restore_general_histories'          => 'grade_histories',
                'restore_general_groups'             => 'groups',
                'restore_general_competencies'       => 'competencies'
        );

        $plan = $this->get_plan();
        foreach ($settings as $config => $settingname) {
            if ($plan->setting_exists($settingname)) {
                $setting = $plan->get_setting($settingname);
                $value = get_config('local_sandbox', $config);
                $setting->set_status(base_setting::NOT_LOCKED); // Otherwise, we won't be allowed to set the value now.
                // Only change the setting if the corresponding XML file is integrated in the backup file.
                // We can only disable restore settings of the restore plan has enabled them.
                // Enabling restore settings which are not enabled in the restore plan would let the restore job fail
                // with the error "Backup is missing XML file".
                if ($setting->get_value() == true) {
                    $setting->set_value($value);
                }
            }
        }
        /* KIZ MODIFICATION END */

        $this->set_status(backup::STATUS_CONFIGURED);
    }
}
