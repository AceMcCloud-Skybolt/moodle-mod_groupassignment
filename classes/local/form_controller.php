<?php
// This file is part of Moodle - http://moodle.org/
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
 * Form preparation and request processing for group assignments.
 *
 * @package    mod_groupassign
 * @copyright  2026 Matthew Darch <matthew.darch.1up@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_groupassign\local;

/**
 * Form preparation and request processing for group assignments.
 */
class form_controller {
    /**
     * Prepare grade form.
     *
     * @param mixed $groupassign
     * @param mixed $cm
     * @param mixed $context
     * @param int $groupid
     * @param array $editoroptions
     * @return array|null
     */
    public static function prepare_grade_form($groupassign, $cm, $context, int $groupid, array $editoroptions): ?array {
        $groups = \mod_groupassign\local\workflow_manager::get_groups($groupassign);
        if (empty($groups[$groupid])) {
            return null;
        }

        $group = $groups[$groupid];
        $members = groups_get_members($groupid, 'u.*', 'u.lastname, u.firstname');
        $submission = \mod_groupassign\local\workflow_manager::get_submission($groupassign, $groupid);
        $grade = \mod_groupassign\local\workflow_manager::get_grade($groupassign, $groupid);
        $gradeurl = new \moodle_url('/mod/groupassign/view.php', [
            'id' => $cm->id,
            'action' => 'grade',
            'groupid' => $groupid,
        ]);
        $mform = new \mod_groupassign\form\grade_form($gradeurl, [
            'groupassign' => $groupassign,
            'editoroptions' => $editoroptions,
            'members' => $members,
        ]);

        $defaults = [
            'groupid' => $groupid,
            'grade' => $grade->grade ?? '',
            'feedbackeditor' => [
                'text' => $grade->feedback ?? '',
                'format' => $grade->feedbackformat ?? FORMAT_HTML,
            ],
        ];
        foreach ($members as $member) {
            if ($membergrade = groupassign_get_membergrade($groupassign->id, $member->id)) {
                $defaults['membergrade_' . $member->id] = $membergrade->grade;
                $defaults['memberfeedback_' . $member->id] = $membergrade->feedback;
            }
        }
        $mform->set_data($defaults);

        return [
            'mform' => $mform,
            'group' => $group,
            'members' => $members,
            'submission' => $submission,
            'grade' => $grade,
            'gradeurl' => $gradeurl,
        ];
    }

    /**
     * Process grade form.
     *
     * @param mixed $groupassign
     * @param mixed $cm
     * @param mixed $context
     * @param int $groupid
     * @param array $editoroptions
     * @return array|null
     */
    public static function process_grade_form($groupassign, $cm, $context, int $groupid, array $editoroptions): ?array {
        global $DB, $USER;

        require_capability('mod/groupassign:grade', $context);
        $prepared = self::prepare_grade_form($groupassign, $cm, $context, $groupid, $editoroptions);
        if ($prepared === null) {
            return null;
        }

        $mform = $prepared['mform'];
        $members = $prepared['members'];
        $grade = $prepared['grade'];
        $gradeurl = $prepared['gradeurl'];

        if ($mform->is_cancelled()) {
            redirect(new \moodle_url('/mod/groupassign/view.php', ['id' => $cm->id, 'action' => 'submissions']));
        } else if ($data = $mform->get_data()) {
            $now = time();
            $record = (object)[
                'groupassignid' => $groupassign->id,
                'groupid' => $groupid,
                'graderid' => $USER->id,
                'grade' => \mod_groupassign\local\workflow_manager::submitted_grade_value($data->grade ?? null, $groupassign),
                'feedback' => $data->feedbackeditor['text'],
                'feedbackformat' => $data->feedbackeditor['format'],
                'timemodified' => $now,
            ];
            if ($grade) {
                $record->id = $grade->id;
                $DB->update_record('groupassign_grades', $record);
                $gradeid = $grade->id;
            } else {
                $record->timecreated = $now;
                $gradeid = $DB->insert_record('groupassign_grades', $record);
            }

            if ((int)$groupassign->grade === 0) {
                groupassign_update_grades($groupassign);
                \mod_groupassign\event\submission_graded::create([
                    'context' => $context,
                    'objectid' => $gradeid,
                    'other' => ['groupid' => $groupid],
                ])->trigger();
                redirect(
                    $gradeurl,
                    get_string('gradesaved', 'groupassign'),
                    null,
                    \core\output\notification::NOTIFY_SUCCESS
                );
            }

            foreach ($members as $member) {
                $gradefield = 'membergrade_' . $member->id;
                $feedbackfield = 'memberfeedback_' . $member->id;
                $membergradevalue = isset($data->{$gradefield}) ? trim((string)$data->{$gradefield}) : '';
                $memberfeedback = isset($data->{$feedbackfield}) ? trim((string)$data->{$feedbackfield}) : '';
                $submittedmembergrade = \mod_groupassign\local\workflow_manager::submitted_grade_value(
                    $membergradevalue,
                    $groupassign
                );
                $existing = groupassign_get_membergrade($groupassign->id, $member->id);

                if ($submittedmembergrade === null && $memberfeedback === '') {
                    if ($existing) {
                        $DB->delete_records('groupassign_membergrades', ['id' => $existing->id]);
                    }
                    continue;
                }

                $memberrecord = (object)[
                    'groupassignid' => $groupassign->id,
                    'groupid' => $groupid,
                    'userid' => $member->id,
                    'graderid' => $USER->id,
                    'grade' => $submittedmembergrade,
                    'feedback' => $memberfeedback,
                    'feedbackformat' => FORMAT_PLAIN,
                    'timemodified' => $now,
                ];
                if ($existing) {
                    $memberrecord->id = $existing->id;
                    $DB->update_record('groupassign_membergrades', $memberrecord);
                } else {
                    $memberrecord->timecreated = $now;
                    $DB->insert_record('groupassign_membergrades', $memberrecord);
                }
            }

            groupassign_update_grades($groupassign);
            \mod_groupassign\event\submission_graded::create([
                'context' => $context,
                'objectid' => $gradeid,
                'other' => ['groupid' => $groupid],
            ])->trigger();
            redirect(
                $gradeurl,
                get_string('gradesaved', 'groupassign'),
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );
        }

        return $prepared;
    }

