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
 * Grade form for mod_groupassign.
 *
 * @package    mod_groupassign
 * @copyright  2026 Matthew Darch <matthew.darch.1up@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_groupassign\form;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/gradelib.php');
/**
 * Grade form.
 */
class grade_form extends \moodleform {
    /**
     * Definition.
     * @return mixed
     */
    public function definition() {
        $mform = $this->_form;
        $groupassign = $this->_customdata['groupassign'];
        $editoroptions = $this->_customdata['editoroptions'];
        $members = $this->_customdata['members'] ?? [];

        $mform->addElement('hidden', 'groupid');
        $mform->setType('groupid', PARAM_INT);

        if ((int)$groupassign->grade > 0) {
            $mform->addElement('text', 'grade', get_string('grade', 'groupassign'), ['size' => 8]);
            $mform->setType('grade', PARAM_FLOAT);
            $mform->addRule('grade', get_string('maximumgrade') . ': ' . $groupassign->grade, 'numeric', null, 'client');
        } else if ((int)$groupassign->grade < 0) {
            $mform->addElement('select', 'grade', get_string('grade', 'groupassign'), $this->get_scale_options($groupassign));
            $mform->setType('grade', PARAM_INT);
        } else {
            $mform->addElement(
                'static',
                'grade_disabled',
                get_string('grade', 'groupassign'),
                get_string('gradeisnone', 'groupassign')
            );
        }

        $mform->addElement('editor', 'feedbackeditor', get_string('feedback', 'groupassign'), null, $editoroptions);
        $mform->setType('feedbackeditor', PARAM_RAW);
        if ($members && (int)$groupassign->grade !== 0) {
            $mform->addElement('header', 'individualadjustments', get_string('individualadjustments', 'groupassign'));
            $mform->addElement('static', 'individualadjustments_help', '', get_string('individualadjustments_help', 'groupassign'));

            foreach ($members as $member) {
                $membername = fullname($member);
                $mform->addElement(
                    'static',
                    'membername_' . $member->id,
                    '',
                    \html_writer::span($membername, 'fw-bold')
                );

                $gradefield = 'membergrade_' . $member->id;
                $feedbackfield = 'memberfeedback_' . $member->id;
                if ((int)$groupassign->grade > 0) {
                    $mform->addElement(
                        'text',
                        $gradefield,
                        get_string('individualgradefor', 'groupassign', $membername),
                        ['size' => 8]
                    );
                    $mform->setType($gradefield, PARAM_FLOAT);
                    $mform->addRule(
                        $gradefield,
                        get_string('maximumgrade') . ': ' . $groupassign->grade,
                        'numeric',
                        null,
                        'client'
                    );
                } else {
                    $mform->addElement(
                        'select',
                        $gradefield,
                        get_string('individualgradefor', 'groupassign', $membername),
                        $this->get_scale_options($groupassign)
                    );
                    $mform->setType($gradefield, PARAM_INT);
                }
                $mform->addElement(
                    'textarea',
                    $feedbackfield,
                    get_string('individualfeedbackfor', 'groupassign', $membername),
                    ['rows' => 3, 'cols' => 70]
                );
                $mform->setType($feedbackfield, PARAM_TEXT);
            }
        }

        $this->add_action_buttons(true, get_string('savechanges'));
    }

    /**
     * Get scale options.
     *
     * @param mixed $groupassign
     * @return array
     */
    private function get_scale_options($groupassign): array {

        $options = ['' => get_string('nograde')];
        $scaleid = abs((int)$groupassign->grade);
        $scale = \grade_scale::fetch(['id' => $scaleid]);
        if (!$scale) {
            return $options;
        }

        foreach ($scale->load_items() as $index => $label) {
            $options[$index + 1] = format_string($label);
        }
        return $options;
    }

    /**
     * Validation.
     *
     * @param mixed $data
     * @param mixed $files
     * @return mixed
     */
    public function validation($data, $files) {

        $errors = parent::validation($data, $files);
        $groupassign = $this->_customdata['groupassign'];
        if (
            (int)$groupassign->grade > 0 && $data['grade'] !== ''
                && ((float)$data['grade'] < 0 || (float)$data['grade'] > (float)$groupassign->grade)
        ) {
            $errors['grade'] = get_string('gradeoutofrange', 'groupassign', $groupassign->grade);
        }
        if ((int)$groupassign->grade < 0 && !$this->scale_value_valid($groupassign, $data['grade'] ?? '')) {
            $errors['grade'] = get_string('invalidgrade', 'groupassign');
        }

        foreach (($this->_customdata['members'] ?? []) as $member) {
            $gradefield = 'membergrade_' . $member->id;
            $feedbackfield = 'memberfeedback_' . $member->id;
            $membergrade = $data[$gradefield] ?? '';
            $memberfeedback = trim((string)($data[$feedbackfield] ?? ''));
            $hasmembergrade = !($membergrade === '' || $membergrade === null
                || ((int)$groupassign->grade < 0 && (int)$membergrade === 0));
            if (
                (int)$groupassign->grade > 0 && $membergrade !== ''
                    && ((float)$membergrade < 0 || (float)$membergrade > (float)$groupassign->grade)
            ) {
                $errors[$gradefield] = get_string('gradeoutofrange', 'groupassign', $groupassign->grade);
            }
            if ((int)$groupassign->grade < 0 && !$this->scale_value_valid($groupassign, $membergrade)) {
                $errors[$gradefield] = get_string('invalidgrade', 'groupassign');
            }
            if ($hasmembergrade && $memberfeedback === '') {
                $errors[$feedbackfield] = get_string('individualfeedbackrequired', 'groupassign');
            }
        }

        return $errors;
    }

    /**
     * Scale value valid.
     *
     * @param mixed $groupassign
     * @param mixed $value
     * @return bool
     */
    private function scale_value_valid($groupassign, $value): bool {

        if ($value === '' || $value === null || (int)$value === 0) {
            return true;
        }
        return array_key_exists((int)$value, $this->get_scale_options($groupassign));
    }
}
