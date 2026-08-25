@mod @mod_groupassign
Feature: Teachers and students can access a group assignment
  In order to coordinate a group assessment in one activity
  As a teacher or student
  I need role-appropriate group assignment views

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Terry | Teacher | teacher1@example.invalid |
      | student1 | Sam | Student | student1@example.invalid |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Group assignment course | GAC1 | 0 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | GAC1 | editingteacher |
      | student1 | GAC1 | student |
    And the following "activities" exist:
      | activity | name | intro | course | idnumber | numgroups | groupnameprefix |
      | groupassign | Team portfolio | Complete the portfolio as a group. | GAC1 | groupassign1 | 2 | Portfolio team |

  Scenario: A teacher sees the management dashboard
    When I am on the "Team portfolio" "groupassign activity" page logged in as teacher1
    Then I should see "Group management summary"
    And I should see "Dashboard overview"
    And I should see "Submissions and grading"

  Scenario: A student sees group selection and submission guidance
    When I am on the "Team portfolio" "groupassign activity" page logged in as student1
    Then I should see "Choose your group"
    And I should see "Portfolio team 1"
    And I should see "Portfolio team 2"
