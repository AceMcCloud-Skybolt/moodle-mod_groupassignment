<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_groupassign\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\helper;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider,
        \core_privacy\local\request\core_userlist_provider {

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
            'groupassignid' => 'privacy:metadata:groupassignid',
            'groupid' => 'privacy:metadata:groupid',
        ], 'privacy:metadata:groupassign_groups');

        $collection->add_subsystem_link('core_files', [], 'privacy:metadata:core_files');

        return $collection;
    }

    public static function get_contexts_for_userid(int $userid): contextlist {
        $params = [
            'modname' => 'groupassign',
            'contextlevel' => CONTEXT_MODULE,
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
        $contextlist->add_from_sql($base . "
                   JOIN {groupassign_membergrades} mg ON mg.groupassignid = ga.id
                  WHERE mg.userid = :memberuserid OR mg.graderid = :membergraderid",
            $params + ['memberuserid' => $userid, 'membergraderid' => $userid]);
        $contextlist->add_from_sql($base . "
                   JOIN {groupassign_peerreviews} pr ON pr.groupassignid = ga.id
                  WHERE pr.reviewerid = :reviewerid OR pr.revieweeid = :revieweeid",
            $params + ['reviewerid' => $userid, 'revieweeid' => $userid]);

        return $contextlist;
    }

    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if (!$context instanceof \context_module) {
            return;
        }

        $params = [
            'cmid' => $context->instanceid,
            'modname' => 'groupassign',
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
                'groupassignid' => $cm->instance,
                'userid' => $user->id,
            ])));
            $data->individualgrades = array_values(self::normalise_records($DB->get_records('groupassign_membergrades', [
                'groupassignid' => $cm->instance,
                'userid' => $user->id,
            ])));
            $data->peerreviewsgiven = array_values(self::normalise_records($DB->get_records('groupassign_peerreviews', [
                'groupassignid' => $cm->instance,
                'reviewerid' => $user->id,
            ])));
            $data->peerreviewsreceived = array_values(self::normalise_records($DB->get_records('groupassign_peerreviews', [
                'groupassignid' => $cm->instance,
                'revieweeid' => $user->id,
            ])));

            writer::with_context($context)->export_data([], $data);
            helper::export_context_files($context, $user);
        }
    }

    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if (!$context instanceof \context_module) {
            return;
        }
        $cm = get_coursemodule_from_id('groupassign', $context->instanceid);
        if (!$cm) {
            return;
        }

        $DB->delete_records('groupassign_submissions', ['groupassignid' => $cm->instance]);
        $DB->delete_records('groupassign_grades', ['groupassignid' => $cm->instance]);
        $DB->delete_records('groupassign_membergrades', ['groupassignid' => $cm->instance]);
        $DB->delete_records('groupassign_peerreviews', ['groupassignid' => $cm->instance]);
    }

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

            $DB->delete_records('groupassign_submissions', ['groupassignid' => $cm->instance, 'userid' => $userid]);
            $DB->delete_records('groupassign_membergrades', ['groupassignid' => $cm->instance, 'userid' => $userid]);
            $DB->delete_records_select('groupassign_peerreviews',
                'groupassignid = :groupassignid AND (reviewerid = :reviewerid OR revieweeid = :revieweeid)', [
                    'groupassignid' => $cm->instance,
                    'reviewerid' => $userid,
                    'revieweeid' => $userid,
                ]);
        }
    }

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

        [$usersql, $params] = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED);
        $params['groupassignid'] = $cm->instance;
        $DB->delete_records_select('groupassign_submissions', "groupassignid = :groupassignid AND userid $usersql", $params);
        $DB->delete_records_select('groupassign_membergrades', "groupassignid = :groupassignid AND userid $usersql", $params);

        [$reviewersql, $reviewerparams] = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED, 'reviewer');
        [$revieweesql, $revieweeparams] = $DB->get_in_or_equal($userlist->get_userids(), SQL_PARAMS_NAMED, 'reviewee');
        $reviewparams = ['groupassignid' => $cm->instance] + $reviewerparams + $revieweeparams;
        $DB->delete_records_select('groupassign_peerreviews',
            "groupassignid = :groupassignid AND (reviewerid $reviewersql OR revieweeid $revieweesql)", $reviewparams);
    }

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
