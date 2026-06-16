<?php

defined('MOODLE_INTERNAL') || die();

class backup_groupassign_activity_structure_step extends backup_activity_structure_step {

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
            $submission->set_source_table('groupassign_submissions', ['groupassignid' => '../../id']);
            $grade->set_source_table('groupassign_grades', ['groupassignid' => '../../id']);
            $membergrade->set_source_table('groupassign_membergrades', ['groupassignid' => '../../id']);
            $peerreview->set_source_table('groupassign_peerreviews', ['groupassignid' => '../../id']);
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
