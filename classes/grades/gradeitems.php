<?php
// This file is part of Moodle - http://moodle.org/

declare(strict_types=1);

namespace mod_groupassign\grades;

use core_grades\local\gradeitem\advancedgrading_mapping;
use core_grades\local\gradeitem\itemnumber_mapping;

defined('MOODLE_INTERNAL') || die();

/**
 * Grade item mappings for the group assignment activity.
 *
 * @package    mod_groupassign
 */
class gradeitems implements itemnumber_mapping, advancedgrading_mapping {

    /**
     * Return grade item names mapped to item numbers.
     *
     * @return array
     */
    public static function get_itemname_mapping_for_component(): array {
        return [
            0 => 'submissions',
        ];
    }

    /**
     * Return item names that support advanced grading.
     *
     * @return array
     */
    public static function get_advancedgrading_itemnames(): array {
        return [
            'submissions',
        ];
    }
}
