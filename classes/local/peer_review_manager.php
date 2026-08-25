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
 * Peer-review workflow and reporting services.
 *
 * @package    mod_groupassign
 * @copyright  2026 Matthew Darch <matthew.darch.1up@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_groupassign\local;

/**
 * Peer-review workflow and reporting services.
 */
class peer_review_manager {
    /**
     * Peer rating options.
     *
     * @param string $ratingtype
     * @return array
     */
    public static function peer_rating_options(string $ratingtype = 'fourlevel'): array {
        if ($ratingtype === 'satisfactory') {
            return [
                '' => get_string('choosedots'),
                1 => get_string('peerrating:unsatisfactory', 'groupassign'),
                2 => get_string('peerrating:satisfactory', 'groupassign'),
                3 => get_string('peerrating:strong', 'groupassign'),
            ];
        }
        if ($ratingtype === 'marks5') {
            return [
                '' => get_string('choosedots'),
                0 => '0',
                1 => '1',
                2 => '2',
                3 => '3',
                4 => '4',
                5 => '5',
            ];
        }
        return [
            '' => get_string('choosedots'),
            1 => get_string('peerrating:concern', 'groupassign'),
            2 => get_string('peerrating:developing', 'groupassign'),
            3 => get_string('peerrating:met', 'groupassign'),
            4 => get_string('peerrating:exceeded', 'groupassign'),
        ];
    }

    /**
     * Peer ratingtype map.
     *
     * @param mixed $groupassign
     * @return array
     */
    public static function peer_ratingtype_map($groupassign): array {
        global $DB;

        $records = $DB->get_records('groupassign_peercriteria', ['groupassignid' => $groupassign->id], '', 'id,ratingtype');
        $ratingtypes = [];
        foreach ($records as $record) {
            $ratingtypes[(int)$record->id] = $record->ratingtype ?? 'fourlevel';
        }
        return $ratingtypes;
    }

    /**
     * Normalise peer rating.
     *
     * @param float $rating
     * @param string $ratingtype
     * @return float|null
     */
    public static function normalise_peer_rating(float $rating, string $ratingtype): ?float {
        if ($ratingtype === 'marks5') {
            $minimum = 0;
            $maximum = 5;
        } else if ($ratingtype === 'satisfactory') {
            $minimum = 1;
            $maximum = 3;
        } else {
            $minimum = 1;
            $maximum = 4;
        }

        if ($rating < $minimum || $rating > $maximum) {
            return null;
        }

        return ($rating - $minimum) / max($maximum - $minimum, 1);
    }

    /**
     * Get peercriteria.
     *
     * @param mixed $groupassign
     * @return array
     */
    public static function get_peercriteria($groupassign): array {
        global $DB;

        return $DB->get_records(
            'groupassign_peercriteria',
            ['groupassignid' => $groupassign->id, 'archived' => 0],
            'sortorder ASC'
        );
    }

    /**
     * Get reviewable members.
     *
     * @param mixed $groupassign
     * @param int $groupid
     * @param int $userid
     * @return array
     */
    public static function get_reviewable_members($groupassign, int $groupid, int $userid): array {
        $members = groups_get_members($groupid, 'u.*', 'u.lastname, u.firstname');
        if (empty($groupassign->peerselfassessment)) {
            unset($members[$userid]);
        }
        return $members ?: [];
    }

    /**
     * Peer review expected count.
     *
     * @param mixed $groupassign
     * @param int $groupid
     * @param int $userid
     * @return int
     */
    public static function peer_review_expected_count($groupassign, int $groupid, int $userid): int {
        return count(self::get_peercriteria($groupassign))
            * count(self::get_reviewable_members($groupassign, $groupid, $userid));
    }

    /**
     * Peer review completed count.
     *
     * @param mixed $groupassign
     * @param int $groupid
     * @param int $userid
     * @return int
     */
    public static function peer_review_completed_count($groupassign, int $groupid, int $userid): int {
        global $DB;

        return $DB->count_records('groupassign_peerreviews', [
            'groupassignid' => $groupassign->id,
            'groupid' => $groupid,
            'reviewerid' => $userid,
        ]);
    }

    /**
     * Peer review status label.
     *
     * @param mixed $groupassign
     * @param int $groupid
     * @param int $userid
     * @return string
     */
    public static function peer_review_status_label($groupassign, int $groupid, int $userid): string {
        $expected = self::peer_review_expected_count($groupassign, $groupid, $userid);
        if (!$expected) {
            return get_string('peerreviewdisabled', 'groupassign');
        }
        $completed = self::peer_review_completed_count($groupassign, $groupid, $userid);
        if ($completed >= $expected) {
            return get_string('peerreviewstatus:complete', 'groupassign');
        }
        if ($completed > 0) {
            return get_string('peerreviewstatus:partial', 'groupassign') . " ($completed / $expected)";
        }
        return get_string('peerreviewstatus:notstarted', 'groupassign') . " (0 / $expected)";
    }

    /**
     * Peer review group completion label.
     *
     * @param mixed $groupassign
     * @param int $groupid
     * @return string
     */
    public static function peer_review_group_completion_label($groupassign, int $groupid): string {
        $members = groups_get_members($groupid, 'u.id');
        if (!$members) {
            return '-';
        }
        $complete = 0;
        foreach ($members as $member) {
            $expected = self::peer_review_expected_count($groupassign, $groupid, $member->id);
            if ($expected && self::peer_review_completed_count($groupassign, $groupid, $member->id) >= $expected) {
                $complete++;
            }
        }
        return $complete . ' / ' . count($members);
    }

