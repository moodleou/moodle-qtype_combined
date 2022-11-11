@ou @ou_vle @qtype @qtype_combined
Feature: Test duplicating a quiz containing a Combined question
  As a teacher
  In order re-use my courses containing Combined questions
  I need to be able to backup and restore them

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype    | name                |
      | Test questions   | combined | A combined question |
    And the following "activities" exist:
      | activity   | name      | course | idnumber |
      | quiz       | Test quiz | C1     | quiz1    |
    And quiz "Test quiz" contains the following questions:
      | A combined question | 1 |

  @javascript
  Scenario: Backup and restore a course containing a Combined question
    When I am on the "Course 1" course page logged in as admin
    And I backup "Course 1" course using this options:
      | Confirmation | Filename | test_backup.mbz |
    And I restore "test_backup.mbz" backup into a new course using this options:
      | Schema | Course name       | Course 2 |
      | Schema | Course short name | C2       |
    And I am on the "Course 2" "core_question > course question bank" page
    And I choose "Edit question" action for "A combined question" in the question bank
    Then the following fields match these values:
      | Question name | A combined question   |
