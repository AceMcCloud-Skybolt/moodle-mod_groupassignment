<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/group/lib.php');
require_once($CFG->libdir . '/gradelib.php');

define('GROUPASSIGN_FORMATION_SELFSELECT', 'selfselect');
define('GROUPASSIGN_FORMATION_AUTO', 'auto');
define('GROUPASSIGN_FORMATION_EXISTING', 'existing');
define('GROUPASSIGN_SUFFIX_NUMBERS', 'numbers');
define('GROUPASSIGN_SUFFIX_LETTERS', 'letters');
define('GROUPASSIGN_STATUS_DRAFT', 0);
define('GROUPASSIGN_STATUS_SUBMITTED', 1);

function groupassign_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
        case FEATURE_GROUPS:
        case FEATURE_GROUPINGS:
        case FEATURE_GRADE_HAS_GRADE:
        case FEATURE_COMPLETION_TRACKS_VIEWS:
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return false;
        case FEATURE_ADVANCED_GRADING:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return false;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_ASSESSMENT;
        default:
            return null;
    }
}

function groupassign_add_instance($data, $mform = null) {
    global $DB;

    $peercriteria = groupassign_extract_peercriteria($data);
    $draftitems = groupassign_extract_editor_fields($data);
    groupassign_normalise_settings($data);
    $data->timemodified = time();
    $data->id = $DB->insert_record('groupassign', $data);
    groupassign_save_activity_files($data, $draftitems);
    groupassign_sync_groups($data);
    groupassign_save_peercriteria($data->id, $peercriteria);
    groupassign_grade_item_update($data);

    return $data->id;
}

function groupassign_update_instance($data, $mform = null) {
    global $DB;

    $peercriteria = groupassign_extract_peercriteria($data);
    $draftitems = groupassign_extract_editor_fields($data);
    groupassign_normalise_settings($data);
    $data->id = $data->instance;
    $data->timemodified = time();
    $DB->update_record('groupassign', $data);
    groupassign_save_activity_files($data, $draftitems);
    groupassign_sync_groups($data);
    groupassign_save_peercriteria($data->id, $peercriteria);
    groupassign_grade_item_update($data);

    return true;
}

function groupassign_delete_instance($id) {
    global $DB;

    if (!$groupassign = $DB->get_record('groupassign', ['id' => $id])) {
        return false;
    }

    grade_update('mod/groupassign', $groupassign->course, 'mod', 'groupassign', $groupassign->id, 0, null, ['deleted' => 1]);
    if ($cm = get_coursemodule_from_instance('groupassign', $id, $groupassign->course, false)) {
        $context = context_module::instance($cm->id);
        get_file_storage()->delete_area_files($context->id, 'mod_groupassign');
    }
    $DB->delete_records('groupassign_groups', ['groupassignid' => $id]);
    $DB->delete_records('groupassign_submissions', ['groupassignid' => $id]);
    $DB->delete_records('groupassign_grades', ['groupassignid' => $id]);
    $DB->delete_records('groupassign_membergrades', ['groupassignid' => $id]);
    $DB->delete_records('groupassign_peerreviews', ['groupassignid' => $id]);
    $DB->delete_records('groupassign_peercriteria', ['groupassignid' => $id]);
    $DB->delete_records('groupassign', ['id' => $id]);

    return true;
}

function groupassign_get_coursemodule_info($coursemodule) {
    global $DB;

    $fields = 'id, name, intro, introformat';
    if (!$groupassign = $DB->get_record('groupassign', ['id' => $coursemodule->instance], $fields)) {
        return false;
    }

    $info = new cached_cm_info();
    $info->name = $groupassign->name;
    if ($coursemodule->showdescription) {
        $info->content = format_module_intro('groupassign', $groupassign, $coursemodule->id, false);
    }

    return $info;
}

function groupassign_grade_item_update(stdClass $groupassign, $grades = null) {
    $item = [
        'itemname' => clean_param($groupassign->name, PARAM_NOTAGS),
        'gradetype' => GRADE_TYPE_VALUE,
        'grademax' => !empty($groupassign->grade) ? $groupassign->grade : 100,
        'grademin' => 0,
    ];

    return grade_update('mod/groupassign', $groupassign->course, 'mod', 'groupassign', $groupassign->id, 0, $grades, $item);
}

