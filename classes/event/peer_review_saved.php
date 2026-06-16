<?php

namespace mod_groupassign\event;

defined('MOODLE_INTERNAL') || die();

class peer_review_saved extends \core\event\base {

    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'groupassign';
    }

    public static function get_name() {
        return get_string('eventpeerreviewsaved', 'groupassign');
    }

    public function get_description() {
        return "The user with id '$this->userid' saved peer review ratings for group '{$this->other['groupid']}' " .
            "in the group assignment with course module id '$this->contextinstanceid'.";
    }

    public function get_url() {
        return new \moodle_url('/mod/groupassign/view.php', ['id' => $this->contextinstanceid]);
    }

    public static function get_objectid_mapping() {
        return ['db' => 'groupassign', 'restore' => 'groupassign'];
    }

    public static function get_other_mapping() {
        return ['groupid' => ['db' => 'groups', 'restore' => 'group']];
    }
}
