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
 * Local plugin "sandbox" - Local Library
 *
 * @package    local_sandbox
 * @copyright  2013 Alexander Bias, Ulm University <alexander.bias@uni-ulm.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('SANDBOX_LEVEL_NOTICE', 1);
define('SANDBOX_LEVEL_WARNING', 2);
define('SANDBOX_LEVEL_ERROR', 3);


/**
 * Helper function for sending notifications to the configured admins.
 *
 * The notifications are sent with the Message API, so the recipients can control in their notification preferences how
 * they receive them (by default by email and as popup notification).
 *
 * @param string $message The message
 * @param int $level Notification level
 * @return bool
 */
function local_sandbox_inform_admin($message, $level = SANDBOX_LEVEL_NOTICE) {
    // Get recipients.
    $recipients = get_users_from_config(get_config('local_sandbox', 'notifyonerrors'), 'moodle/site:config');

    // If there are no recipients, don't execute.
    if (!is_array($recipients) || count($recipients) <= 0) {
        return false;
    }

    // If message level is below configured notice level, don't execute.
    if ($level < get_config('local_sandbox', 'notifylevel')) {
        return false;
    }

    // Get subject.
    if ($level > SANDBOX_LEVEL_WARNING) {
        $subject = get_string('emailsubjecterror', 'local_sandbox');
    } else if ($level > SANDBOX_LEVEL_NOTICE) {
        $subject = get_string('emailsubjectwarning', 'local_sandbox');
    } else {
        $subject = get_string('emailsubjectnotice', 'local_sandbox');
    }

    // Send notification.
    $success = true;
    foreach ($recipients as $r) {
        $messagedata = new \core\message\message();
        $messagedata->courseid = SITEID;
        $messagedata->component = 'local_sandbox';
        $messagedata->name = 'restorenotification';
        $messagedata->userfrom = \core\user::get_support_user();
        $messagedata->userto = $r;
        $messagedata->subject = $subject;
        $messagedata->fullmessage = $message;
        $messagedata->fullmessageformat = FORMAT_PLAIN;
        $messagedata->fullmessagehtml = '';
        $messagedata->smallmessage = $message;
        $messagedata->notification = 1;
        $messagedata->contexturl = (new \moodle_url('/admin/settings.php', ['section' => 'sandbox']))->out(false);
        $messagedata->contexturlname = get_string('pluginname', 'local_sandbox');
        if (!message_send($messagedata)) {
            $success = false;
        }
    }

    return $success;
}


/**
 * Helper function for fetching the list of course backup files.
 *
 * @return array
 */
function local_sandbox_getfiles() {
    // Get context.
    $context = \context_system::instance();

    // Get file storage.
    $fs = get_file_storage();

    // Get file area.
    $files = $fs->get_area_files($context->id, 'local_sandbox', 'coursebackups');

    // Initialize backup files array.
    $backupfiles = [];

    // Get course shortnames from filenames.
    foreach ($files as $file) {
        // Get filename.
        $filename = $file->get_filename();

        // Check if we really have a backup file.
        // The extension is compared case-insensitively, just like the accepted_types check of the filemanager does.
        if (core_text::strtolower(substr($filename, -4)) !== '.mbz') {
            continue;
        }

        // Extract shortname.
        $shortname = substr($filename, 0, -4);

        // Remember shortname.
        $backupfiles[$shortname] = $file;
    }

    // Return backup files array.
    return $backupfiles;
}
