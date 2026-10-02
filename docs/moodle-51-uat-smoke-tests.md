# Moodle 5.1 upgrade: Group Assignment UAT

## Verified baseline

Checked on 2 October 2026: the local sandbox code and database are Moodle **5.1.4+ (Build: 20260604)**, branch 501. Installed Group Assignment is **0.1.15**, database version **2026080300**. The August validation ran on Moodle 5.1.4+, PHP 8.2.4 and MariaDB 10.11.11: six PHPUnit tests passed with 22 assertions, alongside PHP lint, Moodle coding-standard checks and the plugin upgrade.

These results establish the development baseline. Run the checklist below on the university's actual upgrade environment, with its theme, permissions, file storage and other plugins. Initial Behat scenarios exist; they have not been run locally.

An independent run on 2 October in the isolated Moodle 5.1 staging installation passed **8 tests and 28 assertions**, including the additional settings/dashboard tests. See [the compatibility review](moodle-5.1-review-2026-10-02.md) for scope and remaining acceptance checks.

## Installation and test preparation

- Install `dist/groupassign.zip` from the agreed Git commit. Its top-level directory must be `groupassign`, and its component must be `mod_groupassign`.
- For a source checkout on Moodle 5.1, use `public/mod/groupassign`. Avoid installing a raw repository ZIP under `groupassignment`.
- Record the Git commit, plugin version, Moodle build, PHP/database versions and theme in the test record.
- Use a disposable course, an academic, a restricted tutor, four students, and at least two groups. Use synthetic files and peer-review comments.
- Enable developer debugging in the upgrade environment for testing. Check displayed errors and server logs after each workflow.

## Smoke-test checklist

Record Pass / Fail / Not tested, tester, date and evidence for each ID. A checkbox alone means the test was executed, not that it passed.

| ID | Test | Expected result |
| --- | --- | --- |
| U01 | Install or upgrade the plugin; open site notifications and activity picker. | Upgrade finishes; Group assignment has a name and icon; no missing component, string or database errors. |
| U02 | Create an activity with online text and files, instructions, supporting files, dates, peer criteria and a points grade. Reopen and save settings. | Settings persist; TinyMCE renders; labels are readable; supporting files are available to students. |
| U03 | Configure automatic blank groups with a prefix, suffix and size limits. | Real course groups and a dedicated grouping are created once; saving again creates no duplicates. |
| U04 | Configure an existing grouping. Rename a group manually, then view the dashboard and save activity settings. | Correct groups are used; manual names and memberships survive. |
| U05 | Students join, leave and switch groups during the selection window. Attempt to join a full group and join outside the window. | Membership updates correctly; capacity and date restrictions apply; students see clear confirmation. |
| U06 | Submit online text and a file as a group member. View as another member, another group and the teacher. | Group members and authorised staff see the submission; unrelated students cannot access it or its files. |
| U07 | Test before submission opening, after due date but before cutoff, and after cutoff. | Opening and cutoff are enforced; a permitted late submission is marked late. |
| U08 | Submit a group assignment, then try leaving or switching groups. | Membership is locked where a group submission exists; the submission remains intact. |
| U09 | Grade a group with feedback; reload and inspect each member's Gradebook entry. | Grade and feedback persist; all members receive the shared result in one activity grade item. |
| U10 | Give one member an individual grade and explanation, then regrade the group. | Individual result and explanation persist as intended; other members retain the group grade. |
| U11 | Hide/release the grade item in Gradebook; view as students. Manually override a grade in Gradebook and save group grading again. | Visibility follows Gradebook; an explicit Gradebook override is preserved. |
| U12 | Create separate scale-graded and no-grade activities; save feedback and inspect Gradebook. | Scale choices map correctly; no-grade feedback works without an invalid numeric maximum. |
| U13 | Inspect grading options when creating and editing an activity. | Supported points, scale and no-grade choices are usable; rubric/marking-guide configuration is unavailable because advanced grading is deliberately disabled. |
| U14 | Students complete peer/self reviews, including required justification. Check teacher completion counts and concern flags. | Required fields are enforced; reviews persist; counts are correct; flags support follow-up without automatically adjusting grades. |
| U15 | Save activity settings after reviews exist. Remove a criterion that has reviews. | Existing reviews are retained; criteria are updated in place; removed reviewed criteria are archived without orphaning data. |
| U16 | Use the submissions search, status filters and pagination with multiple groups. | Results and counts agree; grading opens the selected group; no errors or unusably slow pages. |
| U17 | Test as a restricted tutor and an unrelated student, including direct URLs. | Access follows the intended role/group policy; unauthorised grading, membership changes and file access are denied. |
| U18 | Inspect calendar/timeline dates and activity logs after joining, submitting and grading. | Configured dates appear appropriately; recorded events identify the correct activity and actor. |
| U19 | Back up and restore with user data into a disposable course. Repeat without user data and with groups omitted. | IDs/files remap; no original-course data leaks; missing group setup is clearly surfaced and can be repaired. |
| U20 | Reset the disposable course, selecting plugin user-data reset. | Submissions, reviews and grades clear as requested; course group handling matches the selected options. |
| U21 | Use the university theme on a narrow screen; navigate forms and dashboard with a keyboard. | Controls are labelled and usable; focus is visible; no blocking clipping or editor failures. |

## Sign-off

Block pilot use for lost submissions/reviews, incorrect grades, cross-group disclosure, failed installation, or broken backup/restore. Assign an owner and retest every failure before sign-off. Record untested cases explicitly.

The activity remains an alpha prototype. Notifications, extensions, advanced grading and richer Assignment-style row actions are documented as deferred; do not assume native Assignment parity. This checklist is a UAT aid, not evidence that all journeys have already passed.
