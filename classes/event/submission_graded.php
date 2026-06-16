<?php

namespace mod_groupassign\event;

defined('MOODLE_INTERNAL') || die();

class submission_graded extends \core\event\base {

    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'groupassign_grades';
    }

    public static function get_name() {
        return get_string('eventsubmissiongraded', 'groupassign');
    }

    public function get_description() {
        return "The user with id '$this->userid' graded group '{$this->other['groupid']}' for the group assignment " .
            "with course module id '$this->contextinstanceid'.";
    }

    public function get_url() {
        return new \moodle_url('/mod/groupassign/view.php', [
            'id' => $this->contextinstanceid,
            'action' => 'grade',
            'groupid' => $this->other['groupid'],
        ]);
    }

    public static function get_objectid_mapping() {
        return ['db' => 'groupassign_grades', 'restore' => 'grade'];
    }

    public static function get_other_mapping() {
        return ['groupid' => ['db' => 'groups', 'restore' => 'group']];
    }
}
