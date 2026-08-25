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
 * Group assignment workflow and data-access services.
 *
 * @package    mod_groupassign
 * @copyright  2026 Matthew Darch <matthew.darch.1up@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_groupassign\local;

/**
 * Group assignment workflow and data-access services.
 */
class workflow_manager {
    /**
     * Selection open.
     *
     * @param mixed $groupassign
     * @return bool
     */
    public static function selection_open($groupassign): bool {
        $now = time();
        if (!empty($groupassign->selectionopen) && $now < $groupassign->selectionopen) {
            return false;
        }
        if (!empty($groupassign->selectionclose) && $now > $groupassign->selectionclose) {
            return false;
        }
        return true;
    }

    /**
     * Submission open.
     *
     * @param mixed $groupassign
     * @return bool
     */
    public static function submission_open($groupassign): bool {
        $now = time();
        if (!empty($groupassign->allowsubmissionsfromdate) && $now < $groupassign->allowsubmissionsfromdate) {
            return false;
        }
        if (!empty($groupassign->cutoffdate) && $now > $groupassign->cutoffdate) {
            return false;
        }
        return true;
    }

    /**
     * Submission window notice.
     *
     * @param mixed $groupassign
     * @return string
     */
    public static function submission_window_notice($groupassign): string {
        $parts = [];
        if (!empty($groupassign->allowsubmissionsfromdate)) {
            $parts[] = get_string('allowsubmissionsfromdate', 'groupassign') . ': ' .
                userdate($groupassign->allowsubmissionsfromdate);
        }
        if (!empty($groupassign->duedate)) {
            $parts[] = get_string('duedate', 'groupassign') . ': ' . userdate($groupassign->duedate);
        }
        if (!empty($groupassign->cutoffdate)) {
            $parts[] = get_string('cutoffdate', 'groupassign') . ': ' . userdate($groupassign->cutoffdate);
        }
        return implode(' ', $parts);
    }

    /**
     * Get groups.
     *
     * @param mixed $groupassign
     * @return array
     */
    public static function get_groups($groupassign): array {
        if (empty($groupassign->groupingid)) {
            return [];
        }
        return groups_get_all_groups($groupassign->course, 0, $groupassign->groupingid, 'g.*', 'g.name ASC') ?: [];
    }

    /**
     * Get my groups.
     *
     * @param mixed $groupassign
     * @param int $userid
     * @return array
     */
    public static function get_my_groups($groupassign, int $userid): array {
        if (empty($groupassign->groupingid)) {
            return [];
        }
        return groups_get_all_groups($groupassign->course, $userid, $groupassign->groupingid, 'g.*', 'g.name ASC') ?: [];
    }

    /**
     * Group ids.
     *
     * @param array $groups
     * @return array
     */
    public static function group_ids(array $groups): array {
        return array_map(static fn($group) => (int)$group->id, $groups);
    }

    /**
     * Group members map.
     *
     * @param array $groups
     * @return array
     */
    public static function group_members_map(array $groups): array {
        global $DB;

        $members = [];
        $groupids = self::group_ids($groups);
        if (!$groupids) {
            return $members;
        }

        [$insql, $params] = $DB->get_in_or_equal($groupids, SQL_PARAMS_NAMED, 'groupid');
        $sql = "SELECT gm.id AS membershipid, gm.groupid AS gagroupid, u.*
                  FROM {groups_members} gm
                  JOIN {user} u ON u.id = gm.userid
                 WHERE gm.groupid $insql
              ORDER BY gm.groupid, u.lastname, u.firstname";
        $records = $DB->get_recordset_sql($sql, $params);
        foreach ($records as $record) {
            $groupid = (int)$record->gagroupid;
            unset($record->membershipid, $record->gagroupid);
            $members[$groupid][$record->id] = $record;
        }
        $records->close();

        return $members;
    }

    /**
     * Records by group.
     *
     * @param string $table
     * @param int $groupassignid
     * @param array $groups
     * @return array
     */
    public static function records_by_group(string $table, int $groupassignid, array $groups): array {
        global $DB;

        $recordsbygroup = [];
        $groupids = self::group_ids($groups);
        if (!$groupids) {
            return $recordsbygroup;
        }

        [$insql, $params] = $DB->get_in_or_equal($groupids, SQL_PARAMS_NAMED, 'groupid');
        $params['groupassignid'] = $groupassignid;
        $records = $DB->get_records_select($table, "groupassignid = :groupassignid AND groupid $insql", $params);
        foreach ($records as $record) {
            $recordsbygroup[(int)$record->groupid] = $record;
        }

        return $recordsbygroup;
    }

    /**
     * Member count.
     *
     * @param int $groupid
     * @return int
     */
    public static function member_count(int $groupid): int {
        return count(groups_get_members($groupid, 'u.id'));
    }

