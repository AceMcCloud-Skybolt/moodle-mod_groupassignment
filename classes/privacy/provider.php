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
 * Provider for mod_groupassign.
 *
 * @package    mod_groupassign
 * @copyright  2026 Matthew Darch <matthew.darch.1up@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_groupassign\privacy;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\helper;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
/**
 * Provider.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Get metadata.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {

        $collection->add_database_table('groupassign_submissions', [
            'groupassignid' => 'privacy:metadata:groupassignid',
            'groupid' => 'privacy:metadata:groupid',
            'userid' => 'privacy:metadata:userid',
            'submissiontext' => 'privacy:metadata:submissiontext',
            'status' => 'privacy:metadata:status',
            'timecreated' => 'privacy:metadata:timecreated',
            'timemodified' => 'privacy:metadata:timemodified',
            'timesubmitted' => 'privacy:metadata:timesubmitted',
        ], 'privacy:metadata:groupassign_submissions');
        $collection->add_database_table('groupassign_grades', [
            'groupassignid' => 'privacy:metadata:groupassignid',
            'groupid' => 'privacy:metadata:groupid',
            'graderid' => 'privacy:metadata:graderid',
            'grade' => 'privacy:metadata:grade',
            'feedback' => 'privacy:metadata:feedback',
            'timecreated' => 'privacy:metadata:timecreated',
            'timemodified' => 'privacy:metadata:timemodified',
        ], 'privacy:metadata:groupassign_grades');
        $collection->add_database_table('groupassign_membergrades', [
            'groupassignid' => 'privacy:metadata:groupassignid',
            'groupid' => 'privacy:metadata:groupid',
            'userid' => 'privacy:metadata:userid',
            'graderid' => 'privacy:metadata:graderid',
            'grade' => 'privacy:metadata:grade',
            'feedback' => 'privacy:metadata:feedback',
            'timecreated' => 'privacy:metadata:timecreated',
            'timemodified' => 'privacy:metadata:timemodified',
        ], 'privacy:metadata:groupassign_membergrades');
        $collection->add_database_table('groupassign_peerreviews', [
            'groupassignid' => 'privacy:metadata:groupassignid',
            'groupid' => 'privacy:metadata:groupid',
            'criteriaid' => 'privacy:metadata:criteriaid',
            'reviewerid' => 'privacy:metadata:reviewerid',
            'revieweeid' => 'privacy:metadata:revieweeid',
            'rating' => 'privacy:metadata:rating',
            'comment' => 'privacy:metadata:comment',
            'timecreated' => 'privacy:metadata:timecreated',
            'timemodified' => 'privacy:metadata:timemodified',
        ], 'privacy:metadata:groupassign_peerreviews');
        $collection->add_database_table('groupassign_groups', [
            'groupassignid' => 'privacy:metadata:groupassignid', 'groupid' => 'privacy:metadata:groupid',
        ], 'privacy:metadata:groupassign_groups');
        $collection->add_subsystem_link('core_files', [], 'privacy:metadata:core_files');
        return $collection;
    }

    /**
     * Get contexts for userid.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {

        $params = [
            'modname' => 'groupassign', 'contextlevel' => CONTEXT_MODULE,
        ];
        $contextlist = new contextlist();
        $base = "SELECT ctx.id
                   FROM {context} ctx
                   JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                   JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                   JOIN {groupassign} ga ON ga.id = cm.instance";
        $contextlist->add_from_sql($base . "
                   JOIN {groupassign_submissions} s ON s.groupassignid = ga.id
                  WHERE s.userid = :submissionuserid", $params + ['submissionuserid' => $userid]);
        $contextlist->add_from_sql($base . "
                   JOIN {groupassign_grades} g ON g.groupassignid = ga.id
                  WHERE g.graderid = :gradegraderid", $params + ['gradegraderid' => $userid]);
        $contextlist->add_from_sql(
            $base . "
                   JOIN {groupassign_membergrades} mg ON mg.groupassignid = ga.id
                  WHERE mg.userid = :memberuserid OR mg.graderid = :membergraderid",
            $params + ['memberuserid' => $userid, 'membergraderid' => $userid]
        );
        $contextlist->add_from_sql(
            $base . "
                   JOIN {groupassign_peerreviews} pr ON pr.groupassignid = ga.id
                  WHERE pr.reviewerid = :reviewerid OR pr.revieweeid = :revieweeid",
            $params + ['reviewerid' => $userid, 'revieweeid' => $userid]
        );
        return $contextlist;
    }

    /**
     * Get users in context.
     *
     * @param userlist $userlist
     * @return mixed
     */
    public static function get_users_in_context(userlist $userlist) {

        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }

        $params = [
            'cmid' => $context->instanceid, 'modname' => 'groupassign',
        ];
        $base = "FROM {course_modules} cm
                 JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                 JOIN {groupassign} ga ON ga.id = cm.instance";
        $userlist->add_from_sql('userid', "SELECT s.userid
                   $base
                   JOIN {groupassign_submissions} s ON s.groupassignid = ga.id
                  WHERE cm.id = :cmid", $params);
        $userlist->add_from_sql('userid', "SELECT g.graderid AS userid
                   $base
                   JOIN {groupassign_grades} g ON g.groupassignid = ga.id
                  WHERE cm.id = :cmid", $params);
        $userlist->add_from_sql('userid', "SELECT mg.userid
                   $base
                   JOIN {groupassign_membergrades} mg ON mg.groupassignid = ga.id
                  WHERE cm.id = :cmid", $params);
        $userlist->add_from_sql('userid', "SELECT mg.graderid AS userid
                   $base
                   JOIN {groupassign_membergrades} mg ON mg.groupassignid = ga.id
                  WHERE cm.id = :cmid", $params);
        $userlist->add_from_sql('userid', "SELECT pr.reviewerid AS userid
                   $base
                   JOIN {groupassign_peerreviews} pr ON pr.groupassignid = ga.id
                  WHERE cm.id = :cmid", $params);
        $userlist->add_from_sql('userid', "SELECT pr.revieweeid AS userid
                   $base
                   JOIN {groupassign_peerreviews} pr ON pr.groupassignid = ga.id
                  WHERE cm.id = :cmid", $params);
    }

    /**
     * Export user data.
     *
     * @param approved_contextlist $contextlist
     * @return mixed
     */
    public static function export_user_data(approved_contextlist $contextlist) {

        global $DB;
        if (!$contextlist->count()) {
            return;
        }

        $user = $contextlist->get_user();
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            $cm = get_coursemodule_from_id('groupassign', $context->instanceid);
            if (!$cm) {
                continue;
            }

            $data = helper::get_context_data($context, $user);
            $data->submissions = array_values(self::normalise_records($DB->get_records('groupassign_submissions', [
                'groupassignid' => $cm->instance, 'userid' => $user->id,
            ])));
            $data->individualgrades = array_values(self::normalise_records($DB->get_records('groupassign_membergrades', [
                        'groupassignid' => $cm->instance, 'userid' => $user->id,
            ])));
            $data->peerreviewsgiven = array_values(self::normalise_records($DB->get_records('groupassign_peerreviews', [
                        'groupassignid' => $cm->instance, 'reviewerid' => $user->id,
            ])));
            $data->peerreviewsreceived = array_values(self::normalise_records($DB->get_records('groupassign_peerreviews', [
                        'groupassignid' => $cm->instance, 'revieweeid' => $user->id,
            ])));
            writer::with_context($context)->export_data([], $data);
            helper::export_context_files($context, $user);
        }
    }

    /**
     * Delete data for all users in context.
     *
     * @param \context $context
     * @return mixed
     */
    public static function delete_data_for_all_users_in_context(\context $context) {

        global $DB;
        if (!$context instanceof \context_module) {
            return;
        }
        $cm = get_coursemodule_from_id('groupassign', $context->instanceid);
        if (!$cm) {
            return;
        }

        $submissionids = $DB->get_fieldset_select(
            'groupassign_submissions',
            'id',
            'groupassignid = :groupassignid',
            ['groupassignid' => $cm->instance]
        );
        self::delete_submission_files($context, $submissionids);
        $DB->delete_records('groupassign_submissions', ['groupassignid' => $cm->instance]);
        $DB->delete_records('groupassign_grades', ['groupassignid' => $cm->instance]);
        $DB->delete_records('groupassign_membergrades', ['groupassignid' => $cm->instance]);
        $DB->delete_records('groupassign_peerreviews', ['groupassignid' => $cm->instance]);
    }

    /**
     * Delete data for user.
     *
     * @param approved_contextlist $contextlist
     * @return mixed
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {

        global $DB;
        if (!$contextlist->count()) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            $cm = get_coursemodule_from_id('groupassign', $context->instanceid);
            if (!$cm) {
                continue;
            }

            $submissionids = $DB->get_fieldset_select(
                'groupassign_submissions',
                'id',
                'groupassignid = :groupassignid AND userid = :userid',
                [
                    'groupassignid' => $cm->instance, 'userid' => $userid,
                ]
            );
            self::delete_submission_files($context, $submissionids);
            $DB->delete_records('groupassign_submissions', ['groupassignid' => $cm->instance, 'userid' => $userid]);
            $DB->delete_records('groupassign_membergrades', ['groupassignid' => $cm->instance, 'userid' => $userid]);
            $DB->delete_records_select(
                'groupassign_peerreviews',
                'groupassignid = :groupassignid AND (reviewerid = :reviewerid OR revieweeid = :revieweeid)',
                [
                    'groupassignid' => $cm->instance, 'reviewerid' => $userid, 'revieweeid' => $userid,
                ]
            );
        }
    }

    /**
     * Delete data for users.
     *
     * @param approved_userlist $userlist
     * @return mixed
     */
    public static function delete_data_for_users(approved_userlist $userlist) {

        global $DB;
        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }
        $cm = get_coursemodule_from_id('groupassign', $context->instanceid);
        if (!$cm) {
            return;
        }

        $userids = $userlist->get_userids();
        if (!$userids) {
            return;
        }

        [$usersql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $params['groupassignid'] = $cm->instance;
        $submissionids = $DB->get_fieldset_select(
            'groupassign_submissions',
            'id',
            "groupassignid = :groupassignid AND userid $usersql",
            $params
        );
        self::delete_submission_files($context, $submissionids);
        $DB->delete_records_select('groupassign_submissions', "groupassignid = :groupassignid AND userid $usersql", $params);
        $DB->delete_records_select('groupassign_membergrades', "groupassignid = :groupassignid AND userid $usersql", $params);
        [$reviewersql, $reviewerparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'reviewer');
        [$revieweesql, $revieweeparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'reviewee');
        $reviewparams = ['groupassignid' => $cm->instance] + $reviewerparams + $revieweeparams;
        $DB->delete_records_select(
            'groupassign_peerreviews',
            "groupassignid = :groupassignid AND (reviewerid $reviewersql OR revieweeid $revieweesql)",
            $reviewparams
        );
    }

    /**
     * Delete submission files.
     *
     * @param \context_module $context
     * @param array $submissionids
     */
    protected static function delete_submission_files(\context_module $context, array $submissionids): void {

        if (!$submissionids) {
            return;
        }

        $fs = get_file_storage();
        foreach ($submissionids as $submissionid) {
            $fs->delete_area_files($context->id, 'mod_groupassign', 'submission', $submissionid);
        }
    }

    /**
     * Normalise records.
     *
     * @param array $records
     * @return array
     */
    protected static function normalise_records(array $records): array {

        foreach ($records as $record) {
            foreach (['timecreated', 'timemodified', 'timesubmitted'] as $field) {
                if (!empty($record->{$field})) {
                    $record->{$field} = transform::datetime($record->{$field});
                }
            }
        }

        return $records;
    }
}
