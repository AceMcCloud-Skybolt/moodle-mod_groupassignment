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

        $this->add_action_buttons(false, get_string('submitassignment', 'assign'));
    }
}
