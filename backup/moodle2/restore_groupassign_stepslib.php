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
 * Restore groupassign stepslib for mod_groupassign.
 *
 * @package    mod_groupassign
 * @copyright  2026 Matthew Darch <matthew.darch.1up@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Restore groupassign activity structure step.
 */
class restore_groupassign_activity_structure_step extends restore_activity_structure_step {
    /**
     * Define structure.
     * @return mixed
     */
    protected function define_structure() {
        $paths = [
            new restore_path_element('groupassign', '/activity/groupassign'),
            new restore_path_element('groupassign_group', '/activity/groupassign/groups/group'),
            new restore_path_element('groupassign_criterion', '/activity/groupassign/criteria/criterion'),
        ];

        if ($this->get_setting_value('userinfo')) {
            $paths[] = new restore_path_element(
                'groupassign_submission',
                '/activity/groupassign/submissions/submission'
            );
            $paths[] = new restore_path_element('groupassign_grade', '/activity/groupassign/grades/grade');
            $paths[] = new restore_path_element(
                'groupassign_membergrade',
                '/activity/groupassign/membergrades/membergrade'
            );
            $paths[] = new restore_path_element(
                'groupassign_peerreview',
                '/activity/groupassign/peerreviews/peerreview'
            );
        }

        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process groupassign.
     *
     * @param mixed $data
     * @return mixed
     */
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

        foreach ($this->legacy_groupassign_fields() as $fieldname) {
            if (property_exists($data, $fieldname)) {
                unset($data->{$fieldname});
            }
        }

        $newitemid = $DB->insert_record('groupassign', $data);
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Legacy groupassign fields.
     *
     * @return array
     */
    private function legacy_groupassign_fields(): array {
        return [
            'timelimit',
            'alwaysshowdescription',
            'submissionattachments',
            'submissiondrafts',
            'maxattempts',
            'attemptreopenmethod',
            'managedgrouping',
            'peeranonymous',
            'peerstudentresponse',
            'sendnotifications',
            'sendlatenotifications',
            'sendstudentnotifications',
            'blindmarking',
            'hidegrader',
            'markingworkflow',
            'markingallocation',
            'markinganonymous',
            'completionreceivegrade',
        ];
    }

    /**
     * Process groupassign group.
     *
     * @param mixed $data
     * @return mixed
     */
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

    /**
     * Process groupassign criterion.
     *
     * @param mixed $data
     * @return mixed
     */
    protected function process_groupassign_criterion($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->groupassignid = $this->get_new_parentid('groupassign');

        $newitemid = $DB->insert_record('groupassign_peercriteria', $data);
        $this->set_mapping('groupassign_criterion', $oldid, $newitemid);
    }

    /**
     * Process groupassign submission.
     *
     * @param mixed $data
     * @return mixed
     */
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

    /**
     * Process groupassign grade.
     *
     * @param mixed $data
     * @return mixed
     */
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

    /**
     * Process groupassign membergrade.
     *
     * @param mixed $data
     * @return mixed
     */
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

    /**
     * Process groupassign peerreview.
     *
     * @param mixed $data
     * @return mixed
     */
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

    /**
     * After execute.
     * @return mixed
     */
    protected function after_execute() {
        $this->add_related_files('mod_groupassign', 'introattachment', null);
        $this->add_related_files('mod_groupassign', 'activityattachment', null);
        $this->add_related_files('mod_groupassign', 'submission', 'groupassign_submission');
    }
}
