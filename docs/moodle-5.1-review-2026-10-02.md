# Group Assignment: Moodle 5.1 compatibility review

## Scope and environment

Reviewed on 2 October 2026 against Moodle 5.1.4+ (Build: 20260604), PHP 8.2.4, MariaDB 10.11.11 and PHPUnit 11.5.55 in an isolated Windows test installation. No university-site data was accessed or changed.

This is compatibility evidence, not production certification. The institution's target Moodle patch release, theme, role configuration, integrations and upgrade path still require acceptance testing. See Moodle's [5.1 developer update](https://moodledev.io/docs/5.1/devupdate) for the upstream API changes.

## Automated evidence

- PHP syntax, Moodle coding standard (zero warnings), PHPDoc, plugin structure and upgrade savepoint checks pass.
- Literal language-string references were checked against the installed Moodle 5.1 string manager; no missing references remain. Dynamic identifiers need workflow testing too.
- Independent PHPUnit run: **8 tests, 28 assertions, zero failures**, on Moodle 5.1.4+, PHP 8.2.4 and MariaDB 10.11.11. This includes settings/completion rendering and staff-dashboard rendering. The separately installed Peerwork plugin emits a deprecated subplugin declaration notice.
- Template lint on Windows encountered an upstream mixed-path-separator limitation; Linux GitHub Actions runs the installed-plugin template checks.

## Changes

- Add settings/completion and staff-dashboard rendering regression tests.
- Add GitHub Actions for Moodle 5.1 on PHP 8.2 and 8.3, including coding, PHPDoc, template and PHPUnit checks.
- Preserve the current implementation's deliberate exclusion of advanced grading. A grading-area mapping exists, but FEATURE_ADVANCED_GRADING is false; this review does not enable an incomplete rubric workflow.

No runtime or database schema change; the existing release/version is retained.

## Acceptance before rollout

Follow [the UAT checklist](moodle-51-uat-smoke-tests.md). Verify group formation, grouping access, shared submission/editing, peer/self evaluation and individual grade adjustments with multiple real-role accounts. Exercise gradebook scales/no-grade, course copy/reset and backup/restore. Deferred notifications, extensions and advanced grading remain feature limitations, not completed work.
