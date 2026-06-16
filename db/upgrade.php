<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

function xmldb_groupassign_upgrade($oldversion) {
    global $DB, $CFG;

    $dbman = $DB->get_manager();

    if ($oldversion < 2026052601) {
        $table = new xmldb_table('groupassign');
        $fields = [
            new xmldb_field('submissiononlinetext', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1', 'gradingduedate'),
            new xmldb_field('submissionfile', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1', 'submissiononlinetext'),
            new xmldb_field('maxfiles', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '5', 'submissionfile'),
            new xmldb_field('maxbytes', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'maxfiles'),
        ];
        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        $submissiontable = new xmldb_table('groupassign_submissions');
        if (!$dbman->table_exists($submissiontable)) {
            $submissiontable->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $submissiontable->add_field('groupassignid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $submissiontable->add_field('groupid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $submissiontable->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $submissiontable->add_field('submissiontext', XMLDB_TYPE_TEXT, null, null, null);
            $submissiontable->add_field('submissionformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0');
            $submissiontable->add_field('status', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0');
            $submissiontable->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $submissiontable->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $submissiontable->add_field('timesubmitted', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $submissiontable->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $submissiontable->add_key('groupassignid', XMLDB_KEY_FOREIGN, ['groupassignid'], 'groupassign', ['id']);
            $submissiontable->add_index('groupassignid-groupid', XMLDB_INDEX_UNIQUE, ['groupassignid', 'groupid']);
            $dbman->create_table($submissiontable);
        }

        upgrade_mod_savepoint(true, 2026052601, 'groupassign');
    }

    if ($oldversion < 2026052602) {
        $gradetable = new xmldb_table('groupassign_grades');
        if (!$dbman->table_exists($gradetable)) {
            $gradetable->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $gradetable->add_field('groupassignid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $gradetable->add_field('groupid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $gradetable->add_field('graderid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $gradetable->add_field('grade', XMLDB_TYPE_NUMBER, '10, 5', null, null);
            $gradetable->add_field('feedback', XMLDB_TYPE_TEXT, null, null, null);
            $gradetable->add_field('feedbackformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0');
            $gradetable->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $gradetable->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $gradetable->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $gradetable->add_key('groupassignid', XMLDB_KEY_FOREIGN, ['groupassignid'], 'groupassign', ['id']);
            $gradetable->add_index('groupassignid-groupid', XMLDB_INDEX_UNIQUE, ['groupassignid', 'groupid']);
            $dbman->create_table($gradetable);
        }

        upgrade_mod_savepoint(true, 2026052602, 'groupassign');
    }

    if ($oldversion < 2026052800) {
        $table = new xmldb_table('groupassign');
        $fields = [
            new xmldb_field('hidefullgroups', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'allowstudentdescription'),
            new xmldb_field('showmembers', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1', 'hidefullgroups'),
        ];
        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        upgrade_mod_savepoint(true, 2026052800, 'groupassign');
    }

    if ($oldversion < 2026052801) {
        $table = new xmldb_table('groupassign');
        $fields = [
            new xmldb_field('groupnameprefix', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, '', 'numgroups'),
            new xmldb_field('groupnamesuffix', XMLDB_TYPE_CHAR, '16', null, XMLDB_NOTNULL, null, 'numbers', 'groupnameprefix'),
        ];
        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        upgrade_mod_savepoint(true, 2026052801, 'groupassign');
    }

    if ($oldversion < 2026052802) {
        $table = new xmldb_table('groupassign');
        $fields = [
            new xmldb_field('peerenabled', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'selectionclose'),
            new xmldb_field('peerselfassessment', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1', 'peerenabled'),
            new xmldb_field('peercomments', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1', 'peerselfassessment'),
            new xmldb_field('peeranonymous', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1', 'peercomments'),
            new xmldb_field('peerstudentresponse', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'peeranonymous'),
        ];
        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        $criteriatable = new xmldb_table('groupassign_peercriteria');
        if (!$dbman->table_exists($criteriatable)) {
            $criteriatable->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $criteriatable->add_field('groupassignid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $criteriatable->add_field('description', XMLDB_TYPE_TEXT, null, null, null);
            $criteriatable->add_field('descriptionformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '1');
            $criteriatable->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $criteriatable->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $criteriatable->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $criteriatable->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $criteriatable->add_key('groupassignid', XMLDB_KEY_FOREIGN, ['groupassignid'], 'groupassign', ['id']);
            $criteriatable->add_index('groupassignid-sortorder', XMLDB_INDEX_NOTUNIQUE, ['groupassignid', 'sortorder']);
            $dbman->create_table($criteriatable);
        }

        upgrade_mod_savepoint(true, 2026052802, 'groupassign');
    }

    if ($oldversion < 2026052803) {
        $defaults = [
            get_string('peercriterion1', 'groupassign'),
            get_string('peercriterion2', 'groupassign'),
            get_string('peercriterion3', 'groupassign'),
            get_string('peercriterion4', 'groupassign'),
            get_string('peercriterion5', 'groupassign'),
        ];
        $now = time();
        $instances = $DB->get_records('groupassign', null, '', 'id');
        foreach ($instances as $instance) {
            if ($DB->record_exists('groupassign_peercriteria', ['groupassignid' => $instance->id])) {
                continue;
            }
            foreach ($defaults as $sortorder => $criterion) {
                $DB->insert_record('groupassign_peercriteria', (object)[
                    'groupassignid' => $instance->id,
                    'description' => $criterion,
                    'descriptionformat' => FORMAT_HTML,
                    'sortorder' => $sortorder,
                    'timecreated' => $now,
                    'timemodified' => $now,
                ]);
            }
        }

        upgrade_mod_savepoint(true, 2026052803, 'groupassign');
    }

    if ($oldversion < 2026052804) {
        $reviewtable = new xmldb_table('groupassign_peerreviews');
        if (!$dbman->table_exists($reviewtable)) {
            $reviewtable->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $reviewtable->add_field('groupassignid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $reviewtable->add_field('groupid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $reviewtable->add_field('criteriaid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $reviewtable->add_field('reviewerid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $reviewtable->add_field('revieweeid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $reviewtable->add_field('rating', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0');
            $reviewtable->add_field('comment', XMLDB_TYPE_TEXT, null, null, null);
            $reviewtable->add_field('commentformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0');
            $reviewtable->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $reviewtable->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $reviewtable->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $reviewtable->add_key('groupassignid', XMLDB_KEY_FOREIGN, ['groupassignid'], 'groupassign', ['id']);
            $reviewtable->add_key('criteriaid', XMLDB_KEY_FOREIGN, ['criteriaid'], 'groupassign_peercriteria', ['id']);
            $reviewtable->add_index('uniquepeerreview', XMLDB_INDEX_UNIQUE,
                ['groupassignid', 'criteriaid', 'reviewerid', 'revieweeid']);
            $reviewtable->add_index('groupassignid-groupid', XMLDB_INDEX_NOTUNIQUE, ['groupassignid', 'groupid']);
            $reviewtable->add_index('revieweeid', XMLDB_INDEX_NOTUNIQUE, ['revieweeid']);
            $dbman->create_table($reviewtable);
        }

        upgrade_mod_savepoint(true, 2026052804, 'groupassign');
    }

    if ($oldversion < 2026052805) {
        $membergradetable = new xmldb_table('groupassign_membergrades');
        if (!$dbman->table_exists($membergradetable)) {
            $membergradetable->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $membergradetable->add_field('groupassignid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $membergradetable->add_field('groupid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $membergradetable->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $membergradetable->add_field('graderid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $membergradetable->add_field('grade', XMLDB_TYPE_NUMBER, '10, 5', null, null);
            $membergradetable->add_field('feedback', XMLDB_TYPE_TEXT, null, null, null);
            $membergradetable->add_field('feedbackformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0');
            $membergradetable->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $membergradetable->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $membergradetable->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $membergradetable->add_key('groupassignid', XMLDB_KEY_FOREIGN, ['groupassignid'], 'groupassign', ['id']);
            $membergradetable->add_index('groupassignid-userid', XMLDB_INDEX_UNIQUE, ['groupassignid', 'userid']);
            $membergradetable->add_index('groupassignid-groupid', XMLDB_INDEX_NOTUNIQUE, ['groupassignid', 'groupid']);
            $dbman->create_table($membergradetable);
        }

        upgrade_mod_savepoint(true, 2026052805, 'groupassign');
    }

    if ($oldversion < 2026053000) {
        $table = new xmldb_table('groupassign');

        $field = new xmldb_field('submissionfiletypes', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, '', 'maxbytes');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('peerrequirejustification', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0',
            'peercomments');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2026053000, 'groupassign');
    }

    if ($oldversion < 2026053001) {
        $table = new xmldb_table('groupassign_peercriteria');

        $field = new xmldb_field('details', XMLDB_TYPE_TEXT, null, null, null, null, null, 'descriptionformat');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('ratingtype', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'fourlevel', 'details');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2026053001, 'groupassign');
    }

    if ($oldversion < 2026053002) {
        $table = new xmldb_table('groupassign');
        $field = new xmldb_field('completionreceivegrade', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0',
            'peerstudentresponse');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2026053002, 'groupassign');
    }

    if ($oldversion < 2026061201) {
        $table = new xmldb_table('groupassign');
        $fields = [
            new xmldb_field('activity', XMLDB_TYPE_TEXT, null, null, null, null, null, 'introformat'),
            new xmldb_field('activityformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '1', 'activity'),
            new xmldb_field('timelimit', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'gradingduedate'),
            new xmldb_field('alwaysshowdescription', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'timelimit'),
            new xmldb_field('submissionattachments', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0',
                'alwaysshowdescription'),
            new xmldb_field('wordlimit', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'submissionfiletypes'),
            new xmldb_field('submissiondrafts', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'wordlimit'),
            new xmldb_field('requiresubmissionstatement', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0',
                'submissiondrafts'),
            new xmldb_field('maxattempts', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '-1',
                'requiresubmissionstatement'),
            new xmldb_field('attemptreopenmethod', XMLDB_TYPE_CHAR, '16', null, XMLDB_NOTNULL, null, 'manual',
                'maxattempts'),
            new xmldb_field('sendnotifications', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1',
                'peerstudentresponse'),
            new xmldb_field('sendlatenotifications', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0',
                'sendnotifications'),
            new xmldb_field('sendstudentnotifications', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '1',
                'sendlatenotifications'),
            new xmldb_field('blindmarking', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0',
                'sendstudentnotifications'),
            new xmldb_field('hidegrader', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'blindmarking'),
            new xmldb_field('markingworkflow', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'hidegrader'),
            new xmldb_field('markingallocation', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0',
                'markingworkflow'),
            new xmldb_field('markinganonymous', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0',
                'markingallocation'),
        ];

        foreach ($fields as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }

        upgrade_mod_savepoint(true, 2026061201, 'groupassign');
    }

    if ($oldversion < 2026061202) {
        $table = new xmldb_table('groupassign_peercriteria');
        $field = new xmldb_field('archived', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0', 'sortorder');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2026061202, 'groupassign');
    }

    if ($oldversion < 2026061605) {
        $table = new xmldb_table('groupassign');
        $legacyfields = [
            'timelimit',
            'alwaysshowdescription',
            'submissionattachments',
            'submissiondrafts',
            'maxattempts',
            'attemptreopenmethod',
            'managedgrouping',
            'peeranonymous',
            'peerstudentresponse',
            'sendnotifications',
            'sendlatenotifications',
            'sendstudentnotifications',
            'blindmarking',
            'hidegrader',
            'markingworkflow',
            'markingallocation',
            'markinganonymous',
            'completionreceivegrade',
        ];

        foreach ($legacyfields as $fieldname) {
            $field = new xmldb_field($fieldname);
            if ($dbman->field_exists($table, $field)) {
                $dbman->drop_field($table, $field);
            }
        }

        upgrade_mod_savepoint(true, 2026061605, 'groupassign');
    }

    if ($oldversion < 2026061606) {
        require_once($CFG->dirroot . '/mod/groupassign/lib.php');
        $groupassignments = $DB->get_records('groupassign');
        foreach ($groupassignments as $groupassign) {
            groupassign_update_calendar($groupassign);
        }

        upgrade_mod_savepoint(true, 2026061606, 'groupassign');
    }

    return true;
}
