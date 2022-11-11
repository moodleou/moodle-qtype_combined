@ou @ou_vle @qtype @qtype_combined
Feature: Preview a Combined question
  As a teacher
  In order to check my Combined questions will work for students
  I need to preview them

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
      | questioncategory | qtype    | name                |
      | Test questions   | combined | A combined question |

  @javascript
  Scenario: Preview a Combined question and submit a partially correct, then correct response
    Given I am on the "A combined question" "core_question > preview" page logged in as teacher
    And I expand all fieldsets
    And I set the following fields to these values:
      | How questions behave | Interactive with multiple tries |
      | Marked out of        | 3                               |
      | Marks                | Show mark and max               |
      | Specific feedback    | Shown                           |
      | Right answer         | Shown                           |
    And I press "Start again with these options"

    # Test validation that all parts are answered by submitting an incomplete response.
    When I set the field "Answer 1" to "2.88"
    And I set the field "Answer 2" to "ethanoic acid"
    And I set the field "Answer 4" to "Vinagrette"
    And I click on "H hydrogen" "qtype_multichoice > Answer"
    And "bromine" "qtype_multichoice > Answer" should be visible
    And "O oxygen" "qtype_multichoice > Answer" should be visible
    And I press "Check"

    Then I should see "Part of your answer requires attention:"
    And I should see "Part 3 - Please select at least one answer."

    # Test submitting a partially correct response.
    And I click on "C/carbon" "qtype_multichoice > Answer"
    And I click on "O/oxygen" "qtype_multichoice > Answer"
    And I press "Check"
    And I should see "Parts, but only parts, of your response are correct."
    And I should see "Your choice of elements is not entirely correct."
    And I should see "First hint"

    # Test submitting a correct response on the second try.
    And I press "Try again"
    And I click on "H/hydrogen" "qtype_multichoice > Answer"
    And I press "Check"
    And I should see "Well done!"
    And I should see "The molecule is ethanoic acid which is more commonly known as acetic acid or in dilute solution as vinegar. The constituent elements are carbon (grey), hydrogen (white) and oxygen (red). A 0.1M solution has a pH of 2.88 and when a solution is combined with oil the result is a vinaigrette."

  @javascript
  Scenario: Preview a Combined question and test the clear incorrect option
    Given I am on the "A combined question" "core_question > preview" page logged in as teacher
    And I expand all fieldsets
    And I set the following fields to these values:
      | How questions behave | Interactive with multiple tries |
      | Marked out of        | 3                               |
      | Marks                | Show mark and max               |
      | Specific feedback    | Shown                           |
      | Right answer         | Shown                           |
    And I press "Start again with these options"

    When I set the field "Answer 1" to "2.7"
    And I set the field "Answer 2" to "formic acid"
    And I set the field "Answer 4" to "Wine"
    And I click on "C carbon" "qtype_multichoice > Answer"
    And I click on "Br/bromine" "qtype_multichoice > Answer"
    And I click on "C/carbon" "qtype_multichoice > Answer"
    And I press "Check"
    And I should see "Parts, but only parts, of your response are correct."
    And I should see "First hint"
    And I press "Try again"

    Then the field "Answer 1" matches value ""
    And the field "Answer 2" matches value ""
    And the field "Answer 4" matches value ""
    And "//div[@data-region='answer-label']//div[contains(text(), 'C carbon')]/ancestor::div/input[@checked='checked']" "xpath_element" should not be visible
    And "//div[@data-region='answer-label']//div[contains(text(), 'Br/bromine')]/ancestor::div/input[@checked='checked']" "xpath_element" should not be visible
    And "//div[@data-region='answer-label']//div[contains(text(), 'C/carbon')]/ancestor::div/input[@checked='checked']" "xpath_element" should be visible

  @javascript
  Scenario: Preview a Combined question and test Fill in correct responses option
    Given I am on the "A combined question" "core_question > preview" page logged in as teacher
    And I expand all fieldsets
    And I set the following fields to these values:
      | How questions behave | Interactive with multiple tries |
      | Marked out of        | 3                               |
      | Marks                | Show mark and max               |
      | Specific feedback    | Shown                           |
      | Right answer         | Shown                           |
    And I press "Start again with these options"

    When I press "Fill in correct responses"
    Then the field "Answer 2" matches value "ethanoic acid"
    And "//div[@data-region='answer-label']//div[contains(text(), 'H hydrogen')]/ancestor::div/input[@checked='checked']" "xpath_element" should be visible

  Scenario: Synonyms and other Pmatch features work within a Combined question
    Given the following "questions" exist:
      | questioncategory | qtype    | template       | name            |
      | Test questions   | combined | pmatchsynonyms | Combined pmatch |
    And I am on the "Combined pmatch" "core_question > preview" page logged in as teacher

    # Check entering exactly the expected answer.
    When I set the field "Answer 1" to "number ten"
    And I press "Submit and finish"
    Then I should see "Well done!"

    # Check entering using synonyms feature.
    And I press "Start again"
    And I set the field "Answer 1" to "number 10"
    And I press "Submit and finish"
    And I should see "Well done!"

    # Check entering using convert to space feature.
    And I press "Start again"
    And I set the field "Answer 1" to "number;ten"
    And I press "Submit and finish"
    And I should see "Well done!"

    # Check entering incorrect answer.
    And I press "Start again"
    And I set the field "Answer 1" to "number_ten"
    And I press "Submit and finish"
    And I should see "That is not right at all."

  Scenario: Test showing the warning message if the answer to part of a Combined question is empty
    Given I am on the "A combined question" "core_question > preview" page logged in as teacher
    And I expand all fieldsets
    And I set the following fields to these values:
      | How questions behave | Immediate feedback |
    And I press "Start again with these options"

    When I press "Check"
    Then I should see "Parts of your answer require attention:"
    And I should see "Part 1 - Please enter an answer."
    And I should see "Part 2 - Please enter an answer."
    And I should see "Part 3 - Please select at least one answer."
    And I should see "Part 4 - Please put an answer in each box."
    And I should not see "Part 5 - Please select an answer."

  Scenario: Test showing the warning message if the answer to part of a Combined question is malformed
    Given the following "questions" exist:
      | questioncategory | qtype    | template  | name               |
      | Test questions   | combined | numerical | Combined numerical |
    And I am on the "Combined numerical" "core_question > preview" page logged in as teacher
    And I expand all fieldsets
    And I set the following fields to these values:
      | How questions behave | Immediate feedback |
    And I press "Start again with these options"

    When I set the following fields to these values:
      | Answer no1 | 6,5    |
      | Answer no2 | eleven |
    And I press "Check"
    Then I should see "Parts of your answer require attention:"
    And I should see "Part no1 - You have used an illegal thousands separator \",\" in your answer. We only accept answers with a decimal separator \".\"."
    And I should see "Part no2 - You have not entered a number in a recognised format."
