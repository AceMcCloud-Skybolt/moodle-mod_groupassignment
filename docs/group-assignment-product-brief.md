# Group Assignment Product Brief

## Purpose of this document

This brief explains the educational and operational rationale for a Moodle-native **Group assignment** activity. It is intended to help developers, learning designers, academics, and support staff critique the concept before becoming buried in implementation detail.

The current GitHub prototype demonstrates one possible direction. This document describes the underlying problem, target use cases, minimum viable requirements, and key architecture questions.

## Problem Statement

Group assignments are common across higher education because they can support collaboration, communication, project management, discipline-authentic practice, and peer learning. However, staff often struggle to manage the full group assignment lifecycle inside Moodle.

The standard Moodle Assignment activity supports group submissions, but the broader workflow around group formation, progress visibility, collaboration evidence, peer/self evaluation, and fair grade adjustment is fragmented. Staff often need to combine multiple Moodle tools, third-party plugins, spreadsheets, email, manual Gradebook work, and local support guidance to run one group assessment.

This creates friction for everyone:

- Staff find setup confusing and risky.
- Students may not know where to choose a group, submit work, complete peer evaluation, or view feedback.
- Tutors have limited visibility of group progress and group dysfunction.
- Support teams repeatedly help staff configure Groups, Groupings, Assignment group submission settings, and peer evaluation tools.
- Grade adjustment decisions can become opaque, contested, or administratively heavy.

The product goal is to create one Moodle-native activity that supports the full group assignment workflow: group formation, group submission, peer process evidence, staff dashboarding, and teacher-controlled grading decisions.

## Current Moodle Gap

Moodle already has many of the building blocks:

- Groups and Groupings.
- Assignment group submissions.
- Group self-selection plugins.
- Peer assessment plugins such as Peerwork.
- Workshop activity.
- Rubrics and marking guides.
- Gradebook categories and grade items.
- Activity completion.
- Reports.

The problem is that these tools do not come together as one coherent staff/student workflow.

Common pain points include:

- Staff must understand that groups and groupings are different.
- Staff may need to create empty groups before students can self-select.
- Staff must assign those groups to a grouping before configuring the Assignment activity.
- Students often see separate activities for group selection, assignment submission, and peer evaluation.
- Peer assessment can feel disconnected from the submitted work.
- Existing peer contribution formulas can be difficult to explain and can damage student trust.
- Reports show data, but do not always help staff decide what to do next.
- Applying a group grade to everyone is easy, but making individual adjustments with transparent reasoning is harder.

A Group assignment activity should keep the familiar Moodle Assignment mental model while making the group-specific workflow explicit and integrated.

## Why Not Separate Tools?

Separate tools and plugins can work, but they increase fragmentation.

### Group Self-Selection

Group self-selection is useful, but staff often need support to create groups, groupings, limits, and selection windows correctly. The setup logic is not obvious to many academics.

### Peerwork and Similar Plugins

Peer evaluation plugins can provide a good student interface, anonymous feedback options, and useful peer evidence. However, contribution recalculation models can be difficult to justify, especially where grades are automatically redistributed based on peer scores.

Many staff want peer evidence to inform their judgement, not an opaque formula that automatically changes marks.

### Spreadsheets and Manual Processes

Spreadsheets are flexible but sit outside Moodle. They are hard to audit, easy to lose, and difficult to connect to Gradebook decisions.

### External Platforms

External platforms can be polished, but they introduce cost, training, authentication, data governance, support, and integration concerns.

The strategic argument for a Moodle-native solution is that group assessment is a core assessment workflow and should not require staff to stitch together several disconnected tools.

## Target Users

### Academics and Unit Coordinators

Academics need to set up group assignments without needing deep knowledge of Moodle Groups and Groupings. They need to monitor progress, manage submissions, collect peer evidence, and make grading decisions confidently.

### Tutors and Markers

Tutors need a clear view of group membership, submission status, peer review completion, concerns, and grading tasks. They need to know which groups or students require follow-up.

### Students

Students need one clear activity where they can choose or view their group, understand expectations, submit group work, complete peer/self review, and view feedback.

### Learning Designers

Learning designers need a consistent group assignment pattern they can recommend across disciplines, with settings that support good pedagogy without overwhelming staff.

### Moodle Administrators and Support Staff

Administrators need a plugin that follows Moodle conventions: capabilities, groups/groupings APIs, Gradebook, advanced grading, privacy API, backup/restore, events, logs, message providers, and upgrade safety.

## Core Use Cases

### Student Self-Selected Project Groups

Students choose their own group from a set of available groups. Staff configure the number of groups, naming convention, minimum/maximum size, and selection window inside the assignment setup.

The activity automatically creates the Moodle grouping and blank groups, reducing the need for staff to configure Groups and Groupings manually.

