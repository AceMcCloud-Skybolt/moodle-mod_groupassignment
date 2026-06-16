<?php

namespace mod_groupassign\event;

defined('MOODLE_INTERNAL') || die();

class group_created extends \core\event\base {

    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'groups';
    }

    public static function get_name() {
        return get_string('eventgroupcreated', 'groupassign');
    }

    public function get_description() {
        return "The user with id '$this->userid' created group '$this->objectid' for the group assignment with " .
            "course module id '$this->contextinstanceid'.";
    }

    public function get_url() {
        return new \moodle_url('/mod/groupassign/view.php', ['id' => $this->contextinstanceid]);
    }

    public static function get_objectid_mapping() {
        return ['db' => 'groups', 'restore' => 'group'];
    }

    public static function get_other_mapping() {
        return ['groupassignid' => ['db' => 'groupassign', 'restore' => 'groupassign']];
    }
}
