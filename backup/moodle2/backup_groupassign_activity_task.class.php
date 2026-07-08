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
 * Backup groupassign activity task.class for mod_groupassign.
 *
 * @package    mod_groupassign
 * @copyright  2026 Matthew Darch <matthew.darch.1up@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/groupassign/backup/moodle2/backup_groupassign_stepslib.php');

/**
 * Backup groupassign activity task.
 */
class backup_groupassign_activity_task extends backup_activity_task {
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
        $this->add_step(new backup_groupassign_activity_structure_step('groupassign_structure', 'groupassign.xml'));
    }

    /**
     * Encode content links.
     *
     * @param mixed $content
     * @return mixed
     */
    public static function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, '/');

        $search = '/(' . $base . '\/mod\/groupassign\/index.php\?id\=)([0-9]+)/';
        $content = preg_replace($search, '$@GROUPASSIGNINDEX*$2@$', $content);

        $search = '/(' . $base . '\/mod\/groupassign\/view.php\?id\=)([0-9]+)/';
        $content = preg_replace($search, '$@GROUPASSIGNVIEWBYID*$2@$', $content);

        return $content;
    }
}
