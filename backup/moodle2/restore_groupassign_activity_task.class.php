<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/groupassign/backup/moodle2/restore_groupassign_stepslib.php');

class restore_groupassign_activity_task extends restore_activity_task {

    protected function define_my_settings() {
    }

    protected function define_my_steps() {
        $this->add_step(new restore_groupassign_activity_structure_step('groupassign_structure', 'groupassign.xml'));
    }

    public static function define_decode_contents() {
        return [
            new restore_decode_content('groupassign', ['intro', 'activity'], 'groupassign'),
        ];
    }

    public static function define_decode_rules() {
        return [
            new restore_decode_rule('GROUPASSIGNVIEWBYID', '/mod/groupassign/view.php?id=$1', 'course_module'),
            new restore_decode_rule('GROUPASSIGNINDEX', '/mod/groupassign/index.php?id=$1', 'course'),
        ];
    }

    public static function define_restore_log_rules() {
        return [
            new restore_log_rule('groupassign', 'view', 'view.php?id={course_module}', '{groupassign}'),
        ];
    }

    public static function define_restore_log_rules_for_course() {
        return [
            new restore_log_rule('groupassign', 'view all', 'index.php?id={course}', null),
        ];
    }
}
