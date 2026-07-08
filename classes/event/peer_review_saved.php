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
 * Peer review saved for mod_groupassign.
 *
 * @package    mod_groupassign
 * @copyright  2026 Matthew Darch <matthew.darch.1up@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_groupassign\event;

/**
 * Peer review saved.
 */
class peer_review_saved extends \core\event\base {
    /**
     * Init.
     * @return mixed
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'groupassign';
    }

    /**
     * Get name.
     * @return mixed
     */
    public static function get_name() {
        return get_string('eventpeerreviewsaved', 'groupassign');
    }

    /**
     * Get description.
     * @return mixed
     */
    public function get_description() {
        return "The user with id '$this->userid' saved peer review ratings for group '{$this->other['groupid']}' " .
            "in the group assignment with course module id '$this->contextinstanceid'.";
    }

    /**
     * Get url.
     * @return mixed
     */
    public function get_url() {
        return new \moodle_url('/mod/groupassign/view.php', ['id' => $this->contextinstanceid]);
    }

    /**
     * Get objectid mapping.
     * @return mixed
     */
    public static function get_objectid_mapping() {
        return ['db' => 'groupassign', 'restore' => 'groupassign'];
    }

    /**
     * Get other mapping.
     * @return mixed
     */
    public static function get_other_mapping() {
        return ['groupid' => ['db' => 'groups', 'restore' => 'group']];
    }
}
