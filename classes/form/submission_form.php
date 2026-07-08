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
 * Submission form for mod_groupassign.
 *
 * @package    mod_groupassign
 * @copyright  2026 Matthew Darch <matthew.darch.1up@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_groupassign\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Submission form.
 */
class submission_form extends \moodleform {
    /**
     * Definition.
     * @return mixed
     */
    public function definition() {
        $mform = $this->_form;
        $groupassign = $this->_customdata['groupassign'];
        $editoroptions = $this->_customdata['editoroptions'];
        $fileoptions = $this->_customdata['fileoptions'];

        $mform->addElement('hidden', 'groupid');
        $mform->setType('groupid', PARAM_INT);

        if (!empty($groupassign->submissiononlinetext)) {
            $mform->addElement(
                'editor',
                'submissioneditor',
                get_string('submissiontext', 'groupassign'),
                null,
                $editoroptions
            );
            $mform->setType('submissioneditor', PARAM_RAW);
        }

        if (!empty($groupassign->submissionfile)) {
            $mform->addElement('filemanager', 'submissionfiles', get_string('submissionfiles', 'groupassign'), null, $fileoptions);
        }

        if (!empty($groupassign->requiresubmissionstatement)) {
            $mform->addElement(
                'advcheckbox',
                'submissionstatement',
                get_string('submissionstatementteamsubmission', 'groupassign'),
                get_string('submissionstatementteamsubmissiondefault', 'groupassign')
            );
            $mform->addRule(
                'submissionstatement',
                get_string('submissionstatementrequired', 'groupassign'),
                'required',
                null,
                'client'
            );
        }

        $this->add_action_buttons(false, get_string('submitassignment', 'groupassign'));
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
        if (!empty($groupassign->wordlimit) && !empty($data['submissioneditor']['text'])) {
            $wordcount = str_word_count(strip_tags($data['submissioneditor']['text']));
            if ($wordcount > (int)$groupassign->wordlimit) {
                $errors['submissioneditor'] = get_string('wordlimitexceeded', 'groupassign', (object)[
                    'limit' => (int)$groupassign->wordlimit, 'count' => $wordcount,
                ]);
            }
        }
        if (!empty($groupassign->requiresubmissionstatement) && empty($data['submissionstatement'])) {
            $errors['submissionstatement'] = get_string('submissionstatementrequired', 'groupassign');
        }

        return $errors;
    }
}