### Teacher-Allocated Groups

Staff create a set of blank groups for later teacher allocation. The activity manages the naming convention and grouping so the Assignment configuration remains consistent.

This is useful where staff need to balance skills, campuses, tutorial classes, placements, or project topics.

### Existing Grouping Reuse

Some units already have tutorial groups, lab groups, placement groups, or project teams. Staff can attach the activity to an existing grouping without recreating groups.

### Group Project Submission

A group submits one piece of work on behalf of all members. The activity supports familiar Assignment-style online text and file submissions.

Staff see a native-style submissions table with group-aware columns, status filters, quick grading, and actions.

### Peer and Self Evaluation

Students evaluate their own and peers' contributions against staff-defined criteria. Staff can configure criteria titles, descriptions, scoring types, comments, justification requirements, anonymity, and whether students can respond to released peer feedback.

The design should collect structured evidence without automatically redistributing grades.

### Staff Follow-Up and Risk Flags

The teacher dashboard highlights potential issues:

- groups with missing peer reviews
- underfilled or overfull groups
- students not in a group
- uneven peer ratings
- potential group dysfunction
- groups needing a nudge

The dashboard should support action, not just reporting.

### Group Grade With Individual Adjustment

By default, a group grade and feedback apply to all members. Staff can override an individual student's grade or feedback when there is evidence and a reason to do so.

This supports a clearer and more defensible grading workflow than automatic contribution weighting.

### Large-Class Group Assignment Management

In large classes, staff need filters, summaries, and action buttons. They should not need to inspect every group manually to find problems.

## MVP Requirements

The minimum viable version should focus on one coherent group assignment workflow rather than trying to replace every group-related Moodle tool.

### Activity Setup

- Staff can create a Group assignment from the Moodle activity picker.
- The activity includes familiar Assignment-style settings:
  - description
  - activity instructions
  - additional files
  - availability
  - submission types
  - feedback types
  - grade
  - advanced grading
  - notifications
  - activity completion
- Staff can configure group formation inside the activity:
  - student self-selection
  - teacher-managed blank groups
  - use existing grouping
- Staff can configure group naming:
  - prefix
  - numeric suffix
  - letter suffix
- Staff can configure minimum and maximum members.
- Staff can configure selection open and close dates.
- Staff can choose whether students can join, leave, create, rename, or describe groups.
- The activity can create and manage a Moodle grouping automatically.

### Student Workflow

- Students see group selection and assignment submission in one activity.
- Students can join or switch groups where permitted.
- Students can see whether a group is full or underfilled.
- Students can see group membership where enabled.
- Students submit group work through familiar online text and/or file submission controls.
- Students complete peer/self review in the same activity where enabled.
- Students can view released group feedback and, where enabled, peer feedback.
- Students can respond to released peer feedback where enabled.

### Teacher Dashboard

- Teachers see a dashboard overview with:
  - number of groups
  - number of participants
  - students without a group
  - empty groups
  - underfilled groups
  - overfull groups
  - submitted groups
  - groups needing grading
  - peer review completion
  - peer concern flags
- Dashboard sections use clear Moodle-style headings and accordions.
- Dashboard sections are collapsed by default where this reduces overload.
- Dashboard outputs include actions such as Grade, Nudge, or view submissions.

### Submissions and Grading

- The submissions page should resemble Moodle Assignment's submissions page.
- The page should include:
  - search users
  - name filtering
  - status filtering
  - advanced filtering where feasible
  - quick grading where feasible
  - row action menu
  - group, status, grade, submission files, feedback, and final grade columns
- Staff can grade a group submission.
- By default, the grade and feedback apply to all group members.
- Staff can choose individual grade/feedback adjustments for specific members.
- Individual adjustments require or encourage an explanation.
- Grades flow to the Moodle Gradebook.

### Peer Assessment

- Staff can enable or disable peer assessment.
- Staff can include or exclude self-assessment.
- Staff can allow comments.
- Staff can require justification for ratings.
- Staff can choose whether released feedback is anonymous to students.
- Staff can allow students to respond to released feedback.
- Staff can define up to a small, manageable number of criteria.
- Criteria include:
  - title
  - description
  - scoring type
- Scoring types should be simple and explainable, for example:
  - four-level contribution scale
  - satisfactory scale
  - marks out of 5
- Peer assessment data should support staff judgement, not automatically recalculate grades in MVP.

### Moodle Integration

The MVP should follow Moodle conventions for:

- groups and groupings APIs
- capabilities
- activity completion
- Gradebook
- advanced grading
- privacy API
- backup and restore
- logs/events
- message providers
- course index and activity navigation
- Moodle editor and file API

## Future and Non-MVP Ideas

### Better Progress Tracking

