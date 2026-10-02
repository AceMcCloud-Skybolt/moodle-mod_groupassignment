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
 * Renderer for group assignment workflow pages.
 *
 * @package    mod_groupassign
 * @copyright  2026 Matthew Darch <matthew.darch.1up@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_groupassign\output;

/**
 * Renderer for group assignment workflow pages.
 */
class renderer extends \plugin_renderer_base {
    /**
     * Group status badges.
     *
     * @param mixed $groupassign
     * @param int $count
     * @param bool $iscurrent
     * @return string
     */
    public function group_status_badges($groupassign, int $count, bool $iscurrent = false): string {
        $badges = [];
        if ($iscurrent) {
            $badges[] = \html_writer::span(get_string('currentgroup', 'groupassign'), 'badge bg-success me-1');
        }
        if (!empty($groupassign->maxmembers) && $count >= $groupassign->maxmembers) {
            $badges[] = \html_writer::span(get_string('groupfull', 'groupassign'), 'badge bg-secondary me-1');
        } else if (!empty($groupassign->maxmembers)) {
            $badges[] = \html_writer::span(
                get_string('spotsavailable', 'groupassign', $groupassign->maxmembers - $count),
                'badge bg-info text-dark me-1'
            );
        }
        if (!empty($groupassign->minmembers) && $count < $groupassign->minmembers) {
            $badges[] = \html_writer::span(get_string('underfilledgroups', 'groupassign'), 'badge bg-warning text-dark me-1');
        } else if (!empty($groupassign->minmembers)) {
            $badges[] = \html_writer::span(get_string('ok'), 'badge bg-success me-1');
        }

        return implode(' ', $badges);
    }

