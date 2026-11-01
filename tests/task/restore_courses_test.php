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

namespace local_sandbox\task;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/sandbox/locallib.php');
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

/**
 * Local plugin "sandbox" - Tests for the restore courses scheduled task
 *
 * @package    local_sandbox
 * @copyright  2026 Alexander Bias <bias@alexanderbias.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_sandbox\task\restore_courses
 */
final class restore_courses_test extends \advanced_testcase {
    /** @var string The shortname of the sandbox course which is used in the tests. */
    private const SHORTNAME = 'sandbox-test';

    /** @var \phpunit_phpmailer_sink The email sink. */
    private $mailsink;

    /** @var \phpunit_event_sink The event sink. */
    private $eventsink;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();

        // Send all notifications (also the notices) to the admin.
        set_config('notifyonerrors', get_admin()->id, 'local_sandbox');
        set_config('notifylevel', SANDBOX_LEVEL_NOTICE, 'local_sandbox');

        // Restore the activities from the course backup files (they are not restored by default).
        set_config('restore_general_activities', 1, 'local_sandbox');

        $this->mailsink = $this->redirectEmails();
        $this->eventsink = $this->redirectEvents();
    }

    /**
     * Tear down the test environment.
     */
    protected function tearDown(): void {
        $this->mailsink->close();
        $this->eventsink->close();
        parent::tearDown();
    }

    /**
     * Run the scheduled task and return its output.
     *
     * @return string
     */
    private function run_task(): string {
        // Only keep the events which are triggered by the task (and not the ones from the test setup).
        $this->eventsink->clear();

        $task = new restore_courses();
        ob_start();
        try {
            $this->assertTrue($task->execute());
        } finally {
            $output = ob_get_clean();
        }
        return $output;
    }

    /**
     * Add a file to the course backups filearea of the plugin.
     *
     * @param string $filename The filename.
     * @param string|\stored_file $source The path of the file on disk or a stored file.
     */
    private function add_backup_file(string $filename, $source): void {
        $fs = get_file_storage();
        $record = [
            'contextid' => \context_system::instance()->id,
            'component' => 'local_sandbox',
            'filearea' => 'coursebackups',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => $filename,
        ];
        if ($source instanceof \stored_file) {
            $fs->create_file_from_storedfile($record, $source);
        } else {
            $fs->create_file_from_pathname($record, $source);
        }
    }

    /**
     * Get the path of the course backup fixture.
     *
     * @return string
     */
    private function get_sandbox_fixture_path(): string {
        global $CFG;
        return $CFG->dirroot . '/local/sandbox/tests/fixtures/sandbox-test.mbz';
    }

    /**
     * Create a backup of the front page.
     *
     * Restoring a front page backup into a normal course is refused by the restore prechecks with an error.
     *
     * @return \stored_file
     */
    private function create_frontpage_backup(): \stored_file {
        $bc = new \backup_controller(
            \backup::TYPE_1COURSE,
            SITEID,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            get_admin()->id
        );
        $bc->execute_plan();
        $file = $bc->get_results()['backup_destination'];
        $bc->destroy();
        return $file;
    }

    /**
     * Create a valid ZIP archive which does not contain a Moodle backup.
     *
     * The archive can be unzipped, but creating the restore controller for it throws an exception.
     *
     * @return string The path of the archive.
     */
    private function create_non_backup_archive(): string {
        $path = make_request_directory() . '/nobackup.mbz';
        get_file_packer('application/zip')->archive_to_pathname(['dummy.txt' => ['This is not a Moodle backup.']], $path);
        return $path;
    }

    /**
     * Create a copy of the course backup fixture which claims to come from a newer Moodle version.
     *
     * Restoring this backup triggers a restore precheck warning, but no error.
     *
     * @return string The path of the archive.
     */
    private function create_newer_backup(): string {
        return $this->create_modified_backup(
            'moodle_backup.xml',
            '~<moodle_version>[^<]*</moodle_version>~',
            '<moodle_version>9999999999.00</moodle_version>'
        );
    }

    /**
     * Create a copy of the course backup fixture which contains the given course end date.
     *
     * The course backup fixture itself contains the course end date 2001-01-01.
     *
     * @param int $enddate
     * @return string The path of the archive.
     */
    private function create_backup_with_enddate(int $enddate): string {
        return $this->create_modified_backup(
            'course/course.xml',
            '~<enddate>[^<]*</enddate>~',
            '<enddate>' . $enddate . '</enddate>'
        );
    }

    /**
     * Create a copy of the course backup fixture with a modified XML file.
     *
     * @param string $xmlfile The path of the XML file within the backup.
     * @param string $pattern The regular expression to search for in the XML file.
     * @param string $replacement The replacement for the matches.
     * @return string The path of the archive.
     */
    private function create_modified_backup(string $xmlfile, string $pattern, string $replacement): string {
        $packer = get_file_packer('application/vnd.moodle.backup');
        $dir = make_request_directory();
        $packer->extract_to_pathname($this->get_sandbox_fixture_path(), $dir);

        $xmlpath = $dir . '/' . $xmlfile;
        $xml = preg_replace($pattern, $replacement, file_get_contents($xmlpath));
        file_put_contents($xmlpath, $xml);

        $files = [];
        foreach (get_directory_list($dir) as $relativepath) {
            $files[$relativepath] = $dir . '/' . $relativepath;
        }
        $path = make_request_directory() . '/modified.mbz';
        $packer->archive_to_pathname($files, $path);
        return $path;
    }

    /**
     * Create the sandbox course with an activity which is not part of the course backup fixture.
     *
     * @param array $record Additional course properties.
     * @return \stdClass
     */
    private function create_sandbox_course(array $record = []): \stdClass {
        $course = $this->getDataGenerator()->create_course(array_merge(['shortname' => self::SHORTNAME], $record));
        $this->getDataGenerator()->create_module('assign', ['course' => $course->id, 'name' => 'This is an assignment']);
        return $course;
    }

    /**
     * Get the names of all activities in the course with the given shortname.
     *
     * @param string $shortname
     * @return string[]
     */
    private function get_activity_names(string $shortname): array {
        global $DB;
        $course = $DB->get_record('course', ['shortname' => $shortname], '*', MUST_EXIST);
        $names = [];
        foreach (get_fast_modinfo($course->id)->get_cms() as $cm) {
            $names[] = $cm->modname;
        }
        sort($names);
        return $names;
    }

    /**
     * Assert that an email with the given subject and text was sent.
     *
     * @param string $subject
     * @param string $text
     */
    private function assert_email_sent(string $subject, string $text): void {
        foreach ($this->mailsink->get_messages() as $message) {
            if (
                $message->subject === $subject &&
                    str_contains(quoted_printable_decode($message->body), $text)
            ) {
                $this->addToAssertionCount(1);
                return;
            }
        }
        $this->fail('No email with subject "' . $subject . '" and text "' . $text . '" was sent.');
    }

    /**
     * Get the triggered events of the given class.
     *
     * @param string $classname
     * @return \core\event\base[]
     */
    private function get_events(string $classname): array {
        return array_values(array_filter(
            $this->eventsink->get_events(),
            fn($event) => get_class($event) === $classname
        ));
    }

    /**
     * Test that the task does nothing if there are no course backup files.
     */
    public function test_no_backup_files(): void {
        $this->create_sandbox_course();

        $output = $this->run_task();

        $notice = get_string('noticenocoursebackups', 'local_sandbox');
        $this->assertStringContainsString($notice, $output);
        $this->assertCount(1, $this->mailsink->get_messages());
        $this->assert_email_sent(get_string('emailsubjectnotice', 'local_sandbox'), $notice);
        $this->assertEquals(['assign'], $this->get_activity_names(self::SHORTNAME));
        $this->assertCount(0, $this->get_events(\core\event\course_restored::class));
        $this->assertCount(0, $this->get_events(\local_sandbox\event\course_restored::class));
        $this->assertCount(0, $this->get_events(\core\event\course_created::class));
        $this->assertCount(0, $this->get_events(\core\event\course_updated::class));
    }

    /**
     * Test that files without the .mbz extension are ignored.
     */
    public function test_non_mbz_files_are_ignored(): void {
        $this->create_sandbox_course();
        $this->add_backup_file(self::SHORTNAME . '.zip', $this->get_sandbox_fixture_path());

        $output = $this->run_task();

        $notice = get_string('noticenocoursebackups', 'local_sandbox');
        $this->assertStringContainsString($notice, $output);
        $this->assertCount(1, $this->mailsink->get_messages());
        $this->assert_email_sent(get_string('emailsubjectnotice', 'local_sandbox'), $notice);
        $this->assertEquals(['assign'], $this->get_activity_names(self::SHORTNAME));
        $this->assertCount(0, $this->get_events(\core\event\course_restored::class));
        $this->assertCount(0, $this->get_events(\local_sandbox\event\course_restored::class));
        $this->assertCount(0, $this->get_events(\core\event\course_created::class));
        $this->assertCount(0, $this->get_events(\core\event\course_updated::class));
    }

    /**
     * Test that a course backup file without a matching course is skipped.
     */
    public function test_skip_if_no_matching_course(): void {
        $this->add_backup_file('nonexisting.mbz', $this->get_sandbox_fixture_path());

        $output = $this->run_task();

        $warning = get_string('skippingnocourse', 'local_sandbox', 'nonexisting');
        $this->assertStringContainsString($warning, $output);
        $this->assertStringContainsString(get_string('noticerestorecount', 'local_sandbox', 0), $output);
        $this->assert_email_sent(get_string('emailsubjectwarning', 'local_sandbox'), $warning);
        $this->assertCount(0, $this->get_events(\core\event\course_restored::class));
        $this->assertCount(0, $this->get_events(\local_sandbox\event\course_restored::class));
        $this->assertCount(0, $this->get_events(\core\event\course_created::class));
        $this->assertCount(0, $this->get_events(\core\event\course_updated::class));
    }

    /**
     * Data provider for tests which run with both values of the "Keep course ID" setting.
     *
     * @return array
     */
    public static function keepcourseid_provider(): array {
        return [
            'Keep course ID disabled' => ['keepcourseid' => 0],
            'Keep course ID enabled' => ['keepcourseid' => 1],
        ];
    }

    /**
     * Test the successful restore of a course.
     *
     * @dataProvider keepcourseid_provider
     * @param int $keepcourseid
     */
    public function test_successful_restore(int $keepcourseid): void {
        global $DB;

        set_config('keepcourseid', $keepcourseid, 'local_sandbox');
        $oldcourse = $this->create_sandbox_course();
        $this->add_backup_file(self::SHORTNAME . '.mbz', $this->get_sandbox_fixture_path());

        $output = $this->run_task();

        $success = get_string('successrestored', 'local_sandbox', self::SHORTNAME);
        $this->assertStringContainsString($success, $output);
        $this->assertStringContainsString(get_string('noticerestorecount', 'local_sandbox', 1), $output);
        $this->assertStringNotContainsString(get_string('precheckerror', 'local_sandbox'), $output);
        $this->assert_email_sent(get_string('emailsubjectnotice', 'local_sandbox'), $success);

        // The assignment is gone, the activities of the backup are there.
        $this->assertEquals(['forum', 'label'], $this->get_activity_names(self::SHORTNAME));

        $newcourse = $DB->get_record('course', ['shortname' => self::SHORTNAME], '*', MUST_EXIST);
        if ($keepcourseid == 1) {
            $this->assertEquals($oldcourse->id, $newcourse->id);
        } else {
            $this->assertNotEquals($oldcourse->id, $newcourse->id);
            $this->assertFalse($DB->record_exists('course', ['id' => $oldcourse->id]));
        }

        // The course event depends on whether the existing course has been kept or the course has been recreated.
        if ($keepcourseid == 1) {
            $this->assertCount(0, $this->get_events(\core\event\course_created::class));
            $courseevents = $this->get_events(\core\event\course_updated::class);
        } else {
            $this->assertCount(0, $this->get_events(\core\event\course_updated::class));
            $courseevents = $this->get_events(\core\event\course_created::class);
        }
        $this->assertCount(1, $courseevents);
        $this->assertEquals(
            ['shortname' => $newcourse->shortname, 'fullname' => $newcourse->fullname],
            $courseevents[0]->other
        );

        $coreevents = $this->get_events(\core\event\course_restored::class);
        $this->assertCount(1, $coreevents);
        $sandboxevents = $this->get_events(\local_sandbox\event\course_restored::class);
        $this->assertCount(1, $sandboxevents);

        $context = \context_course::instance($newcourse->id);
        foreach ([$coreevents[0], $sandboxevents[0], $courseevents[0]] as $event) {
            $this->assertEquals($newcourse->id, $event->objectid);
            $this->assertEquals($context->id, $event->contextid);
        }
    }

    /**
     * Data provider for test_keepcoursename.
     *
     * @return array
     */
    public static function keepcoursename_provider(): array {
        return [
            'Keep course name disabled' => ['keepcoursename' => 0, 'expectedfullname' => 'Sandbox Test Course'],
            'Keep course name enabled' => ['keepcoursename' => 1, 'expectedfullname' => 'My own sandbox course'],
        ];
    }

    /**
     * Test the "Keep course name" setting.
     *
     * @dataProvider keepcoursename_provider
     * @param int $keepcoursename
     * @param string $expectedfullname
     */
    public function test_keepcoursename(int $keepcoursename, string $expectedfullname): void {
        global $DB;

        set_config('keepcoursename', $keepcoursename, 'local_sandbox');
        $this->create_sandbox_course(['fullname' => 'My own sandbox course']);
        $this->add_backup_file(self::SHORTNAME . '.mbz', $this->get_sandbox_fixture_path());

        $this->run_task();

        $course = $DB->get_record('course', ['shortname' => self::SHORTNAME], '*', MUST_EXIST);
        $this->assertEquals($expectedfullname, $course->fullname);
        $this->assertCount(1, $this->get_events(\core\event\course_restored::class));
        $this->assertCount(1, $this->get_events(\local_sandbox\event\course_restored::class));
        $this->assertCount(1, $this->get_events(\core\event\course_created::class));
        $this->assertCount(0, $this->get_events(\core\event\course_updated::class));
    }

    /**
     * Test the "Adjust course start date" setting.
     *
     * @testWith [0]
     *           [1]
     * @param int $adjustcoursestartdate
     */
    public function test_adjustcoursestartdate(int $adjustcoursestartdate): void {
        global $DB;

        set_config('adjustcoursestartdate', $adjustcoursestartdate, 'local_sandbox');
        $this->create_sandbox_course(['startdate' => 946684800]);
        $this->add_backup_file(self::SHORTNAME . '.mbz', $this->create_backup_with_enddate(0));
        $before = time();

        $this->run_task();

        $course = $DB->get_record('course', ['shortname' => self::SHORTNAME], '*', MUST_EXIST);
        if ($adjustcoursestartdate == 1) {
            $this->assertGreaterThanOrEqual($before, $course->startdate);
        } else {
            $this->assertLessThan($before, $course->startdate);
        }
        // A course without an end date must not get one.
        $this->assertEquals(0, $course->enddate);
        $this->assertCount(1, $this->get_events(\core\event\course_restored::class));
        $this->assertCount(1, $this->get_events(\local_sandbox\event\course_restored::class));
        $this->assertCount(1, $this->get_events(\core\event\course_created::class));
        $this->assertCount(0, $this->get_events(\core\event\course_updated::class));
    }

    /**
     * Test that the "Adjust course start date" setting shifts the course end date by the same offset.
     *
     * @testWith [0]
     *           [1]
     * @param int $adjustcoursestartdate
     */
    public function test_adjustcoursestartdate_shifts_enddate(int $adjustcoursestartdate): void {
        global $DB;

        // The course start date in the course backup fixture is 2000-01-01, the course duration is set to 365 days.
        $backupstartdate = 946684800;
        $duration = 365 * DAYSECS;

        set_config('adjustcoursestartdate', $adjustcoursestartdate, 'local_sandbox');
        $this->create_sandbox_course();
        $this->add_backup_file(self::SHORTNAME . '.mbz', $this->create_backup_with_enddate($backupstartdate + $duration));
        $before = time();

        $this->run_task();

        $course = $DB->get_record('course', ['shortname' => self::SHORTNAME], '*', MUST_EXIST);
        if ($adjustcoursestartdate == 1) {
            $this->assertGreaterThanOrEqual($before, $course->startdate);
        } else {
            $this->assertEquals($backupstartdate, $course->startdate);
        }
        $this->assertEquals($duration, $course->enddate - $course->startdate);
    }

    /**
     * Test that a course backup file which can't be unzipped is skipped.
     *
     * @dataProvider keepcourseid_provider
     * @param int $keepcourseid
     */
    public function test_skip_if_unzip_failed(int $keepcourseid): void {
        set_config('keepcourseid', $keepcourseid, 'local_sandbox');
        $this->create_sandbox_course();
        $path = make_request_directory() . '/corrupt.mbz';
        file_put_contents($path, 'This is neither a ZIP nor a TGZ archive.');
        $this->add_backup_file(self::SHORTNAME . '.mbz', $path);

        $output = $this->run_task();
        $this->assertDebuggingCalled();

        $warning = get_string('skippingunzipfailed', 'local_sandbox', self::SHORTNAME);
        $this->assertStringContainsString($warning, $output);
        $this->assertStringContainsString(get_string('noticerestorecount', 'local_sandbox', 0), $output);
        $this->assert_email_sent(get_string('emailsubjectwarning', 'local_sandbox'), $warning);
        $this->assertCount(0, $this->get_events(\core\event\course_restored::class));
        $this->assertCount(0, $this->get_events(\local_sandbox\event\course_restored::class));
        $this->assertCount(0, $this->get_events(\core\event\course_created::class));
        $this->assertCount(0, $this->get_events(\core\event\course_updated::class));
    }

    /**
     * Test that a course backup file with restore precheck errors is skipped.
     *
     * @dataProvider keepcourseid_provider
     * @param int $keepcourseid
     */
    public function test_skip_if_precheck_errors(int $keepcourseid): void {
        set_config('keepcourseid', $keepcourseid, 'local_sandbox');
        $this->create_sandbox_course();
        $this->add_backup_file(self::SHORTNAME . '.mbz', $this->create_frontpage_backup());

        $output = $this->run_task();

        $warning = get_string('skippingprecheckfailed', 'local_sandbox', self::SHORTNAME);
        $precheckerror = get_string('errorrestorefrontpagebackup', 'backup');
        $this->assertStringContainsString($warning, $output);
        $this->assertStringContainsString(get_string('precheckerror', 'local_sandbox') . ':', $output);
        $this->assertStringContainsString('* ' . $precheckerror, $output);
        $this->assertStringNotContainsString(get_string('successrestored', 'local_sandbox', self::SHORTNAME), $output);
        $this->assertStringContainsString(get_string('noticerestorecount', 'local_sandbox', 0), $output);
        $this->assert_email_sent(get_string('emailsubjectwarning', 'local_sandbox'), $warning);
        $this->assert_email_sent(get_string('emailsubjectwarning', 'local_sandbox'), $precheckerror);
        $this->assertCount(0, $this->get_events(\core\event\course_restored::class));
        $this->assertCount(0, $this->get_events(\local_sandbox\event\course_restored::class));
        $this->assertCount(0, $this->get_events(\core\event\course_created::class));
        $this->assertCount(0, $this->get_events(\core\event\course_updated::class));

        // The course still exists, but the content has already been removed before the prechecks.
        $this->assertEquals([], $this->get_activity_names(self::SHORTNAME));
    }

    /**
     * Test that restore precheck warnings are logged, but the course is restored anyway.
     */
    public function test_restore_despite_precheck_warnings(): void {
        $this->create_sandbox_course();
        $this->add_backup_file(self::SHORTNAME . '.mbz', $this->create_newer_backup());

        $output = $this->run_task();

        $this->assertStringContainsString(get_string('precheckwarning', 'local_sandbox') . ':', $output);
        $this->assertStringNotContainsString(get_string('precheckerror', 'local_sandbox'), $output);
        $this->assertStringContainsString(get_string('successrestored', 'local_sandbox', self::SHORTNAME), $output);
        $this->assertStringContainsString(get_string('noticerestorecount', 'local_sandbox', 1), $output);
        $this->assertEquals(['forum', 'label'], $this->get_activity_names(self::SHORTNAME));
        $this->assertCount(1, $this->get_events(\core\event\course_restored::class));
        $this->assertCount(1, $this->get_events(\local_sandbox\event\course_restored::class));
        $this->assertCount(1, $this->get_events(\core\event\course_created::class));
        $this->assertCount(0, $this->get_events(\core\event\course_updated::class));
    }

    /**
     * Test that a course backup file whose restore throws an exception is skipped.
     *
     * @dataProvider keepcourseid_provider
     * @param int $keepcourseid
     */
    public function test_skip_if_restore_throws_exception(int $keepcourseid): void {
        set_config('keepcourseid', $keepcourseid, 'local_sandbox');
        $this->create_sandbox_course();
        $this->add_backup_file(self::SHORTNAME . '.mbz', $this->create_non_backup_archive());

        $output = $this->run_task();

        $warning = get_string('skippingrestorefailed', 'local_sandbox', self::SHORTNAME);
        $this->assertStringContainsString($warning, $output);
        $this->assertStringContainsString(get_string('errorinvalidformat', 'backup'), $output);
        $this->assertStringNotContainsString(get_string('successrestored', 'local_sandbox', self::SHORTNAME), $output);
        $this->assertStringContainsString(get_string('noticerestorecount', 'local_sandbox', 0), $output);
        $this->assert_email_sent(get_string('emailsubjectwarning', 'local_sandbox'), $warning);
        $this->assertCount(0, $this->get_events(\core\event\course_restored::class));
        $this->assertCount(0, $this->get_events(\local_sandbox\event\course_restored::class));
        $this->assertCount(0, $this->get_events(\core\event\course_created::class));
        $this->assertCount(0, $this->get_events(\core\event\course_updated::class));
    }

    /**
     * Test that failing course backup files do not abort the processing of the remaining files.
     */
    public function test_failures_do_not_abort_run(): void {
        // The files are processed in alphabetical order, so the failing files are processed first.
        $this->getDataGenerator()->create_course(['shortname' => 'a-exception']);
        $this->add_backup_file('a-exception.mbz', $this->create_non_backup_archive());
        $this->getDataGenerator()->create_course(['shortname' => 'b-precheck']);
        $this->add_backup_file('b-precheck.mbz', $this->create_frontpage_backup());
        $this->create_sandbox_course();
        $this->add_backup_file(self::SHORTNAME . '.mbz', $this->get_sandbox_fixture_path());

        $output = $this->run_task();

        $this->assertStringContainsString(get_string('skippingrestorefailed', 'local_sandbox', 'a-exception'), $output);
        $this->assertStringContainsString(get_string('skippingprecheckfailed', 'local_sandbox', 'b-precheck'), $output);
        $this->assertStringContainsString(get_string('successrestored', 'local_sandbox', self::SHORTNAME), $output);
        $this->assertStringContainsString(get_string('noticerestorecount', 'local_sandbox', 1), $output);
        $this->assertEquals(['forum', 'label'], $this->get_activity_names(self::SHORTNAME));
        $this->assertCount(1, $this->get_events(\core\event\course_restored::class));
        $this->assertCount(1, $this->get_events(\local_sandbox\event\course_restored::class));
        $this->assertCount(1, $this->get_events(\core\event\course_created::class));
        $this->assertCount(0, $this->get_events(\core\event\course_updated::class));
    }

    /**
     * Data provider for test_email_threshold.
     *
     * @return array
     */
    public static function email_threshold_provider(): array {
        return [
            'Threshold notice' => [
                'notifylevel' => SANDBOX_LEVEL_NOTICE,
                'expectedsubjects' => ['emailsubjectwarning', 'emailsubjectnotice'],
            ],
            'Threshold warning' => [
                'notifylevel' => SANDBOX_LEVEL_WARNING,
                'expectedsubjects' => ['emailsubjectwarning'],
            ],
            // The task does not send any notifications with the error level.
            'Threshold error' => [
                'notifylevel' => SANDBOX_LEVEL_ERROR,
                'expectedsubjects' => [],
            ],
        ];
    }

    /**
     * Test that only notifications which reach the configured email threshold are sent.
     *
     * @dataProvider email_threshold_provider
     * @param int $notifylevel
     * @param string[] $expectedsubjects
     */
    public function test_email_threshold(int $notifylevel, array $expectedsubjects): void {
        set_config('notifylevel', $notifylevel, 'local_sandbox');

        // This results in a warning for the skipped file and a notice with the number of restored courses.
        $this->add_backup_file('nonexisting.mbz', $this->get_sandbox_fixture_path());

        $this->run_task();

        $subjects = [];
        foreach ($this->mailsink->get_messages() as $message) {
            $subjects[] = $message->subject;
        }
        $expectedsubjects = array_map(fn($identifier) => get_string($identifier, 'local_sandbox'), $expectedsubjects);
        $this->assertEquals($expectedsubjects, $subjects);
        $this->assertCount(0, $this->get_events(\core\event\course_restored::class));
        $this->assertCount(0, $this->get_events(\local_sandbox\event\course_restored::class));
        $this->assertCount(0, $this->get_events(\core\event\course_created::class));
        $this->assertCount(0, $this->get_events(\core\event\course_updated::class));
    }

    /**
     * Data provider for test_email_recipients.
     *
     * @return array
     */
    public static function email_recipients_provider(): array {
        return [
            'Nobody' => [
                'configuredusers' => [],
                'expectedrecipients' => [],
            ],
            'Main admin' => [
                'configuredusers' => ['admin'],
                'expectedrecipients' => ['admin'],
            ],
            'Second admin' => [
                'configuredusers' => ['secondadmin'],
                'expectedrecipients' => ['secondadmin'],
            ],
            'Both admins' => [
                'configuredusers' => ['admin', 'secondadmin'],
                'expectedrecipients' => ['admin', 'secondadmin'],
            ],
            // Users without the moodle/site:config capability are ignored.
            'Normal user' => [
                'configuredusers' => ['normaluser'],
                'expectedrecipients' => [],
            ],
        ];
    }

    /**
     * Test that the notifications are only sent to the configured recipients.
     *
     * @dataProvider email_recipients_provider
     * @param string[] $configuredusers
     * @param string[] $expectedrecipients
     */
    public function test_email_recipients(array $configuredusers, array $expectedrecipients): void {
        global $CFG;

        $users = [
            'admin' => get_admin(),
            'secondadmin' => $this->getDataGenerator()->create_user(['email' => 'secondadmin@example.com']),
            'normaluser' => $this->getDataGenerator()->create_user(['email' => 'normaluser@example.com']),
        ];
        set_config('siteadmins', $CFG->siteadmins . ',' . $users['secondadmin']->id);

        $userids = array_map(fn($user) => $users[$user]->id, $configuredusers);
        set_config('notifyonerrors', implode(',', $userids), 'local_sandbox');

        $this->run_task();

        // Without course backup files, there is exactly one notification per recipient.
        $recipients = [];
        foreach ($this->mailsink->get_messages() as $message) {
            $recipients[] = $message->to;
        }
        sort($recipients);
        $expectedemails = array_map(fn($user) => $users[$user]->email, $expectedrecipients);
        sort($expectedemails);
        $this->assertEquals($expectedemails, $recipients);
    }
}