    /**
     * Peer review group flag.
     *
     * @param mixed $groupassign
     * @param int $groupid
     * @return string
     */
    public static function peer_review_group_flag($groupassign, int $groupid): string {
        global $DB;

        $reviews = $DB->get_records('groupassign_peerreviews', [
            'groupassignid' => $groupassign->id,
            'groupid' => $groupid,
        ]);

        return self::peer_review_flag_from_reviews($reviews, self::peer_ratingtype_map($groupassign));
    }

    /**
     * Peer review completion map.
     *
     * @param mixed $groupassign
     * @param array $groups
     * @return array
     */
    public static function peer_review_completion_map($groupassign, array $groups): array {
        global $DB;

        $groupids = \mod_groupassign\local\workflow_manager::group_ids($groups);
        if (!$groupids) {
            return [];
        }

        [$insql, $params] = $DB->get_in_or_equal($groupids, SQL_PARAMS_NAMED, 'groupid');
        $params['groupassignid'] = $groupassign->id;
        $sql = "SELECT groupid,
                       reviewerid,
                       COUNT(1) AS reviewcount
                  FROM {groupassign_peerreviews}
                 WHERE groupassignid = :groupassignid
                   AND groupid $insql
              GROUP BY groupid, reviewerid";

        $completion = [];
        $records = $DB->get_recordset_sql($sql, $params);
        foreach ($records as $record) {
            $completion[(int)$record->groupid][(int)$record->reviewerid] = (int)$record->reviewcount;
        }
        $records->close();
        return $completion;
    }

    /**
     * Peer review group completion label cached.
     *
     * @param mixed $groupassign
     * @param int $groupid
     * @param array $members
     * @param array $completion
     * @param int $criteriacount
     * @return string
     */
    public static function peer_review_group_completion_label_cached(
        $groupassign,
        int $groupid,
        array $members,
        array $completion,
        int $criteriacount
    ): string {
        if (!$members || !$criteriacount) {
            return '-';
        }

        $complete = 0;
        $revieweecount = count($members) - (empty($groupassign->peerselfassessment) ? 1 : 0);
        $expected = $criteriacount * max($revieweecount, 0);
        foreach ($members as $member) {
            if ($expected > 0 && (int)($completion[$groupid][$member->id] ?? 0) >= $expected) {
                $complete++;
            }
        }

        return $complete . ' / ' . count($members);
    }

    /**
     * Peer review flags map.
     *
     * @param mixed $groupassign
     * @param array $groups
     * @return array
     */
    public static function peer_review_flags_map($groupassign, array $groups): array {
        global $DB;

        $flags = [];
        $groupids = \mod_groupassign\local\workflow_manager::group_ids($groups);
        if (!$groupids) {
            return $flags;
        }

        [$insql, $params] = $DB->get_in_or_equal($groupids, SQL_PARAMS_NAMED, 'groupid');
        $params['groupassignid'] = $groupassign->id;
        $reviews = $DB->get_records_select(
            'groupassign_peerreviews',
            "groupassignid = :groupassignid AND groupid $insql",
            $params
        );
        $ratingtypes = self::peer_ratingtype_map($groupassign);

        $bygroup = [];
        foreach ($reviews as $review) {
            $bygroup[(int)$review->groupid][] = $review;
        }

        foreach ($groups as $group) {
            $flags[(int)$group->id] = self::peer_review_flag_from_reviews(
                $bygroup[(int)$group->id] ?? [],
                $ratingtypes
            );
        }

        return $flags;
    }

    /**
     * Peer review flag from reviews.
     *
     * @param array $reviews
     * @param array $ratingtypes
     * @return string
     */
    public static function peer_review_flag_from_reviews(array $reviews, array $ratingtypes = []): string {
        if (!$reviews) {
            return get_string('peerflag:clear', 'groupassign');
        }

        $received = [];
        $given = [];
        foreach ($reviews as $review) {
            $rating = self::normalise_peer_rating(
                (float)$review->rating,
                $ratingtypes[(int)$review->criteriaid] ?? 'fourlevel'
            );
            if ($rating === null) {
                continue;
            }
            $received[$review->revieweeid][] = $rating;
            $given[$review->reviewerid][] = $rating;
        }

        $receivedavgs = [];
        foreach ($received as $userid => $ratings) {
            $receivedavgs[$userid] = array_sum($ratings) / max(count($ratings), 1);
        }
        if (count($receivedavgs) >= 2) {
            $minavg = min($receivedavgs);
            $maxavg = max($receivedavgs);
            if ($minavg <= 0.35 && ($maxavg - $minavg) >= 0.35) {
                return get_string('peerflag:concern', 'groupassign');
            }
        }

        $givenavgs = [];
        foreach ($given as $userid => $ratings) {
            $givenavgs[$userid] = array_sum($ratings) / max(count($ratings), 1);
        }
        if (count($givenavgs) >= 2) {
            $mean = array_sum($givenavgs) / count($givenavgs);
            foreach ($givenavgs as $avg) {
                if ($avg <= ($mean - 0.25)) {
                    return get_string('peerflag:followup', 'groupassign');
                }
            }
        }

        return get_string('peerflag:clear', 'groupassign');
    }
}