    /**
     * Prepare peer review form.
     *
     * @param mixed $groupassign
     * @param mixed $cm
     * @param mixed $context
     * @return array|null
     */
    public static function prepare_peer_review_form($groupassign, $cm, $context): ?array {
        global $DB, $USER;

        if (empty($groupassign->peerenabled)) {
            return null;
        }

        $mygroups = \mod_groupassign\local\workflow_manager::get_my_groups($groupassign, $USER->id);
        if (!$mygroups) {
            return null;
        }

        $group = reset($mygroups);
        $criteria = \mod_groupassign\local\peer_review_manager::get_peercriteria($groupassign);
        $members = \mod_groupassign\local\peer_review_manager::get_reviewable_members($groupassign, $group->id, $USER->id);
        if (!$criteria || !$members) {
            return null;
        }

        $url = new \moodle_url('/mod/groupassign/view.php', ['id' => $cm->id, 'action' => 'peerreview']);
        $mform = new \mod_groupassign\form\peer_review_form($url, [
            'groupassign' => $groupassign,
            'criteria' => $criteria,
            'members' => $members,
            'ratingsbycriteria' => array_reduce($criteria, static function ($carry, $criterion) {
                $carry[$criterion->id] = \mod_groupassign\local\peer_review_manager::peer_rating_options(
                    $criterion->ratingtype ?? 'fourlevel'
                );
                return $carry;
            }, []),
        ]);

        $existing = $DB->get_records('groupassign_peerreviews', [
            'groupassignid' => $groupassign->id,
            'groupid' => $group->id,
            'reviewerid' => $USER->id,
        ]);
        $defaults = ['groupid' => $group->id];
        foreach ($existing as $review) {
            $defaults['rating_' . $review->criteriaid . '_' . $review->revieweeid] = $review->rating;
            $defaults['comment_' . $review->criteriaid . '_' . $review->revieweeid] = $review->comment;
        }
        $mform->set_data($defaults);

        return [
            'mform' => $mform,
            'group' => $group,
            'criteria' => $criteria,
            'members' => $members,
        ];
    }

    /**
     * Process peer review form.
     *
     * @param mixed $groupassign
     * @param mixed $cm
     * @param mixed $context
     * @return array|null
     */
    public static function process_peer_review_form($groupassign, $cm, $context): ?array {
        global $DB, $USER;

        require_capability('mod/groupassign:join', $context);
        $prepared = self::prepare_peer_review_form($groupassign, $cm, $context);
        if ($prepared === null) {
            return null;
        }

        $mform = $prepared['mform'];
        $group = $prepared['group'];
        $criteria = $prepared['criteria'];
        $members = $prepared['members'];

        if ($mform->is_cancelled()) {
            redirect(new \moodle_url('/mod/groupassign/view.php', ['id' => $cm->id]));
        } else if ($data = $mform->get_data()) {
            $now = time();
            foreach ($criteria as $criterion) {
                foreach ($members as $member) {
                    $ratingfield = 'rating_' . $criterion->id . '_' . $member->id;
                    $commentfield = 'comment_' . $criterion->id . '_' . $member->id;
                    $params = [
                        'groupassignid' => $groupassign->id,
                        'criteriaid' => $criterion->id,
                        'reviewerid' => $USER->id,
                        'revieweeid' => $member->id,
                    ];
                    $record = $DB->get_record('groupassign_peerreviews', $params) ?: (object)$params;
                    $record->groupid = $group->id;
                    $record->rating = (int)$data->{$ratingfield};
                    $record->comment = !empty($groupassign->peercomments) && isset($data->{$commentfield})
                        ? trim((string)$data->{$commentfield})
                        : '';
                    $record->commentformat = FORMAT_PLAIN;
                    $record->timemodified = $now;
                    if (!empty($record->id)) {
                        $DB->update_record('groupassign_peerreviews', $record);
                    } else {
                        $record->timecreated = $now;
                        $DB->insert_record('groupassign_peerreviews', $record);
                    }
                }
            }
            \mod_groupassign\event\peer_review_saved::create([
                'context' => $context,
                'objectid' => $groupassign->id,
                'other' => ['groupid' => $group->id],
            ])->trigger();
            redirect(
                new \moodle_url('/mod/groupassign/view.php', ['id' => $cm->id]),
                get_string('peerreviewsaved', 'groupassign'),
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );
        }

        return $prepared;
    }