function groupassign_grading_areas_list(): array {
    return ['submissions' => get_string('submissions', 'groupassign')];
}

function groupassign_update_grades(stdClass $groupassign, $userid = 0, $nullifnone = true) {
    global $DB;

    if ($userid) {
        $grades = groupassign_get_gradebook_grades_for_user($groupassign, $userid, $nullifnone);
    } else {
        $grades = [];
        $gradegroups = $DB->get_records('groupassign_grades', ['groupassignid' => $groupassign->id]);
        foreach ($gradegroups as $gradegroup) {
            foreach (groups_get_members($gradegroup->groupid, 'u.id') as $member) {
                $membergrade = groupassign_get_membergrade($groupassign->id, $member->id);
                $grades[$member->id] = groupassign_gradebook_grade_object($member->id, $gradegroup, $membergrade);
            }
        }
        if ($nullifnone) {
            $cm = get_coursemodule_from_instance('groupassign', $groupassign->id, $groupassign->course, false, MUST_EXIST);
            $context = context_module::instance($cm->id);
            $students = get_enrolled_users($context, 'mod/groupassign:join', 0, 'u.id');
            foreach ($students as $student) {
                if (!isset($grades[$student->id])) {
                    $membergrade = groupassign_get_membergrade($groupassign->id, $student->id);
                    $grades[$student->id] = groupassign_gradebook_grade_object($student->id, null, $membergrade);
                }
            }
        }
    }

    groupassign_grade_item_update($groupassign, $grades);
}

function groupassign_get_gradebook_grades_for_user(stdClass $groupassign, int $userid, bool $nullifnone): array {
    global $DB;

    $grades = [];
    $groups = !empty($groupassign->groupingid)
        ? groups_get_all_groups($groupassign->course, $userid, $groupassign->groupingid, 'g.id')
        : [];
    foreach ($groups as $group) {
        $gradegroup = $DB->get_record('groupassign_grades',
            ['groupassignid' => $groupassign->id, 'groupid' => $group->id]);
        $membergrade = groupassign_get_membergrade($groupassign->id, $userid);
        if ($gradegroup || ($membergrade && (int)$membergrade->groupid === (int)$group->id)) {
            $grades[$userid] = groupassign_gradebook_grade_object($userid, $gradegroup ?: null, $membergrade);
            return $grades;
        }
    }

    if ($nullifnone) {
        $membergrade = groupassign_get_membergrade($groupassign->id, $userid);
        $grades[$userid] = groupassign_gradebook_grade_object($userid, null, $membergrade);
    }

    return $grades;
}

function groupassign_get_membergrade(int $groupassignid, int $userid): ?stdClass {
    global $DB;

    $membergrade = $DB->get_record('groupassign_membergrades', [
        'groupassignid' => $groupassignid,
        'userid' => $userid,
    ]);

    return $membergrade ?: null;
}

function groupassign_gradebook_grade_object(int $userid, ?stdClass $gradegroup, ?stdClass $membergrade = null): stdClass {
    $rawgrade = $gradegroup ? $gradegroup->grade : null;
    if ($membergrade && $membergrade->grade !== null) {
        $rawgrade = $membergrade->grade;
    }

    $grade = (object)[
        'userid' => $userid,
        'rawgrade' => $rawgrade,
    ];

    if ($membergrade && trim((string)$membergrade->feedback) !== '') {
        $grade->feedback = $membergrade->feedback;
        $grade->feedbackformat = $membergrade->feedbackformat;
        $grade->usermodified = $membergrade->graderid;
        $grade->dategraded = $membergrade->timemodified;
        $grade->datesubmitted = $membergrade->timemodified;
    } else if ($gradegroup) {
        $grade->feedback = $gradegroup->feedback;
        $grade->feedbackformat = $gradegroup->feedbackformat;
        $grade->usermodified = $gradegroup->graderid;
        $grade->dategraded = $gradegroup->timemodified;
        $grade->datesubmitted = $gradegroup->timemodified;
    } else if ($membergrade) {
        $grade->usermodified = $membergrade->graderid;
        $grade->dategraded = $membergrade->timemodified;
        $grade->datesubmitted = $membergrade->timemodified;
    }
    return $grade;
}

