<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_groupassign\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class grade_form extends \moodleform {

    public function definition() {
        $mform = $this->_form;
        $groupassign = $this->_customdata['groupassign'];
        $editoroptions = $this->_customdata['editoroptions'];
        $members = $this->_customdata['members'] ?? [];

        $mform->addElement('hidden', 'groupid');
        $mform->setType('groupid', PARAM_INT);

        $mform->addElement('text', 'grade', get_string('grade', 'groupassign'), ['size' => 8]);
        $mform->setType('grade', PARAM_FLOAT);
        $mform->addRule('grade', get_string('maximumgrade') . ': ' . $groupassign->grade, 'numeric', null, 'client');

        $mform->addElement('editor', 'feedbackeditor', get_string('feedback', 'groupassign'), null, $editoroptions);
        $mform->setType('feedbackeditor', PARAM_RAW);

        if ($members) {
            $mform->addElement('header', 'individualadjustments',
                get_string('individualadjustments', 'groupassign'));
            $mform->addElement('static', 'individualadjustments_help', '',
                get_string('individualadjustments_help', 'groupassign'));

            foreach ($members as $member) {
                $membername = fullname($member);
                $mform->addElement('static', 'membername_' . $member->id, '',
                    \html_writer::span($membername, 'fw-bold'));

                $gradefield = 'membergrade_' . $member->id;
                $feedbackfield = 'memberfeedback_' . $member->id;
                $mform->addElement('text', $gradefield,
                    get_string('individualgradefor', 'groupassign', $membername), ['size' => 8]);
                $mform->setType($gradefield, PARAM_FLOAT);
                $mform->addRule($gradefield, get_string('maximumgrade') . ': ' . $groupassign->grade,
                    'numeric', null, 'client');

                $mform->addElement('textarea', $feedbackfield,
                    get_string('individualfeedbackfor', 'groupassign', $membername),
                    ['rows' => 3, 'cols' => 70]);
                $mform->setType($feedbackfield, PARAM_TEXT);
            }
        }

        $this->add_action_buttons(true, get_string('savechanges'));
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $groupassign = $this->_customdata['groupassign'];

        if ($data['grade'] !== '' && ((float)$data['grade'] < 0 || (float)$data['grade'] > (float)$groupassign->grade)) {
            $errors['grade'] = get_string('gradeoutofrange', 'groupassign', $groupassign->grade);
        }

        foreach (($this->_customdata['members'] ?? []) as $member) {
            $gradefield = 'membergrade_' . $member->id;
            $feedbackfield = 'memberfeedback_' . $member->id;
            $membergrade = $data[$gradefield] ?? '';
            $memberfeedback = trim((string)($data[$feedbackfield] ?? ''));

            if ($membergrade !== ''
                    && ((float)$membergrade < 0 || (float)$membergrade > (float)$groupassign->grade)) {
                $errors[$gradefield] = get_string('gradeoutofrange', 'groupassign', $groupassign->grade);
            }
            if ($membergrade !== '' && $memberfeedback === '') {
                $errors[$feedbackfield] = get_string('individualfeedbackrequired', 'groupassign');
            }
        }

        return $errors;
    }
}
