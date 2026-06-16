<?php

defined('MOODLE_INTERNAL') || die();

class restore_groupassign_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {
        $paths = [
            new restore_path_element('groupassign', '/activity/groupassign'),
            new restore_path_element('groupassign_group', '/activity/groupassign/groups/group'),
            new restore_path_element('groupassign_criterion', '/activity/groupassign/criteria/criterion'),
        ];

        if ($this->get_setting_value('userinfo')) {
            $paths[] = new restore_path_element('groupassign_submission',
                '/activity/groupassign/submissions/submission');
            $paths[] = new restore_path_element('groupassign_grade', '/activity/groupassign/grades/grade');
            $paths[] = new restore_path_element('groupassign_membergrade',
                '/activity/groupassign/membergrades/membergrade');
            $paths[] = new restore_path_element('groupassign_peerreview',
                '/activity/groupassign/peerreviews/peerreview');
        }

        return $this->prepare_activity_structure($paths);
    }

    protected function process_groupassign($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->allowsubmissionsfromdate = $this->apply_date_offset($data->allowsubmissionsfromdate ?? 0);
        $data->duedate = $this->apply_date_offset($data->duedate ?? 0);
        $data->cutoffdate = $this->apply_date_offset($data->cutoffdate ?? 0);
        $data->gradingduedate = $this->apply_date_offset($data->gradingduedate ?? 0);
        $data->selectionopen = $this->apply_date_offset($data->selectionopen ?? 0);
        $data->selectionclose = $this->apply_date_offset($data->selectionclose ?? 0);

        if (!empty($data->groupingid)) {
            $data->groupingid = $this->get_mappingid('grouping', $data->groupingid) ?: 0;
        } else {
            $data->groupingid = 0;
        }

        if (!empty($data->grade) && $data->grade < 0) {
            $scaleid = $this->get_mappingid('scale', abs($data->grade));
            $data->grade = $scaleid ? -$scaleid : 0;
        }

        $newitemid = $DB->insert_record('groupassign', $data);
        $this->apply_activity_instance($newitemid);
    }

    protected function process_groupassign_group($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $groupid = $this->get_mappingid('group', $data->groupid);
        if (!$groupid) {
            return;
        }

        $record = (object)[
            'groupassignid' => $this->get_new_parentid('groupassign'),
            'groupid' => $groupid,
            'sortorder' => $data->sortorder ?? 0,
            'timecreated' => $data->timecreated ?? time(),
            'timemodified' => $data->timemodified ?? time(),
        ];

        $newitemid = $DB->insert_record('groupassign_groups', $record);
        $this->set_mapping('groupassign_group', $oldid, $newitemid);
    }

    protected function process_groupassign_criterion($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->groupassignid = $this->get_new_parentid('groupassign');

        $newitemid = $DB->insert_record('groupassign_peercriteria', $data);
        $this->set_mapping('groupassign_criterion', $oldid, $newitemid);
    }

    protected function process_groupassign_submission($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->groupassignid = $this->get_new_parentid('groupassign');
        $data->groupid = $this->get_mappingid('group', $data->groupid);
        $data->userid = $this->get_mappingid('user', $data->userid);
        if (!$data->groupid || !$data->userid) {
            return;
        }

        $newitemid = $DB->insert_record('groupassign_submissions', $data);
        $this->set_mapping('groupassign_submission', $oldid, $newitemid, true);
    }

    protected function process_groupassign_grade($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->groupassignid = $this->get_new_parentid('groupassign');
        $data->groupid = $this->get_mappingid('group', $data->groupid);
        $data->graderid = $this->get_mappingid('user', $data->graderid);
        if (!$data->groupid || !$data->graderid) {
            return;
        }

        $newitemid = $DB->insert_record('groupassign_grades', $data);
        $this->set_mapping('groupassign_grade', $oldid, $newitemid);
    }

    protected function process_groupassign_membergrade($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->groupassignid = $this->get_new_parentid('groupassign');
        $data->groupid = $this->get_mappingid('group', $data->groupid);
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->graderid = $this->get_mappingid('user', $data->graderid);
        if (!$data->groupid || !$data->userid || !$data->graderid) {
            return;
        }

        $newitemid = $DB->insert_record('groupassign_membergrades', $data);
        $this->set_mapping('groupassign_membergrade', $oldid, $newitemid);
    }

    protected function process_groupassign_peerreview($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->groupassignid = $this->get_new_parentid('groupassign');
        $data->groupid = $this->get_mappingid('group', $data->groupid);
        $data->criteriaid = $this->get_mappingid('groupassign_criterion', $data->criteriaid);
        $data->reviewerid = $this->get_mappingid('user', $data->reviewerid);
        $data->revieweeid = $this->get_mappingid('user', $data->revieweeid);
        if (!$data->groupid || !$data->criteriaid || !$data->reviewerid || !$data->revieweeid) {
            return;
        }

        $newitemid = $DB->insert_record('groupassign_peerreviews', $data);
        $this->set_mapping('groupassign_peerreview', $oldid, $newitemid);
    }

    protected function after_execute() {
        $this->add_related_files('mod_groupassign', 'introattachment', null);
        $this->add_related_files('mod_groupassign', 'activityattachment', null);
        $this->add_related_files('mod_groupassign', 'submission', 'groupassign_submission');
    }
}
