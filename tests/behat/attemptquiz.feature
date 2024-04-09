@ou @ou_vle @qtype @qtype_combined
Feature: Attempt a Combined question
  As a teacher
  In order to check my Combined questions will work for students
  I need to attempt them

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
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype    | name                                  | template             |
      | Test questions   | combined | A combined question with show working | numericalshowworking |
    And the following "activities" exist:
      | activity | name      | course | idnumber |
      | quiz     | Test quiz | C1     | quiz1    |
    And quiz "Test quiz" contains the following questions:
      | A combined question with show working | 1 |

  @javascript
  Scenario: Preview a quiz with a combined question include show working editor.
    Given I am on the "Test quiz" "mod_quiz > View" page logged in as "admin"
    And I press "Preview quiz"
    And I should see "Showworking editor"
    When I set the following fields to these values:
      | Answer no1 | 5                                                                |
      | Answer no2 | 4                                                                |
      | Answer 5   | <p>The <b>cat</b> sat on the mat. Then it ate a <b>frog</b>.</p> |
    And I press "Finish attempt ..."
    And I press "Return to attempt"
    # Check when editing.
    And the following fields match these values:
      | Answer no1 | 5                                                                                    |
      | Answer no2 | 4                                                                                    |
      | Answer 5   | <p>The <strong>cat</strong> sat on the mat. Then it ate a <strong>frog</strong>.</p> |
    And I press "Finish attempt ..."
    And I press "Submit all and finish"
    # Please note this step below will be change in 4.1
    # When I click on "Submit all and finish" "button" in the "Submit all your answers and finish?" "dialogue"
    When I click on "Submit all and finish" "button" in the "Submit all your answers and finish?" "dialogue"
    Then the following fields match these values:
      | Answer no1 | 5                                                                |
      | Answer no2 | 4                                                                |
    And "sat on the mat" "text" should exist in the ".qtype_combined_response" "css_element"
    And I should see "The cat sat on the mat. Then it ate a frog."
