<?php
// This file is part of Moodle - https://moodle.org/
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
 * Restore groupassign activity task.class for mod_groupassign.
 *
 * @package    mod_groupassign
 * @copyright  2026 Matthew Darch <matthew.darch.1up@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/groupassign/backup/moodle2/restore_groupassign_stepslib.php');

/**
 * Restore groupassign activity task.
 */
class restore_groupassign_activity_task extends restore_activity_task {
    /**
     * Define my settings.
     * @return mixed
     */
    protected function define_my_settings() {
    }

    /**
     * Define my steps.
     * @return mixed
     */
    protected function define_my_steps() {
        $this->add_step(new restore_groupassign_activity_structure_step('groupassign_structure', 'groupassign.xml'));
    }

    /**
     * Define decode contents.
     * @return mixed
     */
    public static function define_decode_contents() {
        return [
            new restore_decode_content('groupassign', ['intro', 'activity'], 'groupassign'),
        ];
    }

    /**
     * Define decode rules.
     * @return mixed
     */
    public static function define_decode_rules() {
        return [
            new restore_decode_rule('GROUPASSIGNVIEWBYID', '/mod/groupassign/view.php?id=$1', 'course_module'),
            new restore_decode_rule('GROUPASSIGNINDEX', '/mod/groupassign/index.php?id=$1', 'course'),
        ];
    }

    /**
     * Define restore log rules.
     * @return mixed
     */
    public static function define_restore_log_rules() {
        return [
            new restore_log_rule('groupassign', 'view', 'view.php?id={course_module}', '{groupassign}'),
        ];
    }

    /**
     * Define restore log rules for course.
     * @return mixed
     */
    public static function define_restore_log_rules_for_course() {
        return [
            new restore_log_rule('groupassign', 'view all', 'index.php?id={course}', null),
        ];
    }
}
