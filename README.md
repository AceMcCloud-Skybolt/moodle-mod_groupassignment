# Group assignment prototype

`mod_groupassign` is an early Moodle activity prototype for group assignments that combine group formation, group submission, peer/self evaluation, and teacher-controlled grade adjustment in one activity.

## Current prototype slice

- Appears in the Moodle activity picker as **Group assignment**.
- Provides Assignment-like availability and grade settings.
- Provides group formation settings:
  - student self-selection
  - automatic blank group creation
  - use existing grouping
  - min/max group sizes
  - selection open/close dates
  - student join/leave/create permissions
- Automatically creates a dedicated Moodle grouping and blank groups when configured to manage groups.
- Teacher view shows a group management summary and group/member table.
- Student view lets students join/leave available groups and optionally create a group.

## Design direction

This prototype should keep Moodle's native Assignment mental model while reducing the setup burden around Groups and Groupings. Peer/self evaluation should initially be treated as structured evidence for teachers, not an automatic grade redistribution formula.

## Not implemented yet

- Group file/text submissions.
- Native Assignment-style submissions table and grader page.
- Peer/self evaluation criteria.
- Grade adjustment workflow and audit trail.
- Calendar/timeline events.
- Backup/restore.
