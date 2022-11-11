@ou @ou_vle @qtype @qtype_combined
Feature: Import and export combined questions
  As a teacher
  In order to reuse my combined questions
  I need to be able to import and export them

  Background:
    Given the following "users" exist:
      | username |
      | teacher  |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "course enrolments" exist:
      | user    | course | role           |
      | teacher | C1     | editingteacher |

  @javascript @_file_upload
  Scenario: Import and export combined questions
    When I am on the "Course 1" "core_question > course question import" page logged in as teacher
    And I set the field "id_format_xml" to "1"
    And I upload "question/type/combined/tests/fixtures/testquestion.moodle.xml" file to "Import" filemanager
    And I press "id_submitbutton"
    Then I should see "Parsing questions from import file."
    And I should see "Importing 1 questions from file"
    And I should see "What is the pH of a 0.1M solution? [[1:numeric:__10__]]."
    And I should see "Showworking [[5:showworking:__80x5__]]"
    And I press "Continue"
    And I should see "Imported Combined 001"
