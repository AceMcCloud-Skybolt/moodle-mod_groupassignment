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
 * Backup groupassign stepslib for mod_groupassign.
 *
 * @package    mod_groupassign
 * @copyright  2026 Matthew Darch <matthew.darch.1up@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Backup groupassign activity structure step.
 */
class backup_groupassign_activity_structure_step extends backup_activity_structure_step {
    /**
     * Define structure.
     * @return mixed
     */
    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');
        $groupinfo = $this->get_setting_value('groups');

        $groupassign = new backup_nested_element('groupassign', ['id'], [
            'name',
            'intro',
            'introformat',
            'activity',
            'activityformat',
            'grade',
            'allowsubmissionsfromdate',
            'duedate',
            'cutoffdate',
            'gradingduedate',
            'submissiononlinetext',
            'submissionfile',
            'maxfiles',
            'maxbytes',
            'submissionfiletypes',
            'wordlimit',
            'requiresubmissionstatement',
            'formationmode',
            'groupingid',
            'numgroups',
            'groupnameprefix',
            'groupnamesuffix',
            'minmembers',
            'maxmembers',
            'allowstudentjoin',
            'allowstudentleave',
            'allowstudentcreate',
            'allowstudentrename',
            'allowstudentdescription',
            'hidefullgroups',
            'showmembers',
            'selectionopen',
            'selectionclose',
            'peerenabled',
            'peerselfassessment',
            'peercomments',
            'peerrequirejustification',
            'timemodified',
        ]);

        $groups = new backup_nested_element('groups');
        $group = new backup_nested_element('group', ['id'], [
            'groupid',
            'sortorder',
            'timecreated',
            'timemodified',
        ]);

        $criteria = new backup_nested_element('criteria');
        $criterion = new backup_nested_element('criterion', ['id'], [
            'description',
            'descriptionformat',
            'details',
            'ratingtype',
            'sortorder',
            'archived',
            'timecreated',
            'timemodified',
        ]);

        $submissions = new backup_nested_element('submissions');
        $submission = new backup_nested_element('submission', ['id'], [
            'groupid',
            'userid',
            'submissiontext',
            'submissionformat',
            'status',
            'timecreated',
            'timemodified',
            'timesubmitted',
        ]);

        $grades = new backup_nested_element('grades');
        $grade = new backup_nested_element('grade', ['id'], [
            'groupid',
            'graderid',
            'grade',
            'feedback',
            'feedbackformat',
            'timecreated',
            'timemodified',
        ]);

        $membergrades = new backup_nested_element('membergrades');
        $membergrade = new backup_nested_element('membergrade', ['id'], [
            'groupid',
            'userid',
            'graderid',
            'grade',
            'feedback',
            'feedbackformat',
            'timecreated',
            'timemodified',
        ]);

        $peerreviews = new backup_nested_element('peerreviews');
        $peerreview = new backup_nested_element('peerreview', ['id'], [
            'groupid',
            'criteriaid',
            'reviewerid',
            'revieweeid',
            'rating',
            'comment',
            'commentformat',
            'timecreated',
            'timemodified',
        ]);

        $groupassign->add_child($groups);
        $groups->add_child($group);
        $groupassign->add_child($criteria);
        $criteria->add_child($criterion);
        $groupassign->add_child($submissions);
        $submissions->add_child($submission);
        $groupassign->add_child($grades);
        $grades->add_child($grade);
        $groupassign->add_child($membergrades);
        $membergrades->add_child($membergrade);
        $groupassign->add_child($peerreviews);
        $peerreviews->add_child($peerreview);

        $groupassign->set_source_table('groupassign', ['id' => backup::VAR_ACTIVITYID]);
        $criterion->set_source_table('groupassign_peercriteria', ['groupassignid' => backup::VAR_PARENTID], 'sortorder ASC');

        if ($groupinfo) {
            $group->set_source_table('groupassign_groups', ['groupassignid' => backup::VAR_PARENTID], 'sortorder ASC');
        }

        if ($userinfo && $groupinfo) {
            $submission->set_source_table('groupassign_submissions', ['groupassignid' => backup::VAR_PARENTID]);
            $grade->set_source_table('groupassign_grades', ['groupassignid' => backup::VAR_PARENTID]);
            $membergrade->set_source_table('groupassign_membergrades', ['groupassignid' => backup::VAR_PARENTID]);
            $peerreview->set_source_table('groupassign_peerreviews', ['groupassignid' => backup::VAR_PARENTID]);
        }

        $groupassign->annotate_ids('grouping', 'groupingid');
        $groupassign->annotate_ids('scale', 'grade');
        $group->annotate_ids('group', 'groupid');
        $submission->annotate_ids('group', 'groupid');
        $submission->annotate_ids('user', 'userid');
        $grade->annotate_ids('group', 'groupid');
        $grade->annotate_ids('user', 'graderid');
        $membergrade->annotate_ids('group', 'groupid');
        $membergrade->annotate_ids('user', 'userid');
        $membergrade->annotate_ids('user', 'graderid');
        $peerreview->annotate_ids('group', 'groupid');
        $peerreview->annotate_ids('user', 'reviewerid');
        $peerreview->annotate_ids('user', 'revieweeid');

        $groupassign->annotate_files('mod_groupassign', 'introattachment', null);
        $groupassign->annotate_files('mod_groupassign', 'activityattachment', null);
        $submission->annotate_files('mod_groupassign', 'submission', 'id');

        return $this->prepare_activity_structure($groupassign);
    }
}