    /**
     * Capacity label.
     *
     * @param mixed $groupassign
     * @param int $count
     * @return string
     */
    public static function capacity_label($groupassign, int $count): string {
        if (empty($groupassign->maxmembers)) {
            return (string)$count;
        }
        return $count . ' / ' . $groupassign->maxmembers;
    }

    /**
     * Selection window notice.
     *
     * @param mixed $groupassign
     * @return string
     */
    public static function selection_window_notice($groupassign): string {
        $parts = [];
        if (self::selection_open($groupassign)) {
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

    /**
     * Remove user from activity groups.
     *
     * @param mixed $groupassign
     * @param int $userid
     */
    public static function remove_user_from_activity_groups($groupassign, int $userid): void {
        foreach (self::get_my_groups($groupassign, $userid) as $group) {
            groups_remove_member($group->id, $userid);
        }
    }

    /**
     * Get submission.
     *
     * @param mixed $groupassign
     * @param int $groupid
     * @return mixed
     */
    public static function get_submission($groupassign, int $groupid) {
        global $DB;
        return $DB->get_record('groupassign_submissions', [
            'groupassignid' => $groupassign->id,
            'groupid' => $groupid,
        ]);
    }

    /**
     * Group has submission.
     *
     * @param mixed $groupassign
     * @param int $groupid
     * @return bool
     */
    public static function group_has_submission($groupassign, int $groupid): bool {
        return (bool)self::get_submission($groupassign, $groupid);
    }

    /**
     * User has submitted group.
     *
     * @param mixed $groupassign
     * @param int $userid
     * @return bool
     */
    public static function user_has_submitted_group($groupassign, int $userid): bool {
        foreach (self::get_my_groups($groupassign, $userid) as $group) {
            if (self::group_has_submission($groupassign, (int)$group->id)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get grade.
     *
     * @param mixed $groupassign
     * @param int $groupid
     * @return mixed
     */
    public static function get_grade($groupassign, int $groupid) {
        global $DB;
        return $DB->get_record('groupassign_grades', [
            'groupassignid' => $groupassign->id,
            'groupid' => $groupid,
        ]);
    }

    /**
     * Submission late.
     *
     * @param mixed $groupassign
     * @param mixed $submission
     * @return bool
     */
    public static function submission_late($groupassign, $submission): bool {
        return $submission
            && !empty($groupassign->duedate)
            && !empty($submission->timesubmitted)
            && (int)$submission->timesubmitted > (int)$groupassign->duedate;
    }

    /**
     * Status label.
     *
     * @param mixed $submission
     * @param mixed $groupassign
     * @return string
     */
    public static function status_label($submission, $groupassign = null): string {
        if (!$submission) {
            return get_string('notsubmitted', 'groupassign');
        }
        $status = (int)$submission->status === GROUPASSIGN_STATUS_SUBMITTED
            ? get_string('submissionstatus:submitted', 'groupassign')
            : get_string('submissionstatus:draft', 'groupassign');
        if ($groupassign && self::submission_late($groupassign, $submission)) {
            $status .= ' - ' . get_string('late', 'groupassign');
        }
        return $status;
    }

    /**
     * Grade label.
     *
     * @param mixed $groupassign
     * @param mixed $grade
     * @return string
     */
    public static function grade_label($groupassign, $grade): string {
        if (!$grade || $grade->grade === null) {
            return '-';
        }
        if ((int)$groupassign->grade < 0) {
            $scale = \grade_scale::fetch(['id' => abs((int)$groupassign->grade)]);
            if ($scale) {
                $items = $scale->load_items();
                $index = (int)$grade->grade - 1;
                return $items[$index] ?? format_float($grade->grade, 0);
            }
            return format_float($grade->grade, 0);
        }
        if ((int)$groupassign->grade === 0) {
            return '-';
        }
        return format_float($grade->grade, 2) . ' / ' . format_float($groupassign->grade, 2);
    }

    /**
     * Submitted grade value.
     *
     * @param mixed $value
     * @param mixed $groupassign
     * @return float|null
     */
    public static function submitted_grade_value($value, $groupassign): ?float {
        if (
            (int)$groupassign->grade === 0 || $value === null || $value === ''
                || ((int)$groupassign->grade < 0 && (int)$value === 0)
        ) {
            return null;
        }
        return (int)$groupassign->grade < 0 ? (float)(int)$value : (float)$value;
    }

    /**
     * Setup warnings.
     *
     * @param mixed $groupassign
     * @param array $groups
     * @param int $studentswithoutgroup
     * @return array
     */
    public static function setup_warnings($groupassign, array $groups, int $studentswithoutgroup): array {
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
}
