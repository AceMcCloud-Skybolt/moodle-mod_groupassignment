<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_groupassign\navigation\views;

use core\navigation\views\secondary as core_secondary;

defined('MOODLE_INTERNAL') || die();

/**
 * Secondary navigation mapping for group assignment.
 *
 * @package    mod_groupassign
 */
class secondary extends core_secondary {
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
