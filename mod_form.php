<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/groupassign/lib.php');

class mod_groupassign_mod_form extends moodleform_mod {

    public function definition() {
        global $CFG, $COURSE, $PAGE;

        $mform = $this->_form;
        $PAGE->requires->css('/mod/groupassign/styles.css');

        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('assignmentname', 'assign'), ['size' => '64']);
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 1333), 'maxlength', 1333, 'client');
        $this->standard_intro_elements(get_string('description', 'assign'));

        $mform->addElement('editor', 'activityeditor', get_string('activityeditor', 'assign'), ['rows' => 10], [
            'maxfiles' => EDITOR_UNLIMITED_FILES,
            'noclean' => true,
            'context' => $this->context,
            'subdirs' => true,
        ]);
        $mform->addHelpButton('activityeditor', 'activityeditor', 'assign');
        $mform->setType('activityeditor', PARAM_RAW);

        $mform->addElement('filemanager', 'introattachments', get_string('introattachments', 'assign'), null, [
            'subdirs' => 0,
            'maxbytes' => $COURSE->maxbytes,
        ]);
        $mform->addHelpButton('introattachments', 'introattachments', 'assign');

        $mform->addElement('header', 'availability', get_string('availability', 'assign'));
        $mform->setExpanded('availability', true);
        $mform->addElement('date_time_selector', 'allowsubmissionsfromdate',
            get_string('allowsubmissionsfromdate', 'assign'), ['optional' => true]);
        $mform->addHelpButton('allowsubmissionsfromdate', 'allowsubmissionsfromdate', 'assign');
        $mform->addElement('date_time_selector', 'duedate', get_string('duedate', 'assign'), ['optional' => true]);
        $mform->addHelpButton('duedate', 'duedate', 'assign');
        $mform->addElement('date_time_selector', 'cutoffdate', get_string('cutoffdate', 'assign'), ['optional' => true]);
        $mform->addHelpButton('cutoffdate', 'cutoffdate', 'assign');
        $mform->addElement('date_time_selector', 'gradingduedate',
            get_string('gradingduedate', 'assign'), ['optional' => true]);
        $mform->addHelpButton('gradingduedate', 'gradingduedate', 'assign');

        $mform->addElement('header', 'submissiontypes', get_string('submissiontypes', 'assign'));
        $submissiontypegroup = [];
        $submissiontypegroup[] = $mform->createElement('advcheckbox', 'submissiononlinetext',
            get_string('onlinetext', 'assignsubmission_onlinetext'), '', [], [0, 1]);
        $submissiontypegroup[] = $mform->createElement('advcheckbox', 'submissionfile',
            get_string('file', 'assignsubmission_file'), '', [], [0, 1]);
        $mform->addGroup($submissiontypegroup, 'submissiontypesgroup', get_string('submissiontypes', 'assign'), ' ', false);
        $mform->setDefault('submissiononlinetext', 1);
        $mform->setDefault('submissionfile', 1);
        $mform->addElement('select', 'maxfiles', get_string('maxfiles', 'assignsubmission_file'),
            array_combine(range(1, 20), range(1, 20)));
        $mform->setDefault('maxfiles', 5);
        $mform->hideIf('maxfiles', 'submissionfile', 'notchecked');
        $choices = get_max_upload_sizes($CFG->maxbytes, $COURSE->maxbytes, 0);
        $mform->addElement('select', 'maxbytes', get_string('maximumsubmissionsize', 'assignsubmission_file'), $choices);
        $mform->setDefault('maxbytes', $COURSE->maxbytes);
        $mform->hideIf('maxbytes', 'submissionfile', 'notchecked');
        $mform->addElement('text', 'submissionfiletypes', get_string('submissionfiletypes', 'groupassign'), ['size' => 64]);
        $mform->setType('submissionfiletypes', PARAM_TEXT);
        $mform->addHelpButton('submissionfiletypes', 'submissionfiletypes', 'groupassign');
        $mform->hideIf('submissionfiletypes', 'submissionfile', 'notchecked');
        $mform->addElement('text', 'wordlimit', get_string('wordlimit', 'assignsubmission_onlinetext'), ['size' => 8]);
        $mform->setType('wordlimit', PARAM_INT);
        $mform->addHelpButton('wordlimit', 'wordlimit', 'assignsubmission_onlinetext');
        $mform->hideIf('wordlimit', 'submissiononlinetext', 'notchecked');

        $mform->addElement('header', 'submissionsettings', get_string('submissionsettings', 'assign'));
        $mform->addElement('selectyesno', 'requiresubmissionstatement',
            get_string('requiresubmissionstatement', 'assign'));
        $mform->addHelpButton('requiresubmissionstatement', 'requiresubmissionstatement', 'assign');

        $mform->addElement('header', 'groupsetup', get_string('groupsetup', 'groupassign'));
        $mform->setExpanded('groupsetup', true);

        $mform->addElement('select', 'formationmode', get_string('formationmode', 'groupassign'), [
            GROUPASSIGN_FORMATION_SELFSELECT => get_string('formation:selfselect', 'groupassign'),
            GROUPASSIGN_FORMATION_AUTO => get_string('formation:auto', 'groupassign'),
            GROUPASSIGN_FORMATION_EXISTING => get_string('formation:existing', 'groupassign'),
        ]);
        $mform->addHelpButton('formationmode', 'formationmode', 'groupassign');

        $groupingoptions = [0 => get_string('none')];
        foreach (groups_get_all_groupings($COURSE->id) as $grouping) {
            $groupingoptions[$grouping->id] = format_string($grouping->name);
        }
        $mform->addElement('select', 'groupingid', get_string('groupingused', 'groupassign'), $groupingoptions);
        $mform->hideIf('groupingid', 'formationmode', 'neq', GROUPASSIGN_FORMATION_EXISTING);

        $mform->addElement('text', 'numgroups', get_string('numgroups', 'groupassign'), ['size' => '6']);
        $mform->setType('numgroups', PARAM_INT);
        $mform->setDefault('numgroups', 4);
        $mform->hideIf('numgroups', 'formationmode', 'eq', GROUPASSIGN_FORMATION_EXISTING);

        $mform->addElement('text', 'groupnameprefix', get_string('groupnameprefix', 'groupassign'), ['size' => '48']);
        $mform->setType('groupnameprefix', PARAM_TEXT);
        $mform->addHelpButton('groupnameprefix', 'groupnameprefix', 'groupassign');
        $mform->hideIf('groupnameprefix', 'formationmode', 'eq', GROUPASSIGN_FORMATION_EXISTING);

        $mform->addElement('select', 'groupnamesuffix', get_string('groupnamesuffix', 'groupassign'), [
            GROUPASSIGN_SUFFIX_NUMBERS => get_string('groupnamesuffix:numbers', 'groupassign'),
            GROUPASSIGN_SUFFIX_LETTERS => get_string('groupnamesuffix:letters', 'groupassign'),
        ]);
        $mform->setDefault('groupnamesuffix', GROUPASSIGN_SUFFIX_NUMBERS);
        $mform->hideIf('groupnamesuffix', 'formationmode', 'eq', GROUPASSIGN_FORMATION_EXISTING);

        $mform->addElement('text', 'minmembers', get_string('minmembers', 'groupassign'), ['size' => '6']);
        $mform->setType('minmembers', PARAM_INT);
        $mform->setDefault('minmembers', 0);

        $mform->addElement('text', 'maxmembers', get_string('maxmembers', 'groupassign'), ['size' => '6']);
        $mform->setType('maxmembers', PARAM_INT);
        $mform->setDefault('maxmembers', 4);

        $mform->addElement('date_time_selector', 'selectionopen',
            get_string('selectionopen', 'groupassign'), ['optional' => true]);
        $mform->addElement('date_time_selector', 'selectionclose',
            get_string('selectionclose', 'groupassign'), ['optional' => true]);

        $mform->addElement('header', 'grouppermissions', get_string('groupformation', 'groupassign'));
        $mform->addElement('advcheckbox', 'allowstudentjoin', get_string('allowstudentjoin', 'groupassign'), '', [], [0, 1]);
        $mform->setDefault('allowstudentjoin', 1);
        $mform->addElement('advcheckbox', 'allowstudentleave', get_string('allowstudentleave', 'groupassign'), '', [], [0, 1]);
        $mform->setDefault('allowstudentleave', 1);
        $mform->addElement('advcheckbox', 'allowstudentcreate', get_string('allowstudentcreate', 'groupassign'), '', [], [0, 1]);
        $mform->setDefault('allowstudentcreate', 0);
        $mform->addElement('advcheckbox', 'allowstudentrename', get_string('allowstudentrename', 'groupassign'), '', [], [0, 1]);
        $mform->setDefault('allowstudentrename', 0);
        $mform->disabledIf('allowstudentrename', 'allowstudentcreate', 'notchecked');
        $mform->addElement('advcheckbox', 'allowstudentdescription', get_string('allowstudentdescription', 'groupassign'), '', [], [0, 1]);
        $mform->setDefault('allowstudentdescription', 1);
        $mform->disabledIf('allowstudentdescription', 'allowstudentcreate', 'notchecked');
        $mform->addElement('advcheckbox', 'hidefullgroups', get_string('hidefullgroups', 'groupassign'), '', [], [0, 1]);
        $mform->setDefault('hidefullgroups', 0);
        $mform->addHelpButton('hidefullgroups', 'hidefullgroups', 'groupassign');
        $mform->addElement('advcheckbox', 'showmembers', get_string('showmembers', 'groupassign'), '', [], [0, 1]);
        $mform->setDefault('showmembers', 1);
        $mform->addHelpButton('showmembers', 'showmembers', 'groupassign');

        $mform->addElement('header', 'peerassessment', get_string('peerassessment', 'groupassign'));
        $mform->addElement('advcheckbox', 'peerenabled', get_string('peerenabled', 'groupassign'), '', [], [0, 1]);
        $mform->setDefault('peerenabled', 0);
        $mform->addHelpButton('peerenabled', 'peerassessment', 'groupassign');
        $mform->addElement('advcheckbox', 'peerselfassessment', get_string('peerselfassessment', 'groupassign'), '', [], [0, 1]);
        $mform->setDefault('peerselfassessment', 1);
        $mform->hideIf('peerselfassessment', 'peerenabled', 'notchecked');
        $mform->addElement('advcheckbox', 'peercomments', get_string('peercomments', 'groupassign'), '', [], [0, 1]);
        $mform->setDefault('peercomments', 1);
        $mform->hideIf('peercomments', 'peerenabled', 'notchecked');
        $mform->addHelpButton('peercomments', 'peercomments', 'groupassign');
        $mform->addElement('advcheckbox', 'peerrequirejustification', get_string('peerrequirejustification', 'groupassign'), '',
            [], [0, 1]);
        $mform->setDefault('peerrequirejustification', 0);
        $mform->hideIf('peerrequirejustification', 'peerenabled', 'notchecked');
        $mform->disabledIf('peerrequirejustification', 'peercomments', 'notchecked');
        $mform->addHelpButton('peerrequirejustification', 'peerrequirejustification', 'groupassign');

        $mform->addElement('static', 'peercriteriaintro', get_string('peercriteria', 'groupassign'),
            get_string('peercriteria_help', 'groupassign'));
        $ratingtypeoptions = [
            'fourlevel' => get_string('peerratingtype:fourlevel', 'groupassign'),
            'satisfactory' => get_string('peerratingtype:satisfactory', 'groupassign'),
            'marks5' => get_string('peerratingtype:marks5', 'groupassign'),
        ];
        for ($i = 0; $i < 5; $i++) {
            $mform->addElement('hidden', "peercriteria_id[$i]");
            $mform->setType("peercriteria_id[$i]", PARAM_INT);
            $mform->addElement('text', "peercriteria_title[$i]", get_string('peercriteria', 'groupassign') . ' ' . ($i + 1),
                ['size' => 64]);
            $mform->setType("peercriteria_title[$i]", PARAM_TEXT);
            $mform->hideIf("peercriteria_title[$i]", 'peerenabled', 'notchecked');
            $mform->addElement('textarea', "peercriteria_description[$i]",
                get_string('peercriteria_description', 'groupassign'), ['rows' => 2, 'cols' => 60]);
            $mform->setType("peercriteria_description[$i]", PARAM_TEXT);
            $mform->hideIf("peercriteria_description[$i]", 'peerenabled', 'notchecked');
            $mform->addElement('select', "peercriteria_ratingtype[$i]", get_string('peercriteria_ratingtype', 'groupassign'),
                $ratingtypeoptions);
            $mform->setDefault("peercriteria_ratingtype[$i]", 'fourlevel');
            $mform->hideIf("peercriteria_ratingtype[$i]", 'peerenabled', 'notchecked');
        }

        $mform->addElement('header', 'feedbacktypes', get_string('feedbacktypes', 'groupassign'));
        $mform->addElement('static', 'feedbackcommentsenabled', get_string('feedbackcomments', 'groupassign'),
            get_string('feedbackcommentsalwayson', 'groupassign'));

        $this->standard_grading_coursemodule_elements();
        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    public function data_preprocessing(&$defaultvalues) {
        global $DB;

        parent::data_preprocessing($defaultvalues);

        $draftitemid = file_get_submitted_draft_itemid('introattachments');
        file_prepare_draft_area($draftitemid, $this->context->id, 'mod_groupassign', 'introattachment', 0,
            ['subdirs' => 0]);
        $defaultvalues['introattachments'] = $draftitemid;

        $activitydraftitemid = file_get_submitted_draft_itemid('activityeditor');
        $defaultvalues['activityeditor'] = [
            'text' => file_prepare_draft_area($activitydraftitemid, $this->context->id, 'mod_groupassign',
                'activityattachment', 0, ['subdirs' => true], $defaultvalues['activity'] ?? ''),
            'format' => $defaultvalues['activityformat'] ?? FORMAT_HTML,
            'itemid' => $activitydraftitemid,
        ];

        $criteria = [];
        if (!empty($this->current->id)) {
            $records = $DB->get_records('groupassign_peercriteria',
                ['groupassignid' => $this->current->id, 'archived' => 0], 'sortorder ASC');
            foreach ($records as $record) {
                $criteria[] = [
                    'id' => $record->id,
                    'title' => $record->description,
                    'description' => $record->details ?? '',
                    'ratingtype' => $record->ratingtype ?? 'fourlevel',
                ];
            }
        }

        if (!$criteria) {
            $criteria = groupassign_default_peercriteria();
        }

        for ($i = 0; $i < 5; $i++) {
            $defaultvalues["peercriteria_id[$i]"] = $criteria[$i]['id'] ?? 0;
            $defaultvalues["peercriteria_title[$i]"] = $criteria[$i]['title'] ?? '';
            $defaultvalues["peercriteria_description[$i]"] = $criteria[$i]['description'] ?? '';
            $defaultvalues["peercriteria_ratingtype[$i]"] = $criteria[$i]['ratingtype'] ?? 'fourlevel';
        }
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if ((int)$data['maxmembers'] > 0 && (int)$data['minmembers'] > (int)$data['maxmembers']) {
            $errors['minmembers'] = get_string('minmembers_error_bigger_maxmembers', 'groupassign');
        }
        if (!empty($data['selectionopen']) && !empty($data['selectionclose'])
                && $data['selectionopen'] >= $data['selectionclose']) {
            $errors['selectionclose'] = get_string('timedue_error_pre_timeavailable', 'groupassign');
        }
        if (!empty($data['allowsubmissionsfromdate']) && !empty($data['duedate'])
                && $data['duedate'] <= $data['allowsubmissionsfromdate']) {
            $errors['duedate'] = get_string('duedateaftersubmissionvalidation', 'assign');
        }
        if (!empty($data['cutoffdate']) && !empty($data['duedate'])
                && $data['cutoffdate'] < $data['duedate']) {
            $errors['cutoffdate'] = get_string('cutoffdatevalidation', 'assign');
        }
        if (!empty($data['allowsubmissionsfromdate']) && !empty($data['cutoffdate'])
                && $data['cutoffdate'] < $data['allowsubmissionsfromdate']) {
            $errors['cutoffdate'] = get_string('cutoffdatefromdatevalidation', 'assign');
        }
        if (!empty($data['gradingduedate'])) {
            if (!empty($data['allowsubmissionsfromdate']) && $data['allowsubmissionsfromdate'] > $data['gradingduedate']) {
                $errors['gradingduedate'] = get_string('gradingduefromdatevalidation', 'assign');
            }
            if (!empty($data['duedate']) && $data['duedate'] > $data['gradingduedate']) {
                $errors['gradingduedate'] = get_string('gradingdueduedatevalidation', 'assign');
            }
        }
        if (!empty($data['wordlimit']) && (int)$data['wordlimit'] < 0) {
            $errors['wordlimit'] = get_string('invalidnum', 'error');
        }
        if ($data['formationmode'] === GROUPASSIGN_FORMATION_EXISTING && empty($data['groupingid'])) {
            $errors['groupingid'] = get_string('required');
        }
        if (empty($data['submissiononlinetext']) && empty($data['submissionfile'])) {
            $errors['submissiononlinetext'] = get_string('submissiontyperequired', 'groupassign');
        }
        if (!empty($data['peerenabled'])) {
            $criteria = array_filter(array_map('trim', $data['peercriteria_title'] ?? []));
            if (!$criteria) {
                $errors['peercriteria_title[0]'] = get_string('required');
            }
            if (!empty($data['peerrequirejustification']) && empty($data['peercomments'])) {
                $errors['peercomments'] = get_string('required');
            }
        }

        return $errors;
    }

}