function groupassign_normalise_settings(stdClass $data): void {
    if (isset($data->activity)) {
        $data->activity = (string)$data->activity;
    }
    $data->activityformat = (int)($data->activityformat ?? FORMAT_HTML);
    $data->submissionattachments = empty($data->submissionattachments) ? 0 : 1;
    $data->alwaysshowdescription = empty($data->alwaysshowdescription) ? 0 : 1;
    $data->timelimit = max(0, (int)($data->timelimit ?? 0));
    $data->formationmode = $data->formationmode ?? GROUPASSIGN_FORMATION_SELFSELECT;
    $data->managedgrouping = $data->formationmode === GROUPASSIGN_FORMATION_EXISTING ? 0 : 1;
    $data->numgroups = max(0, (int)($data->numgroups ?? 0));
    $data->groupnameprefix = trim((string)($data->groupnameprefix ?? ''));
    $data->groupnamesuffix = in_array(($data->groupnamesuffix ?? GROUPASSIGN_SUFFIX_NUMBERS),
        [GROUPASSIGN_SUFFIX_NUMBERS, GROUPASSIGN_SUFFIX_LETTERS], true)
        ? $data->groupnamesuffix
        : GROUPASSIGN_SUFFIX_NUMBERS;
    $data->minmembers = max(0, (int)($data->minmembers ?? 0));
    $data->maxmembers = max(0, (int)($data->maxmembers ?? 0));
    $data->allowstudentjoin = empty($data->allowstudentjoin) ? 0 : 1;
    $data->allowstudentleave = empty($data->allowstudentleave) ? 0 : 1;
    $data->allowstudentcreate = empty($data->allowstudentcreate) ? 0 : 1;
    $data->allowstudentrename = empty($data->allowstudentrename) ? 0 : 1;
    $data->allowstudentdescription = empty($data->allowstudentdescription) ? 0 : 1;
    $data->hidefullgroups = empty($data->hidefullgroups) ? 0 : 1;
    $data->showmembers = empty($data->showmembers) ? 0 : 1;
    $data->peerenabled = empty($data->peerenabled) ? 0 : 1;
    $data->peerselfassessment = empty($data->peerselfassessment) ? 0 : 1;
    $data->peercomments = empty($data->peercomments) ? 0 : 1;
    $data->peeranonymous = empty($data->peeranonymous) ? 0 : 1;
    $data->peerstudentresponse = empty($data->peerstudentresponse) ? 0 : 1;
    $data->submissiononlinetext = empty($data->submissiononlinetext) ? 0 : 1;
    $data->submissionfile = empty($data->submissionfile) ? 0 : 1;
    $data->submissionfiletypes = trim((string)($data->submissionfiletypes ?? ''));
    $data->wordlimit = max(0, (int)($data->wordlimit ?? 0));
    $data->maxfiles = max(1, (int)($data->maxfiles ?? 5));
    $data->maxbytes = max(0, (int)($data->maxbytes ?? 0));
    $data->submissiondrafts = empty($data->submissiondrafts) ? 0 : 1;
    $data->requiresubmissionstatement = empty($data->requiresubmissionstatement) ? 0 : 1;
    $data->maxattempts = (int)($data->maxattempts ?? -1);
    $attemptmethods = ['manual', 'automatic', 'untilpass'];
    $data->attemptreopenmethod = in_array(($data->attemptreopenmethod ?? 'manual'), $attemptmethods, true)
        ? $data->attemptreopenmethod
        : 'manual';
    $data->peerrequirejustification = empty($data->peerrequirejustification) ? 0 : 1;
    $data->sendnotifications = empty($data->sendnotifications) ? 0 : 1;
    $data->sendlatenotifications = empty($data->sendlatenotifications) ? 0 : 1;
    $data->sendstudentnotifications = empty($data->sendstudentnotifications) ? 0 : 1;
    $data->blindmarking = empty($data->blindmarking) ? 0 : 1;
    $data->hidegrader = empty($data->hidegrader) ? 0 : 1;
    $data->markingworkflow = empty($data->markingworkflow) ? 0 : 1;
    $data->markingallocation = empty($data->markingallocation) ? 0 : 1;
    $data->markinganonymous = empty($data->markinganonymous) ? 0 : 1;
    if (property_exists($data, 'feedbackcommentsenabled')) {
        unset($data->feedbackcommentsenabled);
    }
    if (property_exists($data, 'feedbackfilesenabled')) {
        unset($data->feedbackfilesenabled);
    }
    if (property_exists($data, 'submissiontypesgroup')) {
        unset($data->submissiontypesgroup);
    }
}

