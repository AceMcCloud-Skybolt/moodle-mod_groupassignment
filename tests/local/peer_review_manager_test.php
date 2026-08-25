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
 * Tests for peer-review calculations and flags.
 *
 * @package    mod_groupassign
 * @category   test
 * @copyright  2026 Matthew Darch <matthew.darch.1up@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_groupassign\local;

/**
 * Peer-review manager tests.
 *
 */
final class peer_review_manager_test extends \advanced_testcase {
    /**
     * Ratings from every supported scale are normalised consistently.
     */
    public function test_normalise_peer_rating(): void {
        $this->assertSame(0.0, peer_review_manager::normalise_peer_rating(1, 'fourlevel'));
        $this->assertSame(1.0, peer_review_manager::normalise_peer_rating(4, 'fourlevel'));
        $this->assertSame(0.5, peer_review_manager::normalise_peer_rating(2, 'satisfactory'));
        $this->assertSame(0.6, peer_review_manager::normalise_peer_rating(3, 'marks5'));
        $this->assertNull(peer_review_manager::normalise_peer_rating(6, 'marks5'));
    }

    /**
     * A materially lower received average raises a concern flag.
     */
    public function test_received_rating_divergence_is_flagged(): void {
        $reviews = [
            $this->review(2, 1, 1),
            $this->review(3, 1, 1),
            $this->review(1, 2, 4),
            $this->review(3, 2, 4),
        ];

        $this->assertSame(
            get_string('peerflag:concern', 'groupassign'),
            peer_review_manager::peer_review_flag_from_reviews($reviews, [1 => 'fourlevel'])
        );
    }

    /**
     * An unusually harsh reviewer raises a follow-up flag without changing grades.
     */
    public function test_harsh_reviewer_is_flagged_for_follow_up(): void {
        $reviews = [];
        foreach ([10, 11] as $revieweeid) {
            $reviews[] = $this->review(1, $revieweeid, 1);
            $reviews[] = $this->review(2, $revieweeid, 4);
            $reviews[] = $this->review(3, $revieweeid, 4);
        }

        $this->assertSame(
            get_string('peerflag:followup', 'groupassign'),
            peer_review_manager::peer_review_flag_from_reviews($reviews, [1 => 'fourlevel'])
        );
    }

    /**
     * Build a peer-review record for a single criterion.
     *
     * @param int $reviewerid
     * @param int $revieweeid
     * @param int $rating
     * @return stdClass
     */
    private function review(int $reviewerid, int $revieweeid, int $rating): \stdClass {
        return (object)[
            'criteriaid' => 1,
            'reviewerid' => $reviewerid,
            'revieweeid' => $revieweeid,
            'rating' => $rating,
        ];
    }
}
