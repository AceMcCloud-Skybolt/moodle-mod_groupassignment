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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Tests for the group assignment workflow manager.
 *
 * @package    mod_groupassign
 * @category   test
 * @copyright  2026 Matthew Darch <matthew.darch.1up@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_groupassign\local;

/**
 * Workflow manager tests.
 *
 */
final class workflow_manager_test extends \advanced_testcase {
    /**
     * Selection and submission windows honour their configured boundaries.
     */
    public function test_availability_windows(): void {
        $now = time();

        $this->assertTrue(workflow_manager::selection_open((object)[
            'selectionopen' => $now - 60,
            'selectionclose' => $now + 60,
        ]));
        $this->assertFalse(workflow_manager::selection_open((object)[
            'selectionopen' => $now + 60,
            'selectionclose' => 0,
        ]));
        $this->assertFalse(workflow_manager::selection_open((object)[
            'selectionopen' => 0,
            'selectionclose' => $now - 60,
        ]));

        $this->assertTrue(workflow_manager::submission_open((object)[
            'allowsubmissionsfromdate' => $now - 60,
            'cutoffdate' => $now + 60,
        ]));
        $this->assertFalse(workflow_manager::submission_open((object)[
            'allowsubmissionsfromdate' => $now + 60,
            'cutoffdate' => 0,
        ]));
        $this->assertFalse(workflow_manager::submission_open((object)[
            'allowsubmissionsfromdate' => 0,
            'cutoffdate' => $now - 60,
        ]));
    }

    /**
     * Point, scale, and no-grade values are normalised for persistence.
     */
    public function test_submitted_grade_value(): void {
        $this->assertSame(84.5, workflow_manager::submitted_grade_value('84.5', (object)['grade' => 100]));
        $this->assertSame(3.0, workflow_manager::submitted_grade_value('3', (object)['grade' => -7]));
        $this->assertNull(workflow_manager::submitted_grade_value('0', (object)['grade' => -7]));
        $this->assertNull(workflow_manager::submitted_grade_value('', (object)['grade' => 100]));
        $this->assertNull(workflow_manager::submitted_grade_value('50', (object)['grade' => 0]));
    }

    /**
     * Managed formation creates core groups and tracks them against the activity.
     */
    public function test_generator_creates_managed_groups(): void {
        global $DB;

        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_groupassign');
        $groupassign = $generator->create_instance([
            'course' => $course->id,
            'formationmode' => GROUPASSIGN_FORMATION_SELFSELECT,
            'numgroups' => 2,
            'groupnameprefix' => 'Project team',
        ]);

        $this->assertGreaterThan(0, (int)$groupassign->groupingid);
        $trackedgroups = $DB->get_records('groupassign_groups', ['groupassignid' => $groupassign->id]);
        $this->assertCount(2, $trackedgroups);
        foreach ($trackedgroups as $trackedgroup) {
            $this->assertTrue($DB->record_exists('groups', ['id' => $trackedgroup->groupid]));
        }
    }
}