function groupassign_extract_editor_fields(stdClass $data): array {
    $draftitems = [
        'activity' => null,
        'introattachments' => $data->introattachments ?? null,
    ];
    if (isset($data->activityeditor) && is_array($data->activityeditor)) {
        $data->activity = $data->activityeditor['text'] ?? '';
        $data->activityformat = $data->activityeditor['format'] ?? FORMAT_HTML;
        $draftitems['activity'] = $data->activityeditor['itemid'] ?? null;
    }
    unset($data->activityeditor, $data->introattachments);

    return $draftitems;
}

function groupassign_save_activity_files(stdClass $groupassign, array $draftitems): void {
    if (empty($groupassign->coursemodule)) {
        return;
    }

    $context = context_module::instance($groupassign->coursemodule);
    if (!empty($draftitems['activity'])) {
        file_save_draft_area_files($draftitems['activity'], $context->id, 'mod_groupassign',
            'activityattachment', 0, ['subdirs' => true]);
    }
    if (!empty($draftitems['introattachments'])) {
        file_save_draft_area_files($draftitems['introattachments'], $context->id, 'mod_groupassign',
            'introattachment', 0, ['subdirs' => 0]);
    }
}

function groupassign_extract_peercriteria(stdClass $data): array {
    $criteria = [];
    if (!empty($data->peercriteria_title) && is_array($data->peercriteria_title)) {
        $ids = $data->peercriteria_id ?? [];
        $titles = $data->peercriteria_title;
        $descriptions = $data->peercriteria_description ?? [];
        $ratingtypes = $data->peercriteria_ratingtype ?? [];
        foreach ($titles as $index => $title) {
            $title = trim((string)$title);
            if ($title !== '') {
                $criteria[] = [
                    'id' => (int)($ids[$index] ?? 0),
                    'title' => $title,
                    'description' => trim((string)($descriptions[$index] ?? '')),
                    'ratingtype' => trim((string)($ratingtypes[$index] ?? 'fourlevel')),
                ];
            }
        }
    }
    unset($data->peercriteria_id, $data->peercriteria_title, $data->peercriteria_description,
        $data->peercriteria_ratingtype);

    return $criteria ?: groupassign_default_peercriteria();
}

function groupassign_default_peercriteria(): array {
    return [
        [
            'title' => get_string('peercriterion1', 'groupassign'),
            'description' => '',
            'ratingtype' => 'fourlevel',
        ],
        [
            'title' => get_string('peercriterion2', 'groupassign'),
            'description' => '',
            'ratingtype' => 'fourlevel',
        ],
        [
            'title' => get_string('peercriterion3', 'groupassign'),
            'description' => '',
            'ratingtype' => 'fourlevel',
        ],
        [
            'title' => get_string('peercriterion4', 'groupassign'),
            'description' => '',
            'ratingtype' => 'fourlevel',
        ],
        [
            'title' => get_string('peercriterion5', 'groupassign'),
            'description' => '',
            'ratingtype' => 'fourlevel',
        ],
    ];
}

