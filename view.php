<?php
// This file is part of Moodle - http://moodle.org/

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/groupassign/lib.php');
require_once($CFG->dirroot . '/group/lib.php');
require_once($CFG->dirroot . '/mod/groupassign/classes/form/submission_form.php');
require_once($CFG->dirroot . '/mod/groupassign/classes/form/grade_form.php');
require_once($CFG->dirroot . '/mod/groupassign/classes/form/peer_review_form.php');

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

$PAGE->set_url('/mod/groupassign/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($groupassign->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$PAGE->requires->css('/mod/groupassign/styles.css');

$canjoin = has_capability('mod/groupassign:join', $context);
$canmanage = has_capability('mod/groupassign:managegroups', $context);
$cangrade = has_capability('mod/groupassign:grade', $context);
$editoroptions = [
    'context' => $context,
    'maxfiles' => EDITOR_UNLIMITED_FILES,
    'maxbytes' => $course->maxbytes,
    'trusttext' => true,
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

function groupassign_selection_open($groupassign): bool {
    $now = time();
    if (!empty($groupassign->selectionopen) && $now < $groupassign->selectionopen) {
        return false;
    }
    if (!empty($groupassign->selectionclose) && $now > $groupassign->selectionclose) {
        return false;
    }
    return true;
}

function groupassign_submission_open($groupassign): bool {
    $now = time();
    if (!empty($groupassign->allowsubmissionsfromdate) && $now < $groupassign->allowsubmissionsfromdate) {
        return false;
    }
    if (!empty($groupassign->cutoffdate) && $now > $groupassign->cutoffdate) {
        return false;
    }
    return true;
}

function groupassign_submission_window_notice($groupassign): string {
    $parts = [];
    if (!empty($groupassign->allowsubmissionsfromdate)) {
        $parts[] = get_string('allowsubmissionsfromdate', 'assign') . ': ' .
            userdate($groupassign->allowsubmissionsfromdate);
    }
    if (!empty($groupassign->duedate)) {
        $parts[] = get_string('duedate', 'assign') . ': ' . userdate($groupassign->duedate);
    }
    if (!empty($groupassign->cutoffdate)) {
        $parts[] = get_string('cutoffdate', 'assign') . ': ' . userdate($groupassign->cutoffdate);
    }
    return implode(' ', $parts);
}

function groupassign_get_groups($groupassign): array {
    if (empty($groupassign->groupingid)) {
        return [];
    }
    return groups_get_all_groups($groupassign->course, 0, $groupassign->groupingid, 'g.*', 'g.name ASC') ?: [];
}

function groupassign_get_my_groups($groupassign, int $userid): array {
    if (empty($groupassign->groupingid)) {
        return [];
    }
    return groups_get_all_groups($groupassign->course, $userid, $groupassign->groupingid, 'g.*', 'g.name ASC') ?: [];
}

function groupassign_member_count(int $groupid): int {
    return count(groups_get_members($groupid, 'u.id'));
}

function groupassign_capacity_label($groupassign, int $count): string {
    if (empty($groupassign->maxmembers)) {
        return (string)$count;
    }
    return $count . ' / ' . $groupassign->maxmembers;
}

function groupassign_group_status_badges($groupassign, int $count, bool $iscurrent = false): string {
    $badges = [];
    if ($iscurrent) {
        $badges[] = html_writer::span(get_string('currentgroup', 'groupassign'), 'badge bg-success me-1');
    }
    if (!empty($groupassign->maxmembers) && $count >= $groupassign->maxmembers) {
        $badges[] = html_writer::span(get_string('groupfull', 'groupassign'), 'badge bg-secondary me-1');
    } else if (!empty($groupassign->maxmembers)) {
        $badges[] = html_writer::span(get_string('spotsavailable', 'groupassign', $groupassign->maxmembers - $count),
            'badge bg-info text-dark me-1');
    }
    if (!empty($groupassign->minmembers) && $count < $groupassign->minmembers) {
        $badges[] = html_writer::span(get_string('underfilledgroups', 'groupassign'), 'badge bg-warning text-dark me-1');
    } else if (!empty($groupassign->minmembers)) {
        $badges[] = html_writer::span(get_string('ok'), 'badge bg-success me-1');
    }

    return implode(' ', $badges);
}

function groupassign_selection_window_notice($groupassign): string {
    $parts = [];
    if (groupassign_selection_open($groupassign)) {
        $parts[] = get_string('selectionisopen', 'groupassign');
    } else if (!empty($groupassign->selectionopen) && time() < $groupassign->selectionopen) {
        $parts[] = get_string('selectionnotopen', 'groupassign');
    } else {
        $parts[] = get_string('selectionclosed', 'groupassign');
    }
    if (!empty($groupassign->selectionopen)) {
        $parts[] = get_string('selectionopens', 'groupassign', userdate($groupassign->selectionopen));
    }
    if (!empty($groupassign->selectionclose)) {
        $parts[] = get_string('selectioncloses', 'groupassign', userdate($groupassign->selectionclose));
    }
    return implode(' ', $parts);
}

function groupassign_remove_user_from_activity_groups($groupassign, int $userid): void {
    foreach (groupassign_get_my_groups($groupassign, $userid) as $group) {
        groups_remove_member($group->id, $userid);
    }
}

function groupassign_get_submission($groupassign, int $groupid) {
    global $DB;
    return $DB->get_record('groupassign_submissions', [
        'groupassignid' => $groupassign->id,
        'groupid' => $groupid,
    ]);
}

function groupassign_get_grade($groupassign, int $groupid) {
    global $DB;
    return $DB->get_record('groupassign_grades', [
        'groupassignid' => $groupassign->id,
        'groupid' => $groupid,
    ]);
}

function groupassign_submission_late($groupassign, $submission): bool {
    return $submission
        && !empty($groupassign->duedate)
        && !empty($submission->timesubmitted)
        && (int)$submission->timesubmitted > (int)$groupassign->duedate;
}

function groupassign_status_label($submission, $groupassign = null): string {
    if (!$submission) {
        return get_string('notsubmitted', 'groupassign');
    }
    $status = (int)$submission->status === GROUPASSIGN_STATUS_SUBMITTED
        ? get_string('submissionstatus:submitted', 'groupassign')
        : get_string('submissionstatus:draft', 'groupassign');
    if ($groupassign && groupassign_submission_late($groupassign, $submission)) {
        $status .= ' - ' . get_string('late', 'groupassign');
    }
    return $status;
}

function groupassign_grade_label($groupassign, $grade): string {
    if (!$grade || $grade->grade === null) {
        return '-';
    }
    return format_float($grade->grade, 2) . ' / ' . format_float($groupassign->grade, 2);
}

function groupassign_render_submission_content($submission, $context): string {
    if (!$submission) {
        return '-';
    }

    $parts = [];
    if (!empty($submission->submissiontext)) {
        $parts[] = html_writer::div(format_text($submission->submissiontext, $submission->submissionformat),
            'groupassign-submission-text');
    }

    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_groupassign', 'submission', $submission->id, 'filename', false);
    if ($files) {
        $links = [];
        foreach ($files as $file) {
            $url = moodle_url::make_pluginfile_url($context->id, 'mod_groupassign', 'submission', $submission->id,
                $file->get_filepath(), $file->get_filename());
            $links[] = html_writer::link($url, s($file->get_filename()));
        }
        $parts[] = html_writer::alist($links);
    }

    return $parts ? implode('', $parts) : '-';
}

function groupassign_render_activity_details($groupassign, $cm, $context): string {
    $parts = [];
    $intro = format_module_intro('groupassign', $groupassign, $cm->id);
    if (trim(strip_tags($intro)) !== '') {
        $parts[] = html_writer::div($intro, 'groupassign-intro mb-3');
    }
    if (!empty($groupassign->activity)) {
        $parts[] = html_writer::div(
            html_writer::tag('h4', get_string('activityinstructions', 'groupassign'), ['class' => 'h5'])
            . format_text($groupassign->activity, $groupassign->activityformat),
            'groupassign-activity-instructions mb-3'
        );
    }

    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'mod_groupassign', 'introattachment', 0, 'filename', false);
    if ($files) {
        $links = [];
        foreach ($files as $file) {
            $url = moodle_url::make_pluginfile_url($context->id, 'mod_groupassign', 'introattachment', 0,
                $file->get_filepath(), $file->get_filename());
            $links[] = html_writer::link($url, s($file->get_filename()));
        }
        $parts[] = html_writer::div(
            html_writer::tag('h4', get_string('additionalfiles', 'groupassign'), ['class' => 'h5'])
            . html_writer::alist($links),
            'groupassign-additional-files mb-3'
        );
    }

    return $parts ? html_writer::div(implode('', $parts), 'card card-body mb-4') : '';
}

function groupassign_setup_warnings($groupassign, array $groups, int $studentswithoutgroup): array {
    $warnings = [];
    $grouping = !empty($groupassign->groupingid) ? groups_get_grouping($groupassign->groupingid) : false;

    if ($groupassign->formationmode === GROUPASSIGN_FORMATION_EXISTING && !$grouping) {
        $warnings[] = get_string('coursecopywarningmissinggrouping', 'groupassign');
    }
    if (!$groups) {
        $warnings[] = get_string('coursecopywarningnogroups', 'groupassign');
    }
    if ($studentswithoutgroup > 0) {
        $warnings[] = get_string('coursecopywarningunallocated', 'groupassign', $studentswithoutgroup);
    }

    return $warnings;
}

function groupassign_peer_rating_options(string $ratingtype = 'fourlevel'): array {
    if ($ratingtype === 'satisfactory') {
        return [
            '' => get_string('choosedots'),
            1 => get_string('peerrating:unsatisfactory', 'groupassign'),
            2 => get_string('peerrating:satisfactory', 'groupassign'),
            3 => get_string('peerrating:strong', 'groupassign'),
        ];
    }
    if ($ratingtype === 'marks5') {
        return [
            '' => get_string('choosedots'),
            0 => '0',
            1 => '1',
            2 => '2',
            3 => '3',
            4 => '4',
            5 => '5',
        ];
    }
    return [
        '' => get_string('choosedots'),
        1 => get_string('peerrating:concern', 'groupassign'),
        2 => get_string('peerrating:developing', 'groupassign'),
        3 => get_string('peerrating:met', 'groupassign'),
        4 => get_string('peerrating:exceeded', 'groupassign'),
    ];
}

function groupassign_get_peercriteria($groupassign): array {
    global $DB;

    $criteria = $DB->get_records('groupassign_peercriteria',
        ['groupassignid' => $groupassign->id, 'archived' => 0], 'sortorder ASC');
    if (!$criteria) {
        groupassign_save_peercriteria($groupassign->id, groupassign_default_peercriteria());
        $criteria = $DB->get_records('groupassign_peercriteria',
            ['groupassignid' => $groupassign->id, 'archived' => 0], 'sortorder ASC');
    }
    return $criteria;
}

function groupassign_get_reviewable_members($groupassign, int $groupid, int $userid): array {
    $members = groups_get_members($groupid, 'u.*', 'u.lastname, u.firstname');
    if (empty($groupassign->peerselfassessment)) {
        unset($members[$userid]);
    }
    return $members ?: [];
}

function groupassign_peer_review_expected_count($groupassign, int $groupid, int $userid): int {
    return count(groupassign_get_peercriteria($groupassign))
        * count(groupassign_get_reviewable_members($groupassign, $groupid, $userid));
}

function groupassign_peer_review_completed_count($groupassign, int $groupid, int $userid): int {
    global $DB;

    return $DB->count_records('groupassign_peerreviews', [
        'groupassignid' => $groupassign->id,
        'groupid' => $groupid,
        'reviewerid' => $userid,
    ]);
}

function groupassign_peer_review_status_label($groupassign, int $groupid, int $userid): string {
    $expected = groupassign_peer_review_expected_count($groupassign, $groupid, $userid);
    if (!$expected) {
        return get_string('peerreviewdisabled', 'groupassign');
    }
    $completed = groupassign_peer_review_completed_count($groupassign, $groupid, $userid);
    if ($completed >= $expected) {
        return get_string('peerreviewstatus:complete', 'groupassign');
    }
    if ($completed > 0) {
        return get_string('peerreviewstatus:partial', 'groupassign') . " ($completed / $expected)";
    }
    return get_string('peerreviewstatus:notstarted', 'groupassign') . " (0 / $expected)";
}

function groupassign_peer_review_group_completion_label($groupassign, int $groupid): string {
    $members = groups_get_members($groupid, 'u.id');
    if (!$members) {
        return '-';
    }
    $complete = 0;
    foreach ($members as $member) {
        $expected = groupassign_peer_review_expected_count($groupassign, $groupid, $member->id);
        if ($expected && groupassign_peer_review_completed_count($groupassign, $groupid, $member->id) >= $expected) {
            $complete++;
        }
    }
    return $complete . ' / ' . count($members);
}

function groupassign_peer_review_group_flag($groupassign, int $groupid): string {
    global $DB;

    $reviews = $DB->get_records('groupassign_peerreviews', [
        'groupassignid' => $groupassign->id,
        'groupid' => $groupid,
    ]);
    if (!$reviews) {
        return get_string('peerflag:clear', 'groupassign');
    }

    $received = [];
    $given = [];
    foreach ($reviews as $review) {
        $received[$review->revieweeid][] = (float)$review->rating;
        $given[$review->reviewerid][] = (float)$review->rating;
    }

    $receivedavgs = [];
    foreach ($received as $userid => $ratings) {
        $receivedavgs[$userid] = array_sum($ratings) / max(count($ratings), 1);
    }
    if (count($receivedavgs) >= 2) {
        $minavg = min($receivedavgs);
        $maxavg = max($receivedavgs);
        if ($minavg <= 2.0 && ($maxavg - $minavg) >= 1.5) {
            return get_string('peerflag:concern', 'groupassign');
        }
    }

    $givenavgs = [];
    foreach ($given as $userid => $ratings) {
        $givenavgs[$userid] = array_sum($ratings) / max(count($ratings), 1);
    }
    if (count($givenavgs) >= 2) {
        $median = (array_sum($givenavgs) / count($givenavgs));
        foreach ($givenavgs as $avg) {
            if ($avg <= ($median - 1.2)) {
                return get_string('peerflag:followup', 'groupassign');
            }
        }
    }

    return get_string('peerflag:clear', 'groupassign');
}

function groupassign_render_dashboard_section(string $title, string $content, bool $open = false): string {
    $attributes = ['class' => 'groupassign-dashboard-section mb-3'];
    if ($open) {
        $attributes['open'] = 'open';
    }

    return html_writer::start_tag('details', $attributes)
        . html_writer::tag('summary', $title, ['class' => 'groupassign-dashboard-summary'])
        . html_writer::div($content, 'groupassign-dashboard-body')
        . html_writer::end_tag('details');
}

function groupassign_render_teacher_view($groupassign, $cm, $context): void {
    global $OUTPUT;

    $groups = groupassign_get_groups($groupassign);
    $students = get_enrolled_users($context, 'mod/groupassign:join', 0, 'u.*', 'u.lastname, u.firstname');
    $studentswithoutgroup = 0;
    foreach ($students as $student) {
        if (!groupassign_get_my_groups($groupassign, $student->id)) {
            $studentswithoutgroup++;
        }
    }

    $underfilled = 0;
    $overfull = 0;
    $empty = 0;
    $submitted = 0;
    $latesubmissions = 0;
    $needsgrading = 0;
    $graded = 0;
    foreach ($groups as $group) {
        $count = groupassign_member_count($group->id);
        if ($count === 0) {
            $empty++;
        }
        if (!empty($groupassign->minmembers) && $count < $groupassign->minmembers) {
            $underfilled++;
        }
        if (!empty($groupassign->maxmembers) && $count > $groupassign->maxmembers) {
            $overfull++;
        }
        $submission = groupassign_get_submission($groupassign, $group->id);
        $grade = groupassign_get_grade($groupassign, $group->id);
        if ($submission && (int)$submission->status === GROUPASSIGN_STATUS_SUBMITTED) {
            $submitted++;
            if (groupassign_submission_late($groupassign, $submission)) {
                $latesubmissions++;
            }
            if (!$grade || $grade->grade === null) {
                $needsgrading++;
            }
        }
        if ($grade && $grade->grade !== null) {
            $graded++;
        }
    }

    echo $OUTPUT->heading(get_string('groupmanagementsummary', 'groupassign'), 3);
    echo groupassign_render_activity_details($groupassign, $cm, $context);
    $warnings = groupassign_setup_warnings($groupassign, $groups, $studentswithoutgroup);
    if ($warnings) {
        echo $OUTPUT->notification(
            html_writer::tag('strong', get_string('coursecopycheck', 'groupassign')) .
            html_writer::alist(array_map('s', $warnings)) .
            html_writer::div(html_writer::link(new moodle_url('/course/modedit.php', [
                'update' => $cm->id,
                'return' => 1,
            ]), get_string('settings'), ['class' => 'btn btn-sm btn-warning mt-2'])),
            'warning',
            false
        );
    }
    $overview = html_writer::div(groupassign_selection_window_notice($groupassign), 'alert alert-info');
    $overview .= html_writer::start_div('groupassign-summary-grid mb-4');
    $cards = [
        get_string('studentgroups', 'groupassign') => count($groups),
        get_string('participants') => count($students),
        get_string('studentswithoutgroup', 'groupassign') => $studentswithoutgroup,
        get_string('emptygroups', 'groupassign') => $empty,
        get_string('latesubmissions', 'groupassign') => $latesubmissions,
        get_string('underfilledgroups', 'groupassign') => $underfilled,
        get_string('overfullgroups', 'groupassign') => $overfull,
    ];
    foreach ($cards as $label => $value) {
        $overview .= html_writer::div(
            html_writer::div($value, 'groupassign-summary-number') . html_writer::div($label, 'text-muted'),
            'card card-body groupassign-summary-card'
        );
    }
    $overview .= html_writer::end_div();

    $groupingname = $groupassign->groupingid ?: '-';
    if (!empty($groupassign->groupingid) && $grouping = groups_get_grouping($groupassign->groupingid)) {
        $groupingname = format_string($grouping->name) . ' (' . $groupassign->groupingid . ')';
    }
    $overview .= html_writer::div(get_string('groupingused', 'groupassign') . ': ' . $groupingname, 'mb-3 text-muted');
    echo groupassign_render_dashboard_section(get_string('dashboardoverview', 'groupassign'), $overview, false);

    if (!$groups) {
        echo groupassign_render_dashboard_section(get_string('groupsandmembership', 'groupassign'),
            $OUTPUT->notification(get_string('nogroups', 'groupassign'), 'info'));
        return;
    }

    $table = new html_table();
    $table->head = [
        get_string('group'),
        get_string('members', 'groupassign'),
        get_string('maxmembers', 'groupassign'),
    ];
    $table->head[] = get_string('status', 'groupassign');
    foreach ($groups as $group) {
        $members = groups_get_members($group->id, 'u.*', 'u.lastname, u.firstname');
        $count = count($members);
        $status = [];
        if (!empty($groupassign->minmembers) && $count < $groupassign->minmembers) {
            $status[] = get_string('underfilledgroups', 'groupassign');
        }
        if (!empty($groupassign->maxmembers) && $count > $groupassign->maxmembers) {
            $status[] = get_string('overfullgroups', 'groupassign');
        }
        if (!$status) {
            $status[] = get_string('ok');
        }
        $row = [
            format_string($group->name),
            $members ? implode(', ', array_map('fullname', $members)) : '-',
            groupassign_capacity_label($groupassign, $count),
        ];
        $row[] = implode(', ', $status);
        $table->data[] = $row;
    }
    echo groupassign_render_dashboard_section(get_string('groupsandmembership', 'groupassign'),
        html_writer::table($table), false);

    $summarytable = new html_table();
    $summarytable->attributes['class'] = 'generaltable mb-3';
    $summarytable->head = [
        get_string('participants'),
        get_string('submittedgroups', 'groupassign'),
        get_string('needsgrading', 'groupassign'),
        get_string('gradedgroups', 'groupassign'),
    ];
    $summarytable->data[] = [count($students), $submitted, $needsgrading, $graded];

    $gradingcontent = html_writer::tag('h5', get_string('gradingsummary', 'assign'), ['class' => 'mb-3']);
    $gradingcontent .= html_writer::table($summarytable);
    $gradingcontent .= html_writer::div(
        html_writer::link(new moodle_url('/mod/groupassign/view.php', ['id' => $cm->id, 'action' => 'submissions']),
            get_string('gradebutton', 'groupassign'), ['class' => 'btn btn-primary']),
        'mt-3'
    );
    echo groupassign_render_dashboard_section(get_string('submissionsandgrading', 'groupassign'), $gradingcontent, false);

    $peercontent = '';
    if (!empty($groupassign->peerenabled)) {
        $peertable = new html_table();
        $peertable->head = [
            get_string('group'),
            get_string('members', 'groupassign'),
            get_string('peerreviews', 'groupassign'),
            get_string('peerflag', 'groupassign'),
            get_string('actions', 'groupassign'),
        ];
        foreach ($groups as $group) {
            $members = groups_get_members($group->id, 'u.*', 'u.lastname, u.firstname');
            $nudgebuttons = [];
            foreach ($members as $member) {
                $nudgebuttons[] = html_writer::link(new moodle_url('/message/index.php', ['user2' => $member->id]),
                    get_string('nudge', 'groupassign') . ': ' . fullname($member), ['class' => 'btn btn-sm btn-outline-secondary']);
            }
            $peertable->data[] = [
                format_string($group->name),
                $members ? implode(', ', array_map('fullname', $members)) : '-',
                groupassign_peer_review_group_completion_label($groupassign, $group->id),
                groupassign_peer_review_group_flag($groupassign, $group->id),
                $nudgebuttons ? implode(' ', $nudgebuttons) : '-',
            ];
        }
        $peercontent .= html_writer::table($peertable);
    } else {
        $peercontent .= $OUTPUT->notification(get_string('peerreviewdisabled', 'groupassign'), 'info');
    }
    echo groupassign_render_dashboard_section(get_string('peerassessment', 'groupassign'), $peercontent, false);
}

function groupassign_render_submissions_view($groupassign, $cm, $context): void {
    global $OUTPUT;

    $groups = groupassign_get_groups($groupassign);
    $search = optional_param('search', '', PARAM_TEXT);
    $statusfilter = optional_param('statusfilter', 'all', PARAM_ALPHA);

    echo $OUTPUT->heading(get_string('submissions', 'groupassign'), 3);
    if (!$groups) {
        echo $OUTPUT->notification(get_string('nogroups', 'groupassign'), 'info');
        return;
    }

    $toolbar = html_writer::start_tag('form', ['method' => 'get', 'class' => 'mb-3']);
    $toolbar .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $cm->id]);
    $toolbar .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'submissions']);
    $toolbar .= html_writer::start_div('d-flex flex-wrap gap-2 align-items-end');
    $toolbar .= html_writer::tag('label', get_string('searchusers', 'groupassign'), ['for' => 'groupassign-search']);
    $toolbar .= html_writer::empty_tag('input', [
        'type' => 'text',
        'name' => 'search',
        'id' => 'groupassign-search',
        'value' => s($search),
        'class' => 'form-control',
        'style' => 'max-width: 18rem;',
    ]);
    $toolbar .= html_writer::tag('label', get_string('status', 'groupassign'), ['for' => 'groupassign-statusfilter']);
    $toolbar .= html_writer::select(
        [
            'all' => get_string('all'),
            'submitted' => get_string('submissionstatus:submitted', 'groupassign'),
            'late' => get_string('late', 'groupassign'),
            'notsubmitted' => get_string('notsubmitted', 'groupassign'),
        ],
        'statusfilter',
        $statusfilter,
        false,
        ['id' => 'groupassign-statusfilter', 'class' => 'custom-select']
    );
    $toolbar .= html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('filter'), 'class' => 'btn btn-secondary']);
    $toolbar .= html_writer::end_div();
    $toolbar .= html_writer::end_tag('form');
    echo $toolbar;

    $table = new html_table();
    $table->attributes['class'] = 'generaltable groupassign-submissions-table';
    $table->head = [
        html_writer::checkbox('selectall', 1, false, '', ['disabled' => 'disabled']),
        get_string('fullnameuser'),
        get_string('email'),
        get_string('group'),
        get_string('status', 'groupassign'),
        get_string('grade', 'groupassign'),
        get_string('timemodified', 'assign') . ' (' . get_string('submission', 'groupassign') . ')',
        get_string('submissionfiles', 'groupassign'),
        get_string('submissioncomments', 'groupassign'),
        get_string('timemodified', 'assign') . ' (' . get_string('feedback') . ')',
        get_string('feedbackcomments', 'groupassign'),
        get_string('grade', 'groupassign'),
        get_string('actions', 'groupassign'),
    ];

    foreach ($groups as $group) {
        $members = groups_get_members($group->id, 'u.*', 'u.lastname, u.firstname');
        $submission = groupassign_get_submission($groupassign, $group->id);
        $grade = groupassign_get_grade($groupassign, $group->id);
        foreach ($members as $member) {
            $namematch = $search === '' || stripos(fullname($member), $search) !== false || stripos($member->email, $search) !== false;
            $status = groupassign_status_label($submission, $groupassign);
            $statusmatch = $statusfilter === 'all'
                || ($statusfilter === 'submitted' && (int)($submission->status ?? -1) === GROUPASSIGN_STATUS_SUBMITTED)
                || ($statusfilter === 'late' && groupassign_submission_late($groupassign, $submission))
                || ($statusfilter === 'notsubmitted' && !$submission);
            if (!$namematch || !$statusmatch) {
                continue;
            }
            $gradeaction = html_writer::link(new moodle_url('/mod/groupassign/view.php', [
                'id' => $cm->id,
                'action' => 'grade',
                'groupid' => $group->id,
            ]), get_string('gradebutton', 'groupassign'), ['class' => 'btn btn-sm btn-primary']);
            $editsubmissionaction = html_writer::link(new moodle_url('/mod/groupassign/view.php', [
                'id' => $cm->id,
                'action' => 'grade',
                'groupid' => $group->id,
            ]), get_string('editsubmission', 'groupassign'));
            $grantextensionaction = html_writer::link(new moodle_url('/mod/groupassign/view.php', [
                'id' => $cm->id,
                'action' => 'grade',
                'groupid' => $group->id,
            ]), get_string('grantsubmissionextension', 'groupassign'));
            $nudgeaction = html_writer::link(new moodle_url('/message/index.php', ['user2' => $member->id]),
                get_string('nudge', 'groupassign'), ['class' => 'btn btn-sm btn-outline-secondary']);
            $actionmenu = html_writer::start_tag('details', ['class' => 'groupassign-row-actions'])
                . html_writer::tag('summary', '...')
                . html_writer::div(
                    html_writer::div($gradeaction, 'mb-1')
                    . html_writer::div($editsubmissionaction, 'mb-1')
                    . html_writer::div($grantextensionaction, 'mb-1')
                    . html_writer::div($nudgeaction),
                    'small'
                )
                . html_writer::end_tag('details');
            $table->data[] = [
                html_writer::checkbox('selectedusers[]', $member->id, false),
                fullname($member),
                s($member->email),
                format_string($group->name),
                $status,
                groupassign_grade_label($groupassign, $grade) . html_writer::div($gradeaction, 'mt-1'),
                $submission && !empty($submission->timemodified) ? userdate($submission->timemodified) : '-',
                groupassign_render_submission_content($submission, $context),
                '-',
                $grade && !empty($grade->timemodified) ? userdate($grade->timemodified) : '-',
                ($grade && !empty($grade->feedback)) ? format_text($grade->feedback, $grade->feedbackformat) : '-',
                groupassign_grade_label($groupassign, $grade) . html_writer::div($nudgeaction, 'mt-1'),
                $actionmenu,
            ];
        }
    }

    echo html_writer::div(
        html_writer::checkbox('quickgrading', 1, false, get_string('quickgrading', 'groupassign'), ['disabled' => 'disabled']) . ' ' .
        html_writer::tag('button', get_string('actions', 'groupassign'), ['class' => 'btn btn-outline-secondary btn-sm', 'type' => 'button', 'disabled' => 'disabled']),
        'mb-2 d-flex gap-2 align-items-center'
    );
    echo html_writer::table($table);
}