    /**
     * Prepare group submission form.
     *
     * @param mixed $groupassign
     * @param mixed $cm
     * @param mixed $context
     * @param mixed $group
     * @param mixed $editoroptions
     * @param mixed $fileoptions
     * @return array
     */
    public static function prepare_group_submission_form($groupassign, $cm, $context, $group, $editoroptions, $fileoptions): array {
        $submission = \mod_groupassign\local\workflow_manager::get_submission($groupassign, $group->id);
        $submissionsopen = \mod_groupassign\local\workflow_manager::submission_open($groupassign);
        $draftitemid = file_get_submitted_draft_itemid('submissionfiles');
        file_prepare_draft_area($draftitemid, $context->id, 'mod_groupassign', 'submission', $submission->id ?? 0, $fileoptions);

        $mform = new \mod_groupassign\form\submission_form(new \moodle_url('/mod/groupassign/view.php', ['id' => $cm->id]), [
            'groupassign' => $groupassign,
            'editoroptions' => $editoroptions,
            'fileoptions' => $fileoptions,
        ]);
        $mform->set_data([
            'groupid' => $group->id,
            'submissioneditor' => [
                'text' => $submission->submissiontext ?? '',
                'format' => $submission->submissionformat ?? FORMAT_HTML,
            ],
            'submissionfiles' => $draftitemid,
        ]);

        return [
            'mform' => $mform,
            'submission' => $submission,
            'submissionsopen' => $submissionsopen,
        ];
    }

    /**
     * Process group submission form.
     *
     * @param mixed $groupassign
     * @param mixed $cm
     * @param mixed $context
     * @param mixed $group
     * @param mixed $editoroptions
     * @param mixed $fileoptions
     * @return array
     */
    public static function process_group_submission_form($groupassign, $cm, $context, $group, $editoroptions, $fileoptions): array {
        global $DB, $PAGE, $USER;

        $prepared = self::prepare_group_submission_form($groupassign, $cm, $context, $group, $editoroptions, $fileoptions);
        $mform = $prepared['mform'];
        $submission = $prepared['submission'];
        $submissionsopen = $prepared['submissionsopen'];

        if ($data = $mform->get_data()) {
            if (!$submissionsopen) {
                redirect(
                    $PAGE->url,
                    get_string('submissionsclosed', 'groupassign'),
                    null,
                    \core\output\notification::NOTIFY_WARNING
                );
            }
            $now = time();
            $record = (object)[
                'groupassignid' => $groupassign->id,
                'groupid' => $group->id,
                'userid' => $USER->id,
                'submissiontext' => !empty($groupassign->submissiononlinetext) ? $data->submissioneditor['text'] : '',
                'submissionformat' => !empty($groupassign->submissiononlinetext) ? $data->submissioneditor['format'] : FORMAT_HTML,
                'status' => GROUPASSIGN_STATUS_SUBMITTED,
                'timemodified' => $now,
                'timesubmitted' => $now,
            ];
            if ($submission) {
                $record->id = $submission->id;
                $DB->update_record('groupassign_submissions', $record);
                $submissionid = $submission->id;
            } else {
                $record->timecreated = $now;
                $submissionid = $DB->insert_record('groupassign_submissions', $record);
            }
            if (!empty($groupassign->submissionfile)) {
                file_save_draft_area_files(
                    $data->submissionfiles,
                    $context->id,
                    'mod_groupassign',
                    'submission',
                    $submissionid,
                    $fileoptions
                );
            }
            \mod_groupassign\event\submission_saved::create([
                'context' => $context,
                'objectid' => $submissionid,
                'other' => ['groupid' => $group->id],
            ])->trigger();
            redirect(
                $PAGE->url,
                get_string('submissionsaved', 'groupassign'),
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );
        }

        return $prepared;
    }
}