    /**
     * Action button.
     *
     * @param mixed $cm
     * @param string $action
     * @param int $groupid
     * @param string $label
     * @param string $classes
     * @return string
     */
    public function action_button($cm, string $action, int $groupid, string $label, string $classes): string {
        $form = \html_writer::start_tag('form', [
            'method' => 'post',
            'action' => new \moodle_url('/mod/groupassign/view.php'),
            'class' => 'd-inline',
        ]);
        $form .= \html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $cm->id]);
        $form .= \html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => $action]);
        $form .= \html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'groupid', 'value' => $groupid]);
        $form .= \html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        $form .= \html_writer::tag('button', $label, ['type' => 'submit', 'class' => $classes]);
        $form .= \html_writer::end_tag('form');
        return $form;
    }

    /**
     * Render submission content.
     *
     * @param mixed $submission
     * @param mixed $context
     * @return string
     */
    public function render_submission_content($submission, $context): string {
        if (!$submission) {
            return '-';
        }

        $parts = [];
        if (!empty($submission->submissiontext)) {
            $parts[] = \html_writer::div(
                format_text($submission->submissiontext, $submission->submissionformat),
                'groupassign-submission-text'
            );
        }

        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_groupassign', 'submission', $submission->id, 'filename', false);
        if ($files) {
            $links = [];
            foreach ($files as $file) {
                $url = \moodle_url::make_pluginfile_url(
                    $context->id,
                    'mod_groupassign',
                    'submission',
                    $submission->id,
                    $file->get_filepath(),
                    $file->get_filename()
                );
                $links[] = \html_writer::link($url, s($file->get_filename()));
            }
            $parts[] = \html_writer::alist($links);
        }

        return $parts ? implode('', $parts) : '-';
    }

    /**
     * Render activity details.
     *
     * @param mixed $groupassign
     * @param mixed $cm
     * @param mixed $context
     * @return string
     */
    public function render_activity_details($groupassign, $cm, $context): string {
        $parts = [];
        $intro = format_module_intro('groupassign', $groupassign, $cm->id);
        if (trim(strip_tags($intro)) !== '') {
            $parts[] = \html_writer::div($intro, 'groupassign-intro mb-3');
        }
        if (!empty($groupassign->activity)) {
            $parts[] = \html_writer::div(
                \html_writer::tag('h4', get_string('activityinstructions', 'groupassign'), ['class' => 'h5'])
                . format_text($groupassign->activity, $groupassign->activityformat),
                'groupassign-activity-instructions mb-3'
            );
        }

        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_groupassign', 'introattachment', 0, 'filename', false);
        if ($files) {
            $links = [];
            foreach ($files as $file) {
                $url = \moodle_url::make_pluginfile_url(
                    $context->id,
                    'mod_groupassign',
                    'introattachment',
                    0,
                    $file->get_filepath(),
                    $file->get_filename()
                );
                $links[] = \html_writer::link($url, s($file->get_filename()));
            }
            $parts[] = \html_writer::div(
                \html_writer::tag('h4', get_string('additionalfiles', 'groupassign'), ['class' => 'h5'])
                . \html_writer::alist($links),
                'groupassign-additional-files mb-3'
            );
        }

        return $parts ? \html_writer::div(implode('', $parts), 'card card-body mb-4') : '';
    }

    /**
     * Render dashboard section.
     *
     * @param string $title
     * @param string $content
     * @param bool $open
     * @return string
     */
    public function render_dashboard_section(string $title, string $content, bool $open = false): string {
        $attributes = ['class' => 'groupassign-dashboard-section mb-3'];
        if ($open) {
            $attributes['open'] = 'open';
        }

        return \html_writer::start_tag('details', $attributes)
            . \html_writer::tag('summary', $title, ['class' => 'groupassign-dashboard-summary'])
            . \html_writer::div($content, 'groupassign-dashboard-body')
            . \html_writer::end_tag('details');
    }

    /**
     * Render teacher view.
     *
     * @param mixed $groupassign
     * @param mixed $cm
     * @param mixed $context
     */
    public function render_teacher_view($groupassign, $cm, $context): void {

        $groups = \mod_groupassign\local\workflow_manager::get_groups($groupassign);
        $membersbygroup = \mod_groupassign\local\workflow_manager::group_members_map($groups);
        $submissionsbygroup = \mod_groupassign\local\workflow_manager::records_by_group(
            'groupassign_submissions',
            $groupassign->id,
            $groups
        );
        $gradesbygroup = \mod_groupassign\local\workflow_manager::records_by_group('groupassign_grades', $groupassign->id, $groups);
        $students = get_enrolled_users($context, 'mod/groupassign:join', 0, 'u.*', 'u.lastname, u.firstname');
        $studentgroupmemberships = [];
        foreach ($membersbygroup as $members) {
            foreach ($members as $member) {
                $studentgroupmemberships[$member->id] = true;
            }
        }
        $studentswithoutgroup = count(array_filter(
            $students,
            static fn($student) => empty($studentgroupmemberships[$student->id])
        ));

        $underfilled = 0;
        $overfull = 0;
        $empty = 0;
        $submitted = 0;
        $latesubmissions = 0;
        $needsgrading = 0;
        $graded = 0;
        foreach ($groups as $group) {
            $count = count($membersbygroup[$group->id] ?? []);
            if ($count === 0) {
                $empty++;
            }
            if (!empty($groupassign->minmembers) && $count < $groupassign->minmembers) {
                $underfilled++;
            }
            if (!empty($groupassign->maxmembers) && $count > $groupassign->maxmembers) {
                $overfull++;
            }
            $submission = $submissionsbygroup[$group->id] ?? null;
            $grade = $gradesbygroup[$group->id] ?? null;
            if ($submission && (int)$submission->status === GROUPASSIGN_STATUS_SUBMITTED) {
                $submitted++;
                if (\mod_groupassign\local\workflow_manager::submission_late($groupassign, $submission)) {
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

        echo $this->output->heading(get_string('groupmanagementsummary', 'groupassign'), 3);
        echo $this->render_activity_details($groupassign, $cm, $context);
        $warnings = \mod_groupassign\local\workflow_manager::setup_warnings($groupassign, $groups, $studentswithoutgroup);
        if ($warnings) {
            echo $this->output->notification(
                \html_writer::tag('strong', get_string('coursecopycheck', 'groupassign')) .
                \html_writer::alist(array_map('s', $warnings)) .
                \html_writer::div(\html_writer::link(new \moodle_url('/course/modedit.php', [
                    'update' => $cm->id,
                    'return' => 1,
                ]), get_string('settings'), ['class' => 'btn btn-sm btn-warning mt-2'])),
                'warning',
                false
            );
        }
        $overview = \html_writer::div(
            \mod_groupassign\local\workflow_manager::selection_window_notice($groupassign),
            'alert alert-info'
        );
        $overview .= \html_writer::start_div('groupassign-summary-grid mb-4');
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
            $overview .= \html_writer::div(
                \html_writer::div($value, 'groupassign-summary-number') . \html_writer::div($label, 'text-muted'),
                'card card-body groupassign-summary-card'
            );
        }
        $overview .= \html_writer::end_div();

        $groupingname = $groupassign->groupingid ?: '-';
        if (!empty($groupassign->groupingid) && $grouping = groups_get_grouping($groupassign->groupingid)) {
            $groupingname = format_string($grouping->name) . ' (' . $groupassign->groupingid . ')';
        }
        $overview .= \html_writer::div(get_string('groupingused', 'groupassign') . ': ' . $groupingname, 'mb-3 text-muted');
        echo $this->render_dashboard_section(get_string('dashboardoverview', 'groupassign'), $overview, false);

        if (!$groups) {
            echo $this->render_dashboard_section(
                get_string('groupsandmembership', 'groupassign'),
                $this->output->notification(get_string('nogroups', 'groupassign'), 'info')
            );
            return;
        }

        $table = new \html_table();
        $table->head = [
            get_string('group'),
            get_string('members', 'groupassign'),
            get_string('maxmembers', 'groupassign'),
        ];
        $table->head[] = get_string('status', 'groupassign');
        foreach ($groups as $group) {
            $members = $membersbygroup[$group->id] ?? [];
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
                \mod_groupassign\local\workflow_manager::capacity_label($groupassign, $count),
            ];
            $row[] = implode(', ', $status);
            $table->data[] = $row;
        }
        echo $this->render_dashboard_section(
            get_string('groupsandmembership', 'groupassign'),
            \html_writer::table($table),
            false
        );

        $summarytable = new \html_table();
        $summarytable->attributes['class'] = 'generaltable mb-3';
        $summarytable->head = [
            get_string('participants'),
            get_string('submittedgroups', 'groupassign'),
            get_string('needsgrading', 'groupassign'),
            get_string('gradedgroups', 'groupassign'),
        ];
        $summarytable->data[] = [count($students), $submitted, $needsgrading, $graded];

        $gradingcontent = \html_writer::tag('h5', get_string('gradingsummary', 'groupassign'), ['class' => 'mb-3']);
        $gradingcontent .= \html_writer::table($summarytable);
        $gradingcontent .= \html_writer::div(
            \html_writer::link(
                new \moodle_url('/mod/groupassign/view.php', ['id' => $cm->id, 'action' => 'submissions']),
                get_string('gradebutton', 'groupassign'),
                ['class' => 'btn btn-primary']
            ),
            'mt-3'
        );
        echo $this->render_dashboard_section(get_string('submissionsandgrading', 'groupassign'), $gradingcontent, false);

        $peercontent = '';
        if (!empty($groupassign->peerenabled)) {
            $peercompletion = \mod_groupassign\local\peer_review_manager::peer_review_completion_map($groupassign, $groups);
            $peerflags = \mod_groupassign\local\peer_review_manager::peer_review_flags_map($groupassign, $groups);
            $criteriacount = count(\mod_groupassign\local\peer_review_manager::get_peercriteria($groupassign));
            $peertable = new \html_table();
            $peertable->head = [
                get_string('group'),
                get_string('members', 'groupassign'),
                get_string('peerreviews', 'groupassign'),
                get_string('peerflag', 'groupassign'),
                get_string('actions', 'groupassign'),
            ];
            foreach ($groups as $group) {
                $members = $membersbygroup[$group->id] ?? [];
                $nudgebuttons = [];
                foreach ($members as $member) {
                    $nudgebuttons[] = \html_writer::link(
                        new \moodle_url('/message/index.php', ['user2' => $member->id]),
                        get_string('nudge', 'groupassign') . ': ' . fullname($member),
                        ['class' => 'btn btn-sm btn-outline-secondary']
                    );
                }
                $peertable->data[] = [
                    format_string($group->name),
                    $members ? implode(', ', array_map('fullname', $members)) : '-',
                    \mod_groupassign\local\peer_review_manager::peer_review_group_completion_label_cached(
                        $groupassign,
                        $group->id,
                        $members,
                        $peercompletion,
                        $criteriacount
                    ),
                    $peerflags[$group->id] ?? get_string('peerflag:clear', 'groupassign'),
                    $nudgebuttons ? implode(' ', $nudgebuttons) : '-',
                ];
            }
            $peercontent .= \html_writer::table($peertable);
        } else {
            $peercontent .= $this->output->notification(get_string('peerreviewdisabled', 'groupassign'), 'info');
        }
        echo $this->render_dashboard_section(get_string('peerassessment', 'groupassign'), $peercontent, false);
    }

    /**
     * Render submissions view.
     *
     * @param mixed $groupassign
     * @param mixed $cm
     * @param mixed $context
     */
    public function render_submissions_view($groupassign, $cm, $context): void {

        $groups = \mod_groupassign\local\workflow_manager::get_groups($groupassign);
        $membersbygroup = \mod_groupassign\local\workflow_manager::group_members_map($groups);
        $submissionsbygroup = \mod_groupassign\local\workflow_manager::records_by_group(
            'groupassign_submissions',
            $groupassign->id,
            $groups
        );
        $gradesbygroup = \mod_groupassign\local\workflow_manager::records_by_group('groupassign_grades', $groupassign->id, $groups);
        $search = optional_param('search', '', PARAM_TEXT);
        $statusfilter = optional_param('statusfilter', 'all', PARAM_ALPHA);
        $page = max(0, optional_param('page', 0, PARAM_INT));
        $perpage = min(100, max(10, optional_param('perpage', 20, PARAM_INT)));

        echo $this->output->heading(get_string('submissions', 'groupassign'), 3);
        if (!$groups) {
            echo $this->output->notification(get_string('nogroups', 'groupassign'), 'info');
            return;
        }

        $toolbar = \html_writer::start_tag('form', ['method' => 'get', 'class' => 'mb-3']);
        $toolbar .= \html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $cm->id]);
        $toolbar .= \html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'submissions']);
        $toolbar .= \html_writer::start_div('d-flex flex-wrap gap-2 align-items-end');
        $toolbar .= \html_writer::tag('label', get_string('searchusers', 'groupassign'), ['for' => 'groupassign-search']);
        $toolbar .= \html_writer::empty_tag('input', [
            'type' => 'text',
            'name' => 'search',
            'id' => 'groupassign-search',
            'value' => s($search),
            'class' => 'form-control',
            'style' => 'max-width: 18rem;',
        ]);
        $toolbar .= \html_writer::tag('label', get_string('status', 'groupassign'), ['for' => 'groupassign-statusfilter']);
        $toolbar .= \html_writer::select(
            [
                'all' => get_string('all'),
                'submitted' => get_string('submissionstatus:submitted', 'groupassign'),
                'late' => get_string('late', 'groupassign'),
                'notsubmitted' => get_string('notsubmitted', 'groupassign'),
            ],
            'statusfilter',
            $statusfilter,
            false,
            ['id' => 'groupassign-statusfilter', 'class' => 'form-select']
        );
        $toolbar .= \html_writer::tag('label', get_string('show'), ['for' => 'groupassign-perpage']);
        $toolbar .= \html_writer::select(
            [10 => 10, 20 => 20, 50 => 50, 100 => 100],
            'perpage',
            $perpage,
            false,
            ['id' => 'groupassign-perpage', 'class' => 'form-select']
        );
        $toolbar .= \html_writer::empty_tag(
            'input',
            ['type' => 'submit', 'value' => get_string('filter'), 'class' => 'btn btn-secondary']
        );
        $toolbar .= \html_writer::end_div();
        $toolbar .= \html_writer::end_tag('form');
        echo $toolbar;

        $rows = [];
        foreach ($groups as $group) {
            $members = $membersbygroup[$group->id] ?? [];
            $submission = $submissionsbygroup[$group->id] ?? null;
            $grade = $gradesbygroup[$group->id] ?? null;
            foreach ($members as $member) {
                $namematch = $search === '' || stripos(fullname($member), $search) !== false
                    || stripos($member->email, $search) !== false;
                $status = \mod_groupassign\local\workflow_manager::status_label($submission, $groupassign);
                $statusmatch = $statusfilter === 'all'
                    || ($statusfilter === 'submitted' && (int)($submission->status ?? -1) === GROUPASSIGN_STATUS_SUBMITTED)
                    || ($statusfilter === 'late'
                        && \mod_groupassign\local\workflow_manager::submission_late($groupassign, $submission))
                    || ($statusfilter === 'notsubmitted' && !$submission);
                if (!$namematch || !$statusmatch) {
                    continue;
                }

                $rows[] = [
                    'member' => $member,
                    'group' => $group,
                    'submission' => $submission,
                    'grade' => $grade,
                    'status' => $status,
                ];
            }
        }

        $totalrows = count($rows);
        $pagedrows = array_slice($rows, $page * $perpage, $perpage);

        $table = new \html_table();
        $table->attributes['class'] = 'generaltable groupassign-submissions-table';
        $table->head = [
            \html_writer::checkbox('selectall', 1, false, '', ['disabled' => 'disabled']),
            get_string('fullnameuser'),
            get_string('email'),
            get_string('group'),
            get_string('status', 'groupassign'),
            get_string('grade', 'groupassign'),
            get_string('timemodified', 'groupassign') . ' (' . get_string('submission', 'groupassign') . ')',
            get_string('submissionfiles', 'groupassign'),
            get_string('submissioncomments', 'groupassign'),
            get_string('timemodified', 'groupassign') . ' (' . get_string('feedback') . ')',
            get_string('feedbackcomments', 'groupassign'),
            get_string('grade', 'groupassign'),
            get_string('actions', 'groupassign'),
        ];

        foreach ($pagedrows as $row) {
            $member = $row['member'];
            $group = $row['group'];
            $submission = $row['submission'];
            $grade = $row['grade'];
            $status = $row['status'];
            $gradeaction = \html_writer::link(new \moodle_url('/mod/groupassign/view.php', [
                'id' => $cm->id,
                'action' => 'grade',
                'groupid' => $group->id,
            ]), get_string('gradebutton', 'groupassign'), ['class' => 'btn btn-sm btn-primary']);
            $editsubmissionaction = \html_writer::link(new \moodle_url('/mod/groupassign/view.php', [
                'id' => $cm->id,
                'action' => 'grade',
                'groupid' => $group->id,
            ]), get_string('editsubmission', 'groupassign'));
            $grantextensionaction = \html_writer::link(new \moodle_url('/mod/groupassign/view.php', [
                'id' => $cm->id,
                'action' => 'grade',
                'groupid' => $group->id,
            ]), get_string('grantsubmissionextension', 'groupassign'));
            $nudgeaction = \html_writer::link(
                new \moodle_url('/message/index.php', ['user2' => $member->id]),
                get_string('nudge', 'groupassign'),
                ['class' => 'btn btn-sm btn-outline-secondary']
            );
            $actionmenu = \html_writer::start_tag('details', ['class' => 'groupassign-row-actions'])
                . \html_writer::tag('summary', '...')
                . \html_writer::div(
                    \html_writer::div($gradeaction, 'mb-1')
                    . \html_writer::div($editsubmissionaction, 'mb-1')
                    . \html_writer::div($grantextensionaction, 'mb-1')
                    . \html_writer::div($nudgeaction),
                    'small'
                )
                . \html_writer::end_tag('details');
            $table->data[] = [
                \html_writer::checkbox('selectedusers[]', $member->id, false),
                fullname($member),
                s($member->email),
                format_string($group->name),
                $status,
                \mod_groupassign\local\workflow_manager::grade_label($groupassign, $grade)
                    . \html_writer::div($gradeaction, 'mt-1'),
                $submission && !empty($submission->timemodified) ? userdate($submission->timemodified) : '-',
                $this->render_submission_content($submission, $context),
                '-',
                $grade && !empty($grade->timemodified) ? userdate($grade->timemodified) : '-',
                ($grade && !empty($grade->feedback)) ? format_text($grade->feedback, $grade->feedbackformat) : '-',
                \mod_groupassign\local\workflow_manager::grade_label($groupassign, $grade)
                    . \html_writer::div($nudgeaction, 'mt-1'),
                $actionmenu,
            ];
        }

        echo \html_writer::div(
            \html_writer::checkbox(
                'quickgrading',
                1,
                false,
                get_string('quickgrading', 'groupassign'),
                ['disabled' => 'disabled']
            ) . ' ' .
            \html_writer::tag(
                'button',
                get_string('actions', 'groupassign'),
                ['class' => 'btn btn-outline-secondary btn-sm', 'type' => 'button', 'disabled' => 'disabled']
            ),
            'mb-2 d-flex gap-2 align-items-center'
        );
        echo \html_writer::table($table);
        $pagingurl = new \moodle_url('/mod/groupassign/view.php', [
            'id' => $cm->id,
            'action' => 'submissions',
            'search' => $search,
            'statusfilter' => $statusfilter,
            'perpage' => $perpage,
        ]);
        echo $this->output->paging_bar($totalrows, $page, $perpage, $pagingurl);
    }

    /**
     * Render grade view.
     *
     * @param mixed $groupassign
     * @param mixed $cm
     * @param mixed $context
     * @param int $groupid
     * @param array $editoroptions
     * @param array|null $prepared
     */
    public function render_grade_view(
        $groupassign,
        $cm,
        $context,
        int $groupid,
        array $editoroptions,
        ?array $prepared = null
    ): void {

        require_capability('mod/groupassign:grade', $context);
        $prepared = $prepared ?? \mod_groupassign\local\form_controller::prepare_grade_form(
            $groupassign,
            $cm,
            $context,
            $groupid,
            $editoroptions
        );
        if ($prepared === null) {
            echo $this->output->notification(get_string('invalidgroupid', 'groupassign'), 'error');
            return;
        }

        $mform = $prepared['mform'];
        $group = $prepared['group'];
        $members = $prepared['members'];
        $submission = $prepared['submission'];

        echo \html_writer::link(
            new \moodle_url('/mod/groupassign/view.php', ['id' => $cm->id, 'action' => 'submissions']),
            get_string('submissions', 'groupassign'),
            ['class' => 'btn btn-secondary mb-3']
        );
        echo $this->output->heading(format_string($group->name), 3);
        echo \html_writer::div(get_string('members', 'groupassign') . ': ' .
            ($members ? implode(', ', array_map('fullname', $members)) : '-'), 'mb-3');
        echo $this->output->heading(get_string('submission', 'groupassign'), 4);
        echo \html_writer::div($this->render_submission_content($submission, $context), 'card card-body mb-4');
        $mform->display();
    }

    /**
     * Render peer review view.
     *
     * @param mixed $groupassign
     * @param mixed $cm
     * @param mixed $context
     * @param array|null $prepared
     */
    public function render_peer_review_view($groupassign, $cm, $context, ?array $prepared = null): void {

        require_capability('mod/groupassign:join', $context);
        $prepared = $prepared ?? \mod_groupassign\local\form_controller::prepare_peer_review_form($groupassign, $cm, $context);
        if ($prepared === null) {
            echo $this->output->notification(get_string('peerreviewdisabled', 'groupassign'), 'info');
            return;
        }

        $mform = $prepared['mform'];
        $group = $prepared['group'];

        echo \html_writer::link(
            new \moodle_url('/mod/groupassign/view.php', ['id' => $cm->id]),
            get_string('modulename', 'groupassign'),
            ['class' => 'btn btn-secondary mb-3']
        );
        echo $this->output->heading(get_string('peerreview', 'groupassign'), 3);
        echo \html_writer::div(
            get_string('currentgroup', 'groupassign') . ': ' . format_string($group->name),
            'alert alert-info'
        );
        $mform->display();
    }

    /**
     * Render group submission form.
     *
     * @param mixed $groupassign
     * @param mixed $cm
     * @param mixed $context
     * @param mixed $group
     * @param mixed $editoroptions
     * @param mixed $fileoptions
     * @param array|null $prepared
     */
    public function render_group_submission_form(
        $groupassign,
        $cm,
        $context,
        $group,
        $editoroptions,
        $fileoptions,
        ?array $prepared = null
    ): void {

        $prepared = $prepared ?? \mod_groupassign\local\form_controller::prepare_group_submission_form(
            $groupassign,
            $cm,
            $context,
            $group,
            $editoroptions,
            $fileoptions
        );
        $mform = $prepared['mform'];
        $submission = $prepared['submission'];
        $submissionsopen = $prepared['submissionsopen'];

        echo $this->output->heading(get_string('submission', 'groupassign'), 3);
        if ($submission) {
            echo \html_writer::div(
                get_string('status', 'groupassign') . ': ' .
                \mod_groupassign\local\workflow_manager::status_label($submission, $groupassign),
                'alert alert-info'
            );
        }
        $notice = \mod_groupassign\local\workflow_manager::submission_window_notice($groupassign);
        if ($notice !== '') {
            echo \html_writer::div($notice, 'alert alert-light border');
        }
        if ($submissionsopen) {
            $mform->display();
        } else {
            echo $this->output->notification(get_string('submissionsclosed', 'groupassign'), 'warning');
        }
    }

    /**
     * Render student view.
     *
     * @param mixed $groupassign
     * @param mixed $cm
     * @param mixed $context
     * @param mixed $editoroptions
     * @param mixed $fileoptions
     * @param array|null $submissionform
     */
    public function render_student_view(
        $groupassign,
        $cm,
        $context,
        $editoroptions,
        $fileoptions,
        ?array $submissionform = null
    ): void {
        global $USER;

        $groups = \mod_groupassign\local\workflow_manager::get_groups($groupassign);
        $mygroups = \mod_groupassign\local\workflow_manager::get_my_groups($groupassign, $USER->id);
        $mygroupids = array_map(fn($group) => (int)$group->id, $mygroups);
        $isopen = \mod_groupassign\local\workflow_manager::selection_open($groupassign);
        $haslockedgroup = \mod_groupassign\local\workflow_manager::user_has_submitted_group($groupassign, $USER->id);

        echo $this->render_activity_details($groupassign, $cm, $context);
        echo $this->output->heading(get_string('choosegroup', 'groupassign'), 3);
        echo \html_writer::div(
            \mod_groupassign\local\workflow_manager::selection_window_notice($groupassign),
            $isopen ? 'alert alert-info' : 'alert alert-warning'
        );

        if ($mygroups) {
            $currentgroup = reset($mygroups);
            echo \html_writer::div(get_string('currentgroup', 'groupassign') . ': ' .
                implode(', ', array_map(fn($group) => format_string($group->name), $mygroups)), 'alert alert-success');
            $this->render_group_submission_form(
                $groupassign,
                $cm,
                $context,
                $currentgroup,
                $editoroptions,
                $fileoptions,
                $submissionform
            );
            if (!empty($groupassign->peerenabled)) {
                $peerstatus = \mod_groupassign\local\peer_review_manager::peer_review_status_label(
                    $groupassign,
                    $currentgroup->id,
                    $USER->id
                );
                echo \html_writer::div(
                    \html_writer::span($peerstatus, 'me-3') .
                    \html_writer::link(new \moodle_url('/mod/groupassign/view.php', [
                        'id' => $cm->id,
                        'action' => 'peerreview',
                    ]), get_string('peerreview', 'groupassign'), ['class' => 'btn btn-outline-primary']),
                    'alert alert-light border'
                );
            }
        } else {
            echo \html_writer::div(get_string('nogroup', 'groupassign'), 'alert alert-info');
        }

        if (!$groups) {
            echo $this->output->notification(get_string('nogroups', 'groupassign'), 'info');
        }

        foreach ($groups as $group) {
            $count = \mod_groupassign\local\workflow_manager::member_count($group->id);
            $iscurrent = in_array((int)$group->id, $mygroupids, true);
            $isfull = !empty($groupassign->maxmembers) && $count >= $groupassign->maxmembers;
            if (!empty($groupassign->hidefullgroups) && $isfull && !$iscurrent) {
                continue;
            }
            $classes = 'card mb-3 groupassign-group-card' . ($iscurrent ? ' is-current' : '');
            echo \html_writer::start_div($classes);
            echo \html_writer::start_div('card-body');
            echo \html_writer::start_div('d-flex justify-content-between align-items-start gap-3');
            echo \html_writer::div($this->output->heading(format_string($group->name), 4, 'h4 mb-1') .
                \html_writer::div($this->group_status_badges($groupassign, $count, $iscurrent), 'mb-2'));
            echo \html_writer::div(get_string('members', 'groupassign') . ': ' .
                \mod_groupassign\local\workflow_manager::capacity_label($groupassign, $count), 'fw-semibold text-nowrap');
            echo \html_writer::end_div();
            if (!empty($group->description)) {
                echo \html_writer::div(format_text($group->description, $group->descriptionformat), 'mb-2');
            }
            if (!empty($groupassign->showmembers)) {
                $members = groups_get_members($group->id, 'u.*', 'u.lastname, u.firstname');
                echo \html_writer::div($members ? implode(', ', array_map('fullname', $members)) : '-', 'mb-2 text-muted');
            }

            $actions = [];
            if (
                $isopen && $groupassign->allowstudentjoin && !$iscurrent
                    && !$isfull && !$haslockedgroup
            ) {
                $label = $mygroups ? get_string('switchgroup', 'groupassign') : get_string('join', 'groupassign');
                $actions[] = $this->action_button($cm, 'join', (int)$group->id, $label, 'btn btn-primary me-2');
            } else if ($isopen && $groupassign->allowstudentjoin && !$iscurrent && $isfull) {
                $actions[] = \html_writer::span(get_string('groupfull', 'groupassign'), 'btn btn-secondary disabled me-2');
            }
            if ($isopen && $groupassign->allowstudentleave && $iscurrent && !$haslockedgroup) {
                $actions[] = $this->action_button(
                    $cm,
                    'leave',
                    (int)$group->id,
                    get_string('leave', 'groupassign'),
                    'btn btn-secondary me-2'
                );
            } else if ($iscurrent && $haslockedgroup) {
                $actions[] = \html_writer::span(
                    get_string('groupmembershiplocked', 'groupassign'),
                    'badge bg-light text-dark border'
                );
            }
            echo $actions ? \html_writer::div(implode(' ', $actions), 'mt-3') : '';
            echo \html_writer::end_div();
            echo \html_writer::end_div();
        }

        if ($isopen && $groupassign->allowstudentcreate) {
            echo \html_writer::start_div('card mt-4');
            echo \html_writer::start_div('card-body');
            echo $this->output->heading(get_string('creategroup', 'groupassign'), 4);
            echo \html_writer::start_tag('form', ['method' => 'post', 'action' => new \moodle_url('/mod/groupassign/view.php')]);
            echo \html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $cm->id]);
            echo \html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'create']);
            echo \html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
            echo \html_writer::tag('label', get_string('groupname', 'groupassign'), ['for' => 'groupassign_groupname']);
            echo \html_writer::empty_tag('input', [
                'type' => 'text',
                'name' => 'groupname',
                'id' => 'groupassign_groupname',
                'class' => 'form-control mb-3',
                'required' => 'required',
            ]);
            if ($groupassign->allowstudentdescription) {
                echo \html_writer::tag(
                    'label',
                    get_string('groupdescription', 'groupassign'),
                    ['for' => 'groupassign_groupdescription']
                );
                echo \html_writer::tag('textarea', '', [
                    'name' => 'groupdescription',
                    'id' => 'groupassign_groupdescription',
                    'class' => 'form-control mb-3',
                    'rows' => 3,
                ]);
            }
            echo \html_writer::empty_tag('input', [
                'type' => 'submit',
                'value' => get_string('creategroup', 'groupassign'),
                'class' => 'btn btn-primary',
            ]);
            echo \html_writer::end_tag('form');
            echo \html_writer::end_div();
            echo \html_writer::end_div();
        }
    }
}