function groupassign_render_grade_view($groupassign, $cm, $context, int $groupid, array $editoroptions): void {
    global $DB, $OUTPUT, $PAGE, $USER;

    require_capability('mod/groupassign:grade', $context);
    $groups = groupassign_get_groups($groupassign);
    if (empty($groups[$groupid])) {
        echo $OUTPUT->notification(get_string('invalidgroupid', 'groupassign'), 'error');
        return;
    }

    $group = $groups[$groupid];
    $members = groups_get_members($groupid, 'u.*', 'u.lastname, u.firstname');
    $submission = groupassign_get_submission($groupassign, $groupid);
    $grade = groupassign_get_grade($groupassign, $groupid);
    $gradeurl = new moodle_url('/mod/groupassign/view.php', [
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

    if ($mform->is_cancelled()) {
        redirect(new moodle_url('/mod/groupassign/view.php', ['id' => $cm->id, 'action' => 'submissions']));
    } else if ($data = $mform->get_data()) {
        $now = time();
        $record = (object)[
            'groupassignid' => $groupassign->id,
            'groupid' => $groupid,
            'graderid' => $USER->id,
            'grade' => $data->grade === '' ? null : (float)$data->grade,
            'feedback' => $data->feedbackeditor['text'],
            'feedbackformat' => $data->feedbackeditor['format'],
            'timemodified' => $now,
        ];
        if ($grade) {
            $record->id = $grade->id;
            $DB->update_record('groupassign_grades', $record);
        } else {
            $record->timecreated = $now;
            $DB->insert_record('groupassign_grades', $record);
        }

        foreach ($members as $member) {
            $gradefield = 'membergrade_' . $member->id;
            $feedbackfield = 'memberfeedback_' . $member->id;
            $membergradevalue = isset($data->{$gradefield}) ? trim((string)$data->{$gradefield}) : '';
            $memberfeedback = isset($data->{$feedbackfield}) ? trim((string)$data->{$feedbackfield}) : '';
            $existing = groupassign_get_membergrade($groupassign->id, $member->id);

            if ($membergradevalue === '' && $memberfeedback === '') {
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
                'grade' => $membergradevalue === '' ? null : (float)$membergradevalue,
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
        redirect($gradeurl, get_string('gradesaved', 'groupassign'), null,
            \core\output\notification::NOTIFY_SUCCESS);
    }

    echo html_writer::link(new moodle_url('/mod/groupassign/view.php', ['id' => $cm->id, 'action' => 'submissions']),
        get_string('submissions', 'groupassign'), ['class' => 'btn btn-secondary mb-3']);
    echo $OUTPUT->heading(format_string($group->name), 3);
    echo html_writer::div(get_string('members', 'groupassign') . ': ' .
        ($members ? implode(', ', array_map('fullname', $members)) : '-'), 'mb-3');
    echo $OUTPUT->heading(get_string('submission', 'groupassign'), 4);
    echo html_writer::div(groupassign_render_submission_content($submission, $context), 'card card-body mb-4');
    $mform->display();
}

function groupassign_render_peer_review_view($groupassign, $cm, $context): void {
    global $DB, $OUTPUT, $PAGE, $USER;

    require_capability('mod/groupassign:join', $context);
    if (empty($groupassign->peerenabled)) {
        echo $OUTPUT->notification(get_string('peerreviewdisabled', 'groupassign'), 'info');
        return;
    }

    $mygroups = groupassign_get_my_groups($groupassign, $USER->id);
    if (!$mygroups) {
        echo $OUTPUT->notification(get_string('nogroup', 'groupassign'), 'info');
        return;
    }

    $group = reset($mygroups);
    $criteria = groupassign_get_peercriteria($groupassign);
    $members = groupassign_get_reviewable_members($groupassign, $group->id, $USER->id);
    if (!$criteria || !$members) {
        echo $OUTPUT->notification(get_string('peerreviewdisabled', 'groupassign'), 'info');
        return;
    }

    $url = new moodle_url('/mod/groupassign/view.php', ['id' => $cm->id, 'action' => 'peerreview']);
    $mform = new \mod_groupassign\form\peer_review_form($url, [
        'groupassign' => $groupassign,
        'criteria' => $criteria,
        'members' => $members,
        'ratingsbycriteria' => array_reduce($criteria, static function($carry, $criterion) {
            $carry[$criterion->id] = groupassign_peer_rating_options($criterion->ratingtype ?? 'fourlevel');
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

    if ($mform->is_cancelled()) {
        redirect(new moodle_url('/mod/groupassign/view.php', ['id' => $cm->id]));
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
        redirect(new moodle_url('/mod/groupassign/view.php', ['id' => $cm->id]),
            get_string('peerreviewsaved', 'groupassign'), null, \core\output\notification::NOTIFY_SUCCESS);
    }

    echo html_writer::link(new moodle_url('/mod/groupassign/view.php', ['id' => $cm->id]),
        get_string('modulename', 'groupassign'), ['class' => 'btn btn-secondary mb-3']);
    echo $OUTPUT->heading(get_string('peerreview', 'groupassign'), 3);
    echo html_writer::div(get_string('currentgroup', 'groupassign') . ': ' . format_string($group->name),
        'alert alert-info');
    $mform->display();
}

function groupassign_render_group_submission_form($groupassign, $cm, $context, $group, $editoroptions, $fileoptions): void {
    global $DB, $OUTPUT, $PAGE, $USER;

    $submission = groupassign_get_submission($groupassign, $group->id);
    $submissionsopen = groupassign_submission_open($groupassign);
    $draftitemid = file_get_submitted_draft_itemid('submissionfiles');
    file_prepare_draft_area($draftitemid, $context->id, 'mod_groupassign', 'submission', $submission->id ?? 0, $fileoptions);

    $mform = new \mod_groupassign\form\submission_form(new moodle_url('/mod/groupassign/view.php', ['id' => $cm->id]), [
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

    if ($data = $mform->get_data()) {
        if (!$submissionsopen) {
            redirect($PAGE->url, get_string('submissionsclosed', 'groupassign'), null,
                \core\output\notification::NOTIFY_WARNING);
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
            file_save_draft_area_files($data->submissionfiles, $context->id, 'mod_groupassign', 'submission',
                $submissionid, $fileoptions);
        }
        redirect($PAGE->url, get_string('submissionsaved', 'groupassign'), null,
            \core\output\notification::NOTIFY_SUCCESS);
    }

    echo $OUTPUT->heading(get_string('submission', 'groupassign'), 3);
    if ($submission) {
        echo html_writer::div(get_string('status', 'groupassign') . ': ' .
            groupassign_status_label($submission, $groupassign),
            'alert alert-info');
    }
    $notice = groupassign_submission_window_notice($groupassign);
    if ($notice !== '') {
        echo html_writer::div($notice, 'alert alert-light border');
    }
    if ($submissionsopen) {
        $mform->display();
    } else {
        echo $OUTPUT->notification(get_string('submissionsclosed', 'groupassign'), 'warning');
    }
}

function groupassign_render_student_view($groupassign, $cm, $context, $editoroptions, $fileoptions): void {
    global $OUTPUT, $USER;

    $groups = groupassign_get_groups($groupassign);
    $mygroups = groupassign_get_my_groups($groupassign, $USER->id);
    $mygroupids = array_map(fn($group) => (int)$group->id, $mygroups);
    $isopen = groupassign_selection_open($groupassign);

    echo groupassign_render_activity_details($groupassign, $cm, $context);
    echo $OUTPUT->heading(get_string('choosegroup', 'groupassign'), 3);
    echo html_writer::div(groupassign_selection_window_notice($groupassign),
        $isopen ? 'alert alert-info' : 'alert alert-warning');

    if ($mygroups) {
        $currentgroup = reset($mygroups);
        echo html_writer::div(get_string('currentgroup', 'groupassign') . ': ' .
            implode(', ', array_map(fn($group) => format_string($group->name), $mygroups)), 'alert alert-success');
        groupassign_render_group_submission_form($groupassign, $cm, $context, $currentgroup, $editoroptions, $fileoptions);
        if (!empty($groupassign->peerenabled)) {
            $peerstatus = groupassign_peer_review_status_label($groupassign, $currentgroup->id, $USER->id);
            echo html_writer::div(
                html_writer::span($peerstatus, 'me-3') .
                html_writer::link(new moodle_url('/mod/groupassign/view.php', [
                    'id' => $cm->id,
                    'action' => 'peerreview',
                ]), get_string('peerreview', 'groupassign'), ['class' => 'btn btn-outline-primary']),
                'alert alert-light border'
            );
        }
    } else {
        echo html_writer::div(get_string('nogroup', 'groupassign'), 'alert alert-info');
    }

    if (!$groups) {
        echo $OUTPUT->notification(get_string('nogroups', 'groupassign'), 'info');
    }

    foreach ($groups as $group) {
        $count = groupassign_member_count($group->id);
        $iscurrent = in_array((int)$group->id, $mygroupids, true);
        $isfull = !empty($groupassign->maxmembers) && $count >= $groupassign->maxmembers;
        if (!empty($groupassign->hidefullgroups) && $isfull && !$iscurrent) {
            continue;
        }
        $classes = 'card mb-3 groupassign-group-card' . ($iscurrent ? ' is-current' : '');
        echo html_writer::start_div($classes);
        echo html_writer::start_div('card-body');
        echo html_writer::start_div('d-flex justify-content-between align-items-start gap-3');
        echo html_writer::div($OUTPUT->heading(format_string($group->name), 4, 'h4 mb-1') .
            html_writer::div(groupassign_group_status_badges($groupassign, $count, $iscurrent), 'mb-2'));
        echo html_writer::div(get_string('members', 'groupassign') . ': ' .
            groupassign_capacity_label($groupassign, $count), 'fw-semibold text-nowrap');
        echo html_writer::end_div();
        if (!empty($group->description)) {
            echo html_writer::div(format_text($group->description, $group->descriptionformat), 'mb-2');
        }
        if (!empty($groupassign->showmembers)) {
            $members = groups_get_members($group->id, 'u.*', 'u.lastname, u.firstname');
            echo html_writer::div($members ? implode(', ', array_map('fullname', $members)) : '-', 'mb-2 text-muted');
        }

        $actions = [];
        if ($isopen && $groupassign->allowstudentjoin && !$iscurrent
                && !$isfull) {
            $label = $mygroups ? get_string('switchgroup', 'groupassign') : get_string('join', 'groupassign');
            $actions[] = html_writer::link(new moodle_url('/mod/groupassign/view.php', [
                'id' => $cm->id,
                'action' => 'join',
                'groupid' => $group->id,
                'sesskey' => sesskey(),
            ]), $label, ['class' => 'btn btn-primary me-2']);
        } else if ($isopen && $groupassign->allowstudentjoin && !$iscurrent && $isfull) {
            $actions[] = html_writer::span(get_string('groupfull', 'groupassign'), 'btn btn-secondary disabled me-2');
        }
        if ($isopen && $groupassign->allowstudentleave && $iscurrent) {
            $actions[] = html_writer::link(new moodle_url('/mod/groupassign/view.php', [
                'id' => $cm->id,
                'action' => 'leave',
                'groupid' => $group->id,
                'sesskey' => sesskey(),
            ]), get_string('leave', 'groupassign'), ['class' => 'btn btn-secondary me-2']);
        }
        echo $actions ? html_writer::div(implode(' ', $actions), 'mt-3') : '';
        echo html_writer::end_div();
        echo html_writer::end_div();
    }

    if ($isopen && $groupassign->allowstudentcreate) {
        echo html_writer::start_div('card mt-4');
        echo html_writer::start_div('card-body');
        echo $OUTPUT->heading(get_string('creategroup', 'groupassign'), 4);
        echo html_writer::start_tag('form', ['method' => 'post', 'action' => new moodle_url('/mod/groupassign/view.php')]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $cm->id]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'create']);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        echo html_writer::tag('label', get_string('groupname', 'groupassign'), ['for' => 'groupassign_groupname']);
        echo html_writer::empty_tag('input', [
            'type' => 'text',
            'name' => 'groupname',
            'id' => 'groupassign_groupname',
            'class' => 'form-control mb-3',
            'required' => 'required',
        ]);
        if ($groupassign->allowstudentdescription) {
            echo html_writer::tag('label', get_string('groupdescription', 'groupassign'), ['for' => 'groupassign_groupdescription']);
            echo html_writer::tag('textarea', '', [
                'name' => 'groupdescription',
                'id' => 'groupassign_groupdescription',
                'class' => 'form-control mb-3',
                'rows' => 3,
            ]);
        }
        echo html_writer::empty_tag('input', [
            'type' => 'submit',
            'value' => get_string('creategroup', 'groupassign'),
            'class' => 'btn btn-primary',
        ]);
        echo html_writer::end_tag('form');
        echo html_writer::end_div();
        echo html_writer::end_div();
    }
}

if ($action !== 'view' && $action !== 'submissions' && $action !== 'grade' && $action !== 'peerreview') {
    require_sesskey();
    require_capability('mod/groupassign:join', $context);
    if (!groupassign_selection_open($groupassign)) {
        redirect($PAGE->url, get_string('selectionclosed', 'groupassign'), null, \core\output\notification::NOTIFY_WARNING);
    }

    if ($action === 'join' && $groupid && $groupassign->allowstudentjoin) {
        $groups = groupassign_get_groups($groupassign);
        if (isset($groups[$groupid])) {
            $count = groupassign_member_count($groupid);
            if (!empty($groupassign->maxmembers) && $count >= $groupassign->maxmembers) {
                redirect($PAGE->url, get_string('groupfull', 'groupassign'), null,
                    \core\output\notification::NOTIFY_WARNING);
            }
            groupassign_remove_user_from_activity_groups($groupassign, $USER->id);
            groups_add_member($groupid, $USER->id);
            redirect($PAGE->url, get_string('groupjoined', 'groupassign'), null, \core\output\notification::NOTIFY_SUCCESS);
        }
    } else if ($action === 'leave' && $groupid && $groupassign->allowstudentleave) {
        $mygroups = groupassign_get_my_groups($groupassign, $USER->id);
        if (isset($mygroups[$groupid])) {
            groups_remove_member($groupid, $USER->id);
            redirect($PAGE->url, get_string('groupleft', 'groupassign'), null, \core\output\notification::NOTIFY_SUCCESS);
        }
        redirect($PAGE->url, get_string('invalidgroupid', 'groupassign'), null, \core\output\notification::NOTIFY_ERROR);
    } else if ($action === 'create' && $groupassign->allowstudentcreate) {
        if (trim($groupname) !== '') {
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
            groupassign_remove_user_from_activity_groups($groupassign, $USER->id);
            groups_add_member($newgroupid, $USER->id);
            groupassign_track_group($groupassign->id, $newgroupid, count(groupassign_get_groups($groupassign)) + 1);
            redirect($PAGE->url, get_string('groupcreated', 'groupassign'), null, \core\output\notification::NOTIFY_SUCCESS);
        }
    }
    redirect($PAGE->url);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($groupassign->name));
echo format_module_intro('groupassign', $groupassign, $cm->id);

if ($canmanage || $cangrade) {
    if ($action === 'submissions' && $cangrade) {
        groupassign_render_submissions_view($groupassign, $cm, $context);
    } else if ($action === 'grade' && $cangrade) {
        groupassign_render_grade_view($groupassign, $cm, $context, $groupid, $editoroptions);
    } else {
        groupassign_render_teacher_view($groupassign, $cm, $context);
    }
} else if ($canjoin) {
    if ($action === 'peerreview') {
        groupassign_render_peer_review_view($groupassign, $cm, $context);
    } else {
        groupassign_render_student_view($groupassign, $cm, $context, $editoroptions, $fileoptions);
    }
}

echo $OUTPUT->footer();
