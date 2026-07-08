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

namespace mod_groupassign\navigation\views;

use core\navigation\views\secondary as core_secondary;

/**
 * Secondary navigation mapping for group assignment.
 *
 * @package    mod_groupassign
 * @copyright  2026 Matthew Darch <matthew.darch.1up@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class secondary extends core_secondary {
    /**
     * Get default module mapping.
     *
     * @return array
     */
    protected function get_default_module_mapping(): array {
        $mapping = parent::get_default_module_mapping();
        $mapping[self::TYPE_SETTING] = array_merge($mapping[self::TYPE_SETTING], [
            'modedit' => 1,
            "mod_{$this->page->activityname}_submissions" => 2,
        ]);
        $mapping[self::TYPE_CUSTOM] = array_merge($mapping[self::TYPE_CUSTOM], [
            'advgrading' => 3,
        ]);
        return $mapping;
    }
}