function groupassign_save_peercriteria(int $groupassignid, array $criteria): void {
    global $DB;

    $now = time();
    $existing = $DB->get_records('groupassign_peercriteria', ['groupassignid' => $groupassignid], 'sortorder ASC');
    $existingbyid = [];
    $existingbysortorder = [];
    foreach ($existing as $record) {
        $existingbyid[(int)$record->id] = $record;
        if (empty($record->archived)) {
            $existingbysortorder[(int)$record->sortorder] = $record;
        }
    }

    $validratingtypes = ['fourlevel', 'satisfactory', 'marks5'];
    $activeids = [];
    $sortorder = 0;
    foreach ($criteria as $criterion) {
        if (is_array($criterion)) {
            $id = (int)($criterion['id'] ?? 0);
            $title = trim((string)($criterion['title'] ?? ''));
            $description = trim((string)($criterion['description'] ?? ''));
            $ratingtype = trim((string)($criterion['ratingtype'] ?? 'fourlevel'));
        } else {
            $id = 0;
            $title = trim((string)$criterion);
            $description = '';
            $ratingtype = 'fourlevel';
        }
        if ($title === '') {
            continue;
        }
        if (!in_array($ratingtype, $validratingtypes, true)) {
            $ratingtype = 'fourlevel';
        }

        if ($id && isset($existingbyid[$id])) {
            $record = $existingbyid[$id];
        } else if (isset($existingbysortorder[$sortorder])) {
            $record = $existingbysortorder[$sortorder];
        } else {
            $record = (object)[
                'groupassignid' => $groupassignid,
                'timecreated' => $now,
            ];
        }

        $record->description = $title;
        $record->descriptionformat = FORMAT_HTML;
        $record->details = $description;
        $record->ratingtype = $ratingtype;
        $record->sortorder = $sortorder++;
        $record->archived = 0;
        $record->timemodified = $now;

        if (!empty($record->id)) {
            $DB->update_record('groupassign_peercriteria', $record);
            $activeids[] = (int)$record->id;
        } else {
            $activeids[] = (int)$DB->insert_record('groupassign_peercriteria', $record);
        }
    }

    foreach ($existing as $record) {
        if (in_array((int)$record->id, $activeids, true)) {
            continue;
        }
        if ($DB->record_exists('groupassign_peerreviews', ['criteriaid' => $record->id])) {
            $record->archived = 1;
            $record->timemodified = $now;
            $DB->update_record('groupassign_peercriteria', $record);
        } else {
            $DB->delete_records('groupassign_peercriteria', ['id' => $record->id]);
        }
    }
}

function groupassign_sync_groups(stdClass $groupassign): void {
    global $DB;

    if ($groupassign->formationmode === GROUPASSIGN_FORMATION_EXISTING && empty($groupassign->managedgrouping)) {
        groupassign_track_existing_grouping($groupassign);
        return;
    }

    if (empty($groupassign->groupingid) || !groups_get_grouping($groupassign->groupingid)) {
        $grouping = (object)[
            'courseid' => $groupassign->course,
            'name' => format_string($groupassign->name),
            'description' => get_string('groupingmanaged', 'groupassign'),
            'descriptionformat' => FORMAT_HTML,
        ];
        $groupassign->groupingid = groups_create_grouping($grouping);
        $DB->set_field('groupassign', 'groupingid', $groupassign->groupingid, ['id' => $groupassign->id]);
    }

    groupassign_track_existing_grouping($groupassign);
    $existing = $DB->get_records('groupassign_groups', ['groupassignid' => $groupassign->id], 'sortorder ASC');
    $count = count($existing);
    for ($sortorder = $count + 1; $sortorder <= $groupassign->numgroups; $sortorder++) {
        $group = (object)[
            'courseid' => $groupassign->course,
            'name' => groupassign_format_group_name($groupassign, $sortorder),
            'description' => '',
            'descriptionformat' => FORMAT_HTML,
        ];
        $groupid = groups_create_group($group, false);
        groups_assign_grouping($groupassign->groupingid, $groupid);
        groupassign_track_group($groupassign->id, $groupid, $sortorder);
    }
}

function groupassign_format_group_name(stdClass $groupassign, int $sortorder): string {
    $prefix = trim((string)($groupassign->groupnameprefix ?? ''));
    if ($prefix === '') {
        $prefix = format_string($groupassign->name);
    }

    $suffix = $groupassign->groupnamesuffix === GROUPASSIGN_SUFFIX_LETTERS
        ? groupassign_number_to_letters($sortorder)
        : (string)$sortorder;

    return trim($prefix . ' ' . $suffix);
}

function groupassign_number_to_letters(int $number): string {
    $letters = '';
    while ($number > 0) {
        $number--;
        $letters = chr(65 + ($number % 26)) . $letters;
        $number = intdiv($number, 26);
    }
    return $letters ?: 'A';
}

