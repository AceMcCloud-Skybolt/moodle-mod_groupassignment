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
 * View for mod_groupassign.
 *
 * @package    mod_groupassign
 * @copyright  2026 Matthew Darch <matthew.darch.1up@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/groupassign/lib.php');
require_once($CFG->dirroot . '/group/lib.php');
require_once($CFG->libdir . '/formslib.php');

$id = required_param('id', PARAM_INT);
$action = optional_param('action', 'view', PARAM_ALPHA);
$groupid = optional_param('groupid', 0, PARAM_INT);
$groupname = optional_param('groupname', '', PARAM_TEXT);
$groupdescription = optional_param('groupdescription', '', PARAM_TEXT);

$cm = get_coursemodule_from_id('groupassign', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$groupassign = $DB->get_record('groupassign', ['id' => $cm->instance], '*', MUST_EXIST);
$context = context_module::instance($cm->id);

require_login($course, true, $cm);
require_once($CFG->libdir . '/completionlib.php');
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$viewevent = \mod_groupassign\event\course_module_viewed::create([
    'context' => $context,
    'objectid' => $groupassign->id,
]);
$viewevent->add_record_snapshot('groupassign', $groupassign);
$viewevent->trigger();

$PAGE->set_url('/mod/groupassign/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($groupassign->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->requires->css('/mod/groupassign/styles.css');

$canjoin = has_capability('mod/groupassign:join', $context);
$canmanage = has_capability('mod/groupassign:managegroups', $context);
$cangrade = has_capability('mod/groupassign:grade', $context);
// Feedback editor (teachers): trust text only when trusttext is enabled and the
// grader holds the trust capability, mirroring core assign.
$feedbackeditoroptions = [
    'context' => $context,
    'maxfiles' => EDITOR_UNLIMITED_FILES,
    'maxbytes' => $course->maxbytes,
    'trusttext' => trusttext_active() && has_capability('moodle/site:trustcontent', $context),
];
// Submission editor (students): never trusted.
$submissioneditoroptions = [
    'context' => $context,
    'maxfiles' => EDITOR_UNLIMITED_FILES,
    'maxbytes' => $course->maxbytes,
    'trusttext' => false,
];
$fileoptions = [
    'subdirs' => 0,
    'maxbytes' => !empty($groupassign->maxbytes) ? $groupassign->maxbytes : $course->maxbytes,
    'maxfiles' => !empty($groupassign->maxfiles) ? $groupassign->maxfiles : 5,
    'accepted_types' => '*',
];
if (!empty($groupassign->submissionfiletypes)) {
    $types = array_values(array_filter(array_map('trim', explode(',', $groupassign->submissionfiletypes))));
    if ($types) {
        $fileoptions['accepted_types'] = $types;
    }
}

$renderer = $PAGE->get_renderer('mod_groupassign');

if ($action !== 'view' && $action !== 'submissions' && $action !== 'grade' && $action !== 'peerreview') {
    require_sesskey();
    require_capability('mod/groupassign:join', $context);
    if (!\mod_groupassign\local\workflow_manager::selection_open($groupassign)) {
        redirect($PAGE->url, get_string('selectionclosed', 'groupassign'), null, \core\output\notification::NOTIFY_WARNING);
    }

    if ($action === 'join' && $groupid && $groupassign->allowstudentjoin) {
        $groups = \mod_groupassign\local\workflow_manager::get_groups($groupassign);
        if (isset($groups[$groupid])) {
            if (\mod_groupassign\local\workflow_manager::user_has_submitted_group($groupassign, $USER->id)) {
                redirect(
                    $PAGE->url,
                    get_string('groupmembershiplocked', 'groupassign'),
                    null,
                    \core\output\notification::NOTIFY_WARNING
                );
            }
            $count = \mod_groupassign\local\workflow_manager::member_count($groupid);
            if (!empty($groupassign->maxmembers) && $count >= $groupassign->maxmembers) {
                redirect(
                    $PAGE->url,
                    get_string('groupfull', 'groupassign'),
                    null,
                    \core\output\notification::NOTIFY_WARNING
                );
            }
            \mod_groupassign\local\workflow_manager::remove_user_from_activity_groups($groupassign, $USER->id);
            groups_add_member($groupid, $USER->id);
            \mod_groupassign\event\group_joined::create([
                'context' => $context,
                'objectid' => $groupid,
                'other' => ['groupassignid' => $groupassign->id],
            ])->trigger();
            redirect($PAGE->url, get_string('groupjoined', 'groupassign'), null, \core\output\notification::NOTIFY_SUCCESS);
        }
    } else if ($action === 'leave' && $groupid && $groupassign->allowstudentleave) {
        $mygroups = \mod_groupassign\local\workflow_manager::get_my_groups($groupassign, $USER->id);
        if (isset($mygroups[$groupid])) {
            if (\mod_groupassign\local\workflow_manager::group_has_submission($groupassign, $groupid)) {
                redirect(
                    $PAGE->url,
                    get_string('groupmembershiplocked', 'groupassign'),
                    null,
                    \core\output\notification::NOTIFY_WARNING
                );
            }
            groups_remove_member($groupid, $USER->id);
            \mod_groupassign\event\group_left::create([
                'context' => $context,
                'objectid' => $groupid,
                'other' => ['groupassignid' => $groupassign->id],
            ])->trigger();
            redirect($PAGE->url, get_string('groupleft', 'groupassign'), null, \core\output\notification::NOTIFY_SUCCESS);
        }
        redirect($PAGE->url, get_string('invalidgroupid', 'groupassign'), null, \core\output\notification::NOTIFY_ERROR);
    } else if ($action === 'create' && $groupassign->allowstudentcreate) {
        if (trim($groupname) !== '') {
            if (\mod_groupassign\local\workflow_manager::user_has_submitted_group($groupassign, $USER->id)) {
                redirect(
                    $PAGE->url,
                    get_string('groupmembershiplocked', 'groupassign'),
                    null,
                    \core\output\notification::NOTIFY_WARNING
                );
            }
            if (empty($groupassign->groupingid)) {
                groupassign_sync_groups($groupassign);
                $groupassign = $DB->get_record('groupassign', ['id' => $groupassign->id], '*', MUST_EXIST);
            }
            $group = (object)[
                'courseid' => $course->id,
                'name' => $groupassign->allowstudentrename ? $groupname : fullname($USER) . ' group',
                'description' => $groupassign->allowstudentdescription ? $groupdescription : '',
                'descriptionformat' => FORMAT_HTML,
            ];
            $newgroupid = groups_create_group($group, false);
            groups_assign_grouping($groupassign->groupingid, $newgroupid);
            \mod_groupassign\local\workflow_manager::remove_user_from_activity_groups($groupassign, $USER->id);
            groups_add_member($newgroupid, $USER->id);
            $activitygroups = \mod_groupassign\local\workflow_manager::get_groups($groupassign);
            groupassign_track_group($groupassign->id, $newgroupid, count($activitygroups) + 1);
            \mod_groupassign\event\group_created::create([
                'context' => $context,
                'objectid' => $newgroupid,
                'other' => ['groupassignid' => $groupassign->id],
            ])->trigger();
            redirect($PAGE->url, get_string('groupcreated', 'groupassign'), null, \core\output\notification::NOTIFY_SUCCESS);
        }
    }
    redirect($PAGE->url);
}

$gradeform = null;
$peerreviewform = null;
$submissionform = null;

if ($action === 'grade' && $cangrade) {
    $gradeform = \mod_groupassign\local\form_controller::process_grade_form(
        $groupassign,
        $cm,
        $context,
        $groupid,
        $feedbackeditoroptions
    );
} else if ($action === 'peerreview' && $canjoin && !$canmanage && !$cangrade) {
    $peerreviewform = \mod_groupassign\local\form_controller::process_peer_review_form($groupassign, $cm, $context);
} else if ($action === 'view' && $canjoin && !$canmanage && !$cangrade) {
    $mygroups = \mod_groupassign\local\workflow_manager::get_my_groups($groupassign, $USER->id);
    if ($mygroups) {
        $currentgroup = reset($mygroups);
        $submissionform = \mod_groupassign\local\form_controller::process_group_submission_form(
            $groupassign,
            $cm,
            $context,
            $currentgroup,
            $submissioneditoroptions,
            $fileoptions
        );
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($groupassign->name));
echo format_module_intro('groupassign', $groupassign, $cm->id);

if ($canmanage || $cangrade) {
    if ($action === 'submissions' && $cangrade) {
        $renderer->render_submissions_view($groupassign, $cm, $context);
    } else if ($action === 'grade' && $cangrade) {
        $renderer->render_grade_view($groupassign, $cm, $context, $groupid, $feedbackeditoroptions, $gradeform);
    } else {
        $renderer->render_teacher_view($groupassign, $cm, $context);
    }
} else if ($canjoin) {
    if ($action === 'peerreview') {
        $renderer->render_peer_review_view($groupassign, $cm, $context, $peerreviewform);
    } else {
        $renderer->render_student_view($groupassign, $cm, $context, $submissioneditoroptions, $fileoptions, $submissionform);
    }
}

echo $OUTPUT->footer();
