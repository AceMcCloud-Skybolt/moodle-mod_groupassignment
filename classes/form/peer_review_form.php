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
 * Peer review form for mod_groupassign.
 *
 * @package    mod_groupassign
 * @copyright  2026 Matthew Darch <matthew.darch.1up@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_groupassign\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Peer review form.
 */
class peer_review_form extends \moodleform {
    /**
     * Definition.
     * @return mixed
     */
    public function definition() {
        $mform = $this->_form;
        $criteria = $this->_customdata['criteria'];
        $members = $this->_customdata['members'];
        $groupassign = $this->_customdata['groupassign'];
        $ratingsbycriteria = $this->_customdata['ratingsbycriteria'];

        $mform->addElement('hidden', 'groupid');
        $mform->setType('groupid', PARAM_INT);

        foreach ($members as $member) {
            $mform->addElement('header', 'member_' . $member->id, fullname($member));
            foreach ($criteria as $criterion) {
                $ratingname = 'rating_' . $criterion->id . '_' . $member->id;
                $commentname = 'comment_' . $criterion->id . '_' . $member->id;

                $criterionlabel = format_string($criterion->description);
                if (!empty($criterion->details)) {
                    $criterionlabel .= ' - ' . format_string($criterion->details);
                }
                $ratingoptions = $ratingsbycriteria[$criterion->id] ?? [];
                $mform->addElement('select', $ratingname, $criterionlabel, $ratingoptions);
                $mform->setType($ratingname, PARAM_INT);
                $mform->addRule($ratingname, get_string('required'), 'required', null, 'client');

                if (!empty($groupassign->peercomments)) {
                    $mform->addElement(
                        'textarea',
                        $commentname,
                        get_string('peercomment', 'groupassign'),
                        ['rows' => 2, 'class' => 'w-100']
                    );
                    $mform->setType($commentname, PARAM_TEXT);
                }
            }
        }

        $this->add_action_buttons(true, get_string('savepeerreview', 'groupassign'));
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
        foreach ($this->_customdata['criteria'] as $criterion) {
            foreach ($this->_customdata['members'] as $member) {
                $ratingname = 'rating_' . $criterion->id . '_' . $member->id;
                if (!isset($data[$ratingname]) || $data[$ratingname] === '') {
                    $errors[$ratingname] = get_string('required');
                }
                $commentname = 'comment_' . $criterion->id . '_' . $member->id;
                if (
                    !empty($this->_customdata['groupassign']->peerrequirejustification)
                        && empty(trim((string)($data[$commentname] ?? '')))
                ) {
                    $errors[$commentname] = get_string('required');
                }
            }
        }
        return $errors;
    }
}
