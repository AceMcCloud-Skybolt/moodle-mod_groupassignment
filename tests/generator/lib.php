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
 * Test data generator for mod_groupassign.
 *
 * @package    mod_groupassign
 * @category   test
 * @copyright  2026 Matthew Darch <matthew.darch.1up@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Group assignment test generator.
 */
class mod_groupassign_generator extends testing_module_generator {
    /**
     * Create a group assignment instance.
     *
     * @param array|stdClass|null $record
     * @param array|null $options
     * @return stdClass
     */
    public function create_instance($record = null, ?array $options = null): stdClass {
        global $CFG;

        require_once($CFG->dirroot . '/mod/groupassign/lib.php');
        $record = (object)(array)$record;
        $defaults = [
            'name' => 'Test group assignment',
            'intro' => '',
            'introformat' => FORMAT_HTML,
            'activity' => '',
            'activityformat' => FORMAT_HTML,
            'grade' => 100,
            'submissiononlinetext' => 1,
            'submissionfile' => 1,
            'submissionfiletypes' => '',
            'groupnameprefix' => 'Test group',
            'groupnamesuffix' => GROUPASSIGN_SUFFIX_NUMBERS,
            'formationmode' => GROUPASSIGN_FORMATION_SELFSELECT,
            'numgroups' => 0,
            'minmembers' => 0,
            'maxmembers' => 4,
            'allowstudentjoin' => 1,
            'allowstudentleave' => 1,
            'allowstudentcreate' => 0,
            'allowstudentrename' => 0,
            'allowstudentdescription' => 1,
            'hidefullgroups' => 0,
            'showmembers' => 1,
            'peerenabled' => 0,
            'peerselfassessment' => 1,
            'peercomments' => 1,
            'peerrequirejustification' => 0,
        ];

        foreach ($defaults as $field => $value) {
            if (!property_exists($record, $field)) {
                $record->{$field} = $value;
            }
        }

        return parent::create_instance($record, $options ?? []);
    }
}