The activity could support progress checkpoints, group contracts, meeting logs, milestone updates, or lightweight status updates.

### Richer Analytics

Future dashboards could show:

- groups with inconsistent peer scores
- groups with missing members
- students repeatedly receiving low contribution ratings
- students rating everyone unusually low or unusually high
- groups with no activity close to deadline
- submission and peer review bottlenecks

### Nudge Workflows

Teachers could trigger Moodle messages from dashboard filters:

- nudge students not in a group
- nudge groups without submissions
- nudge students who have not completed peer review
- nudge groups flagged for follow-up

### Student Response and Dispute Handling

Where peer feedback is released, students may need a structured way to respond. Future work could support teacher moderation, response deadlines, and audit trails.

### Templates

Reusable templates could support common patterns:

- presentation groups
- project teams
- lab groups
- capstone teams
- placement groups
- peer evaluation only
- group formation only

### Group Process Assignment

There may be a future relationship with staged process assessment, where groups submit project milestones over time. This should be treated as a future extension after the core group assignment workflow is stable.

### Integration With Existing Peer Tools

It may be worth exploring whether existing plugins such as Peerwork can inform the interface or data model, while avoiding automatic grade redistribution formulas that staff find difficult to explain.

## Non-Goals for MVP

The MVP should not attempt to:

- replace every feature of Moodle Assignment immediately
- replace Moodle Workshop
- create complex automatic contribution-weighted grading
- solve all group conflict management
- become a full project management tool
- infer collaboration quality automatically
- integrate every external peer assessment model
- replace all Moodle Groups and Groupings administration

The first goal is a robust, understandable, Moodle-native group assignment workflow.

## Pedagogical Rationale

Group assessment can support authentic professional learning when students must coordinate roles, communicate, negotiate, produce shared work, and reflect on contribution. However, group assessment can also generate anxiety and fairness concerns.

A good Moodle-native Group assignment should support:

- transparent group formation
- clear expectations
- visibility of progress
- structured peer/self reflection
- early identification of group trouble
- teacher judgement rather than opaque formulas
- fair individual adjustments where justified
- reduced administrative burden for staff

The key pedagogical principle is that peer evidence should inform teaching and judgement. It should help staff ask better questions and intervene earlier, rather than automatically punish students through a formula they do not understand.

## Questions for Developers

### Architecture

- Should this remain a standalone activity module, or should it extend Moodle Assignment?
- Can core Assignment submission and grading behaviours be reused safely?
- Which Assignment features are essential before a pilot?
- How much should this plugin manage Moodle Groups and Groupings directly?

### Groups and Groupings

- What is the safest way for the activity to create, update, and track groups?
- What should happen if staff manually edit groups created by the activity?
- How should the activity handle existing groupings?
- Should deleting the activity delete managed groups, archive them, or leave them untouched?

### Gradebook

- Should the plugin create one Gradebook item only?
- How should individual member adjustments be represented?
- How should group feedback and individual feedback appear in Gradebook?
- How should hidden grade items, grade categories, and manual overrides be handled?

### Peer Assessment

- How should peer review criteria, ratings, comments, anonymity, and responses be stored?
- What data should be visible to students, tutors, and teachers?
- What risk flags are useful without becoming pseudo-automated grading?
- Should peer assessment be required before grading, after grading, or independently configurable?

### Dashboard and Scale

- How should dashboard counts be calculated in large classes?
- Should status summaries be calculated live, cached, or stored?
- What indexes are needed for common filters?
- How should separate groups mode affect visibility?

### Backup, Restore, and Privacy

- Which data must be included in backup and restore?
- How should group membership, groupings, submissions, peer reviews, and grade adjustments be restored?
- What privacy exports and deletions are required for peer reviews and individual adjustments?

### Moodle UX

- How closely should the submissions page match core Assignment?
- Should the secondary navigation exactly mirror Assignment?
- How can settings remain understandable for staff who do not understand Groups and Groupings?
- What wording best explains teacher-managed blank groups versus student self-selection?

## Suggested Review Framing

When asking developers and learning designers to review this concept, the most useful questions are:

- Is the underlying problem common enough to justify a plugin?
- Would this reduce support burden compared with current Moodle workarounds?
- Would academics understand the setup without extensive training?
- Would students understand where to choose groups, submit work, and complete peer review?
- Does the grade adjustment model feel fair and explainable?
- Which features are essential for a pilot?
- Which features should remain future ideas?

## Summary

The current prototype demonstrates a possible Moodle-native group assignment workflow. The next step is requirements critique.

The core idea is:

> Staff need one Moodle-native activity for group formation, group submission, peer process evidence, staff dashboarding, and teacher-controlled grade adjustment.

The prototype is useful because it makes the idea tangible. This brief is useful because it gives developers and stakeholders something to challenge before implementation decisions harden.
