<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/groupassign/backup/moodle2/backup_groupassign_stepslib.php');

class backup_groupassign_activity_task extends backup_activity_task {

    protected function define_my_settings() {
    }

    protected function define_my_steps() {
        $this->add_step(new backup_groupassign_activity_structure_step('groupassign_structure', 'groupassign.xml'));
    }

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
