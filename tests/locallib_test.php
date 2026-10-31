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

namespace local_sandbox;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/sandbox/locallib.php');

/**
 * Local plugin "sandbox" - Tests for the local library
 *
 * @package    local_sandbox
 * @copyright  2026 Alexander Bias <bias@alexanderbias.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     ::local_sandbox_getfiles
 */
final class locallib_test extends \advanced_testcase {
    /**
     * Data provider for test_getfiles.
     *
     * @return array
     */
    public static function getfiles_provider(): array {
        return [
            'Lowercase extension' => [
                'filename' => 'course1.mbz',
                'expectedshortname' => 'course1',
            ],
            'Uppercase extension' => [
                'filename' => 'COURSE1.MBZ',
                'expectedshortname' => 'COURSE1',
            ],
            'Mixed case extension' => [
                'filename' => 'Course1.Mbz',
                'expectedshortname' => 'Course1',
            ],
            'Shortname with dots' => [
                'filename' => 'course.2026.mbz',
                'expectedshortname' => 'course.2026',
            ],
            'Extension in the middle of the filename' => [
                'filename' => 'course1.mbz.txt',
                'expectedshortname' => null,
            ],
            'Other extension' => [
                'filename' => 'course1.zip',
                'expectedshortname' => null,
            ],
            'No extension' => [
                'filename' => 'course1mbz',
                'expectedshortname' => null,
            ],
        ];
    }

    /**
     * Test that only course backup files are returned and that the shortname is extracted correctly.
     *
     * @dataProvider getfiles_provider
     * @param string $filename The filename of the file in the course backups filearea.
     * @param string|null $expectedshortname The expected shortname or null if the file is not a course backup file.
     */
    public function test_getfiles(string $filename, ?string $expectedshortname): void {
        $this->resetAfterTest();

        get_file_storage()->create_file_from_string([
            'contextid' => \context_system::instance()->id,
            'component' => 'local_sandbox',
            'filearea' => 'coursebackups',
            'itemid' => 0,
            'filepath' => '/',
            'filename' => $filename,
        ], 'content');

        $files = local_sandbox_getfiles();

        if ($expectedshortname === null) {
            $this->assertSame([], $files);
        } else {
            $this->assertSame([$expectedshortname], array_keys($files));
            $this->assertSame($filename, $files[$expectedshortname]->get_filename());
        }
    }
}
