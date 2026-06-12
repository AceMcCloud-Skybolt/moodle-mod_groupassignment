<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_groupassign\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class submission_form extends \moodleform {

    public function definition() {
        $mform = $this->_form;
        $groupassign = $this->_customdata['groupassign'];
        $editoroptions = $this->_customdata['editoroptions'];
        $fileoptions = $this->_customdata['fileoptions'];

        $mform->addElement('hidden', 'groupid');
        $mform->setType('groupid', PARAM_INT);

        if (!empty($groupassign->submissiononlinetext)) {
            $mform->addElement('editor', 'submissioneditor', get_string('submissiontext', 'groupassign'),
                null, $editoroptions);
            $mform->setType('submissioneditor', PARAM_RAW);
        }

        if (!empty($groupassign->submissionfile)) {
            $mform->addElement('filemanager', 'submissionfiles', get_string('submissionfiles', 'groupassign'),
                null, $fileoptions);
        }

        if (!empty($groupassign->requiresubmissionstatement)) {
            $mform->addElement('advcheckbox', 'submissionstatement',
                get_string('submissionstatementteamsubmission', 'assign'),
                get_string('submissionstatementteamsubmissiondefault', 'assign'));
            $mform->addRule('submissionstatement', get_string('submissionstatementrequired', 'assign'),
                'required', null, 'client');
        }

        $this->add_action_buttons(false, get_string('submitassignment', 'assign'));
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $groupassign = $this->_customdata['groupassign'];

        if (!empty($groupassign->wordlimit) && !empty($data['submissioneditor']['text'])) {
            $wordcount = str_word_count(strip_tags($data['submissioneditor']['text']));
            if ($wordcount > (int)$groupassign->wordlimit) {
                $errors['submissioneditor'] = get_string('wordlimitexceeded', 'assignsubmission_onlinetext', (object)[
                    'limit' => (int)$groupassign->wordlimit,
                    'count' => $wordcount,
                ]);
            }
        }
        if (!empty($groupassign->requiresubmissionstatement) && empty($data['submissionstatement'])) {
            $errors['submissionstatement'] = get_string('submissionstatementrequired', 'assign');
        }

        return $errors;
    }
}
