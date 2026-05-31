# Group Assignment Demo Notes

## Demo goal
Show a Moodle-native group assignment workflow that keeps staff in one activity while adding group formation, peer process evidence, and flexible grading controls.

## Pre-demo checklist (5 minutes)
- Confirm plugin is present: `mod_groupassign`.
- Confirm site + demo course completion tracking is enabled.
- Confirm at least 1 teacher account and 2 student accounts are enrolled in the demo course.
- Confirm test activity exists (example: `Group assignment`, course module id `49`).
- Confirm peer assessment is enabled in activity settings.

## Suggested demo flow (teacher perspective)
1. Open activity settings and highlight:
- Group formation modes (`Student self-selection`, `Create groups for teacher allocation`, `Use existing grouping`).
- Group naming controls (prefix + suffix format).
- Submission types and accepted file types.
- Peer assessment controls:
  - self-assessment toggle
  - comments toggle
  - require justification toggle
  - anonymous release toggle
  - student response toggle
  - per-criterion title, description, and scoring type
- Completion conditions (`Receive a grade`).

2. Open activity landing page and show:
- Dashboard sections (overview, groups/membership, submissions/grading, peer assessment).
- Collapsed-by-default structure for reduced visual overload.
- Grading summary block and direct path to submissions.

3. Open `Submissions` tab and show:
- Search + status filter.
- Native-style table shape (student name/email/status/timestamps/files/feedback/grade/actions).
- Row action menu (`...`) with `Grade`, `Edit submission`, `Grant extension`, and `Nudge`.

4. Open `Advanced grading` tab and explain:
- This is where Moodle-native advanced grading methods are attached for the activity.

## Suggested demo flow (student perspective)
1. Switch role to student (or use student account).
2. Open activity and show:
- Group selection notice and available groups.
- Join/switch flow.
3. After joining, show:
- Submission area for the group.
- Peer review entry point.
4. Open peer review and show:
- Criteria labels + descriptions.
- Criterion-specific scoring types.
- Justification behavior when enabled.

## Key messages for staff
- One activity instead of multiple disconnected tools.
- Familiar Moodle assignment mental model.
- Better visibility of group progress and process signals.
- Peer input supports teacher judgment; it does not auto-punish students.

## Key messages for developers
- Feature set is intentionally assignment-aligned first, then extended.
- Current implementation emphasizes workflow, UI direction, and data structures.
- Areas to harden next:
  - richer submission actions parity with core assignment
  - completion/grade edge-case handling
  - peer-risk heuristics tuning and auditability
  - tests and backup/restore coverage

## Backup talking points (if asked)
- Why no automatic peer-based regrading:
  - high contestability
  - low trust in opaque formulas
  - better to provide actionable flags + teacher override
- Why keep groups/groupings integrated:
  - reduces setup burden
  - keeps group lifecycle in the same activity context

## If time permits
- Show a second activity using a different peer scoring type.
- Show how this can support staged process-focused assessment design.