function groupassign_track_existing_grouping(stdClass $groupassign): void {
    global $DB;

    $DB->delete_records('groupassign_groups', ['groupassignid' => $groupassign->id]);

    if (empty($groupassign->groupingid)) {
        return;
    }

    $groups = groups_get_all_groups($groupassign->course, 0, $groupassign->groupingid, 'g.*', 'g.name ASC');
    $sortorder = 1;
    foreach ($groups as $group) {
        groupassign_track_group($groupassign->id, $group->id, $sortorder++);
    }
}

function groupassign_track_group(int $groupassignid, int $groupid, int $sortorder): void {
    global $DB;

    if ($record = $DB->get_record('groupassign_groups', ['groupassignid' => $groupassignid, 'groupid' => $groupid])) {
        $record->sortorder = $sortorder;
        $record->timemodified = time();
        $DB->update_record('groupassign_groups', $record);
        return;
    }

    $record = (object)[
        'groupassignid' => $groupassignid,
        'groupid' => $groupid,
        'sortorder' => $sortorder,
        'timecreated' => time(),
        'timemodified' => time(),
    ];
    $DB->insert_record('groupassign_groups', $record);
}

function groupassign_get_file_areas($course, $cm, $context) {
    return [
        'introattachment' => get_string('introattachments', 'assign'),
        'activityattachment' => get_string('activityeditor', 'assign'),
        'submission' => get_string('submissionfiles', 'groupassign'),
    ];
}

function groupassign_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $DB, $USER;

    if ($context->contextlevel !== CONTEXT_MODULE) {
        return false;
    }
    require_login($course, true, $cm);

    if ($filearea === 'introattachment' || $filearea === 'activityattachment') {
        $itemid = (int)array_shift($args);
        if ($itemid !== 0) {
            return false;
        }
        $filename = array_pop($args);
        $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

        $fs = get_file_storage();
        $file = $fs->get_file($context->id, 'mod_groupassign', $filearea, 0, $filepath, $filename);
        if (!$file || $file->is_directory()) {
            return false;
        }

        send_stored_file($file, 0, 0, $forcedownload, $options);
    }

    if ($filearea !== 'submission') {
        return false;
    }

    $itemid = (int)array_shift($args);
    $submission = $DB->get_record('groupassign_submissions', [
        'id' => $itemid,
        'groupassignid' => $cm->instance,
    ]);
    if (!$submission) {
        return false;
    }

    $groupassign = $DB->get_record('groupassign', ['id' => $cm->instance], 'id,course,groupingid');
    if (!$groupassign) {
        return false;
    }

    $groupallowed = $DB->record_exists('groupassign_groups', [
        'groupassignid' => $cm->instance,
        'groupid' => $submission->groupid,
    ]);
    if (!$groupallowed && !empty($groupassign->groupingid)) {
        $groups = groups_get_all_groups($groupassign->course, 0, $groupassign->groupingid, 'g.id');
        $groupallowed = isset($groups[$submission->groupid]);
    }
    if (!$groupallowed) {
        return false;
    }

    $canviewall = has_capability('mod/groupassign:grade', $context);
    $isgroupmember = has_capability('mod/groupassign:join', $context)
        && groups_is_member($submission->groupid, $USER->id);
    if (!$canviewall && !$isgroupmember) {
        return false;
    }

    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'mod_groupassign', $filearea, $itemid, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}

function groupassign_get_view_actions() {
    return ['view'];
}

function groupassign_get_post_actions() {
    return ['join', 'leave', 'create', 'submit'];
}

function groupassign_extend_settings_navigation(settings_navigation $settings, navigation_node $navref): void {
    $cm = $settings->get_page()->cm;
    if (!$cm || !has_capability('mod/groupassign:grade', $cm->context)) {
        return;
    }

    $navref->add(
        text: get_string('submissions', 'groupassign'),
        action: new moodle_url('/mod/groupassign/view.php', ['id' => $cm->id, 'action' => 'submissions']),
        type: navigation_node::TYPE_SETTING,
        key: 'mod_groupassign_submissions'
    );
}
