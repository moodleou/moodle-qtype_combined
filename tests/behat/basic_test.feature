@ou @ou_vle @qtype @qtype_combined
Feature: Test all the basic functionality of combined question type
  In order to evaluate students responses, As a teacher I need to
  create and preview combined (Combined) questions.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email               |
      | teacher1 | T1        | Teacher1 | teacher1@moodle.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |

  @javascript
  Scenario: Create, edit and preview a combined question.
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I navigate to "Question bank" in current page administration
    Then I press "Create a new question ..."
    And I set the field "Combined" to "1"
    And I click on "Add" "button" in the "Choose a question type to add" "dialogue"
    Then I should see "Adding a combined question"
    And I set the field "Question name" to "Combined 001"
    And I set the field "Question text" to "What is the pH of a 0.1M solution? [[1:numeric:__10__]]. <br/>What is the IUPAC name of the molecule? [[2:pmatch:__20__]]. <br/>Which elements are shown? [[3:multiresponse]]. <br/>When a solution is combined with oil the result is a [[4:selectmenu:2]]"
    Then I set the field "General feedback" to "The molecule is ethanoic acid which is more commonly known as acetic acid or in dilute solution as vinegar. The constituent elements are carbon (grey), hydrogen (white) and oxygen (red). A 0.1M solution has a pH of 2.88 and when a solution is combined with oil the result is a vinaigrette."
    And I press "Verify the question text and update the form"

    # Follow sub questions (The order of sub questions comes from the question text).
    # Numeric part.
    Then I follow "'numeric' input '1'"
    And I set the following fields to these values:
      | id_subqnumeric1defaultmark     | 25%                                     |
      | id_subqnumeric1answer_0        | 2.88                                    |
      | Scientific notation            | No                                      |
      | id_subqnumeric1generalfeedback | You have the incorrect value for the pH |

    # Pmatch part.
    Then I follow "'pmatch' input '2'"
    And I set the following fields to these values:
      | id_subqpmatch2defaultmark     | 25%                                |
      | Check spelling of student     | No                                 |
      | id_subqpmatch2answer_0        | match_mw (ethanoic acid)           |
      | id_subqpmatch2generalfeedback | You have the incorrect IUPAC name. |

    # Multiresponse part.
    Then I follow "'multiresponse' input '3'"
    And I click on "Expand all" "link"
    And I press "Blanks for 3 more choices"
    And I set the following fields to these values:
      | id_subqmultiresponse3defaultmark     | 25%                                              |
      | id_subqmultiresponse3answer_0        | carbon                                           |
      | id_subqmultiresponse3correctanswer_0 | 1                                                |
      | id_subqmultiresponse3answer_1        | hydrogen                                         |
      | id_subqmultiresponse3correctanswer_1 | 1                                                |
      | id_subqmultiresponse3answer_2        | oxygen                                           |
      | id_subqmultiresponse3correctanswer_2 | 1                                                |
      | id_subqmultiresponse3answer_3        | nitrogen                                         |
      | id_subqmultiresponse3answer_4        | fluorine                                         |
      | id_subqmultiresponse3answer_5        | chlorine                                         |
      | id_subqmultiresponse3answer_6        | <b>bromine</b>                                   |
      | id_subqmultiresponse3generalfeedback | Your choice of elements is not entirely correct. |

    # Selectmenu part.
    Then I follow "'selectmenu' input '4'"
    And I set the following fields to these values:
      | id_subqselectmenu4defaultmark        | 25%           |
      | id_subqselectmenu4answer_0           | Wine          |
      | id_subqselectmenu4answer_1           | Vinagrette    |
      | id_subqselectmenu4answer_2           | Paint Thinner |
      | id_subqselectmenu4answer_3           | Mayonnaise    |
      | id_subqselectmenu4generalfeedback    |Your name for the mixture is incorrect. |

    # Set hints for Multiple tries
    And I follow "Multiple tries"
    And I set the field "Hint 1" to "First hint"
    And I set the field "Hint 2" to "Second hint"

    And I press "id_submitbutton"
    Then I should see "Combined 001"

    # Preview it.
    When I click on "Preview" "link" in the "Combined 001" "table_row"
    And I switch to "questionpreview" window

    # Set display and behaviour options
    And I set the following fields to these values:
      | How questions behave | Interactive with multiple tries |
      | Marked out of        | 3                               |
      | Marks                | Show mark and max               |
      | Specific feedback    | Shown                           |
      | Right answer         | Shown                           |
    And I press "Start again with these options"

    # Attempt the question
    # Test html editor for answer field in Combined MultiResponse.
    And "//label/b[contains(text(), 'bromine')]" "xpath_element" should be visible
    And I set the field "Answer 1" to "2.88"
    And I set the field "Answer 2" to "ethanoic acid"
    And I set the field "Answer 4" to "Vinagrette"
    And I press "Check"
    Then I should see "Part of your answer requires attention :"
    And I should see "Input 3 (check box group) - Please select at least one answer."

    And I set the following fields to these values:
      | carbon            | 1             |
      | oxygen            | 1             |
    And I press "Check"
    Then I should see "Your answer is partially correct."
    And I should see "Your choice of elements is not entirely correct."
    And I should see "First hint"

    When I press "Try again"
    And I set the following fields to these values:
      | hydrogen | 1 |
    Then I press "Check"
    And I should see "Your answer is correct."
    And I should see "The molecule is ethanoic acid which is more commonly known as acetic acid or in dilute solution as vinegar. The constituent elements are carbon (grey), hydrogen (white) and oxygen (red). A 0.1M solution has a pH of 2.88 and when a solution is combined with oil the result is a vinaigrette."
    And I switch to the main window

    # Backup the course and restore it.
    When I log out
    And I log in as "admin"
    When I backup "Course 1" course using this options:
      | Confirmation | Filename | test_backup.mbz |
    When I restore "test_backup.mbz" backup into a new course using this options:
      | Schema | Course name | Course 2 |
    Then I should see "Course 2"
    When I navigate to "Question bank" in current page administration
    Then I should see "Combined 001"

    # Edit the copy and verify the form field contents.
    When I click on "Edit" "link" in the "Combined 001" "table_row"
    Then the following fields match these values:
      | Question name   | Combined 001 |
      | Question text   | What is the pH of a 0.1M solution? [[1:numeric:__10__]]. <br/>What is the IUPAC name of the molecule? [[2:pmatch:__20__]]. <br/>Which elements are shown? [[3:multiresponse]]. <br/>When a solution is combined with oil the result is a [[4:selectmenu:2]] |

      | id_subqnumeric1defaultmark     | 25%                                     |
      | id_subqnumeric1answer_0        | 2.88                                    |
      | Scientific notation            | No                                      |
      | id_subqnumeric1generalfeedback | You have the incorrect value for the pH |

      | id_subqpmatch2defaultmark     | 25%                                |
      | Check spelling of student     | No                                 |
      | id_subqpmatch2answer_0        | match_mw (ethanoic acid)           |
      | id_subqpmatch2generalfeedback | You have the incorrect IUPAC name. |

      | id_subqmultiresponse3defaultmark     | 25%                                                                                                                                                                                                                                                         |
      | id_subqmultiresponse3answer_0        | carbon                                                                                                                                                                                                                                                      |
      | id_subqmultiresponse3correctanswer_0 | 1                                                                                                                                                                                                                                                           |
      | id_subqmultiresponse3answer_1        | hydrogen                                                                                                                                                                                                                                                    |
      | id_subqmultiresponse3correctanswer_1 | 1                                                                                                                                                                                                                                                           |
      | id_subqmultiresponse3answer_2        | oxygen                                                                                                                                                                                                                                                      |
      | id_subqmultiresponse3correctanswer_2 | 1                                                                                                                                                                                                                                                           |
      | id_subqmultiresponse3answer_3        | nitrogen                                                                                                                                                                                                                                                    |
      | id_subqmultiresponse3answer_4        | fluorine                                                                                                                                                                                                                                                    |
      | id_subqmultiresponse3answer_5        | chlorine                                                                                                                                                                                                                                                    |
      | id_subqmultiresponse3answer_6        | <b>bromine</b>                                                                                                                                                                                                                                              |
      | id_subqmultiresponse3generalfeedback | Your choice of elements is not entirely correct.                                                                                                                                                                                                            |

      | id_subqselectmenu4defaultmark        | 25%           |
      | id_subqselectmenu4answer_0           | Wine          |
      | id_subqselectmenu4answer_1           | Vinagrette    |
      | id_subqselectmenu4answer_2           | Paint Thinner |
      | id_subqselectmenu4answer_3           | Mayonnaise    |
      | id_subqselectmenu4generalfeedback    |Your name for the mixture is incorrect. |

      | Hint 1          | First hint                    |
      | Hint 2          | Second hint                   |

    And I set the following fields to these values:
      | Question name | Edited question name |
    And I press "id_submitbutton"
    Then I should see "Edited question name"

  @javascript
  Scenario: Test pmatch combine question for convert and synonyms.
    Given I log in as "teacher1"
    When I am on "Course 1" course homepage
    And I navigate to "Question bank" in current page administration
    And I press "Create a new question ..."
    And I set the field "Combined" to "1"
    And I click on "Add" "button" in the "Choose a question type to add" "dialogue"
    And I should see "Adding a combined question"
    And I set the field "Question name" to "Combined 001"
    And I set the field "Question text" to "[[1:pmatch]]"
    And I press "Verify the question text and update the form"
    And I follow "'pmatch' input '1'"
    And I set the following fields to these values:
      | Weighting                           | 100%              |
      | Check spelling of student           | No                |
      | Answer                              | match(number ten) |
      | Feedback for any incorrect response | General feedback  |
      | Word                                | ten               |
      | Synonyms                            | 10                |
    And I press "Save changes and continue editing"
    # Preview it.
    And I follow "Preview"
    # Check entering the correct answer.
    And I switch to "questionpreview" window
    And I set the field "Answer 1" to "number ten"
    And I press "Submit and finish"
    Then I should see "Your answer is correct."
    # Check entering using synonyms feature.
    When I press "Start again"
    And I set the field "Answer 1" to "number 10"
    And I press "Submit and finish"
    Then I should see "Your answer is correct."
    # Check entering using convert to space feature.
    When I press "Start again"
    And I set the field "Answer 1" to "number;ten"
    And I press "Submit and finish"
    Then I should see "Your answer is correct."
    # Check entering incorrect answer.
    When I press "Start again"
    And I set the field "Answer 1" to "number_ten"
    And I press "Submit and finish"
    And I should see "Your answer is incorrect."
    And I switch to the main window

  @javascript
  Scenario: Test duplicating a combined question and editing subquestions before saving
    Given I log in as "teacher1"
    When I am on "Course 1" course homepage
    And I navigate to "Question bank" in current page administration
    And I press "Create a new question ..."
    And I set the field "Combined" to "1"
    And I click on "Add" "button" in the "Choose a question type to add" "dialogue"
    And I should see "Adding a combined question"
    And I set the field "Question name" to "Duplication test"
    And I set the field "Question text" to "Cat: [[1:selectmenu:1]], Dog: [[2:selectmenu:1]]"
    And I press "Verify the question text and update the form"
    And I expand all fieldsets
    And I set the following fields to these values:
      | id_subqselectmenu1defaultmark | 50%     |
      | id_subqselectmenu1answer_0    | Kitten  |
      | id_subqselectmenu1answer_1    | Tadpole |
      | id_subqselectmenu2defaultmark | 50%     |
      | id_subqselectmenu2answer_0    | Puppy   |
      | id_subqselectmenu2answer_1    | Foal    |
    And I press "id_submitbutton"
    And I click on "Duplicate" "link" in the "Duplication test" "table_row"
    And I set the field "Question name" to "The new question"
    And I set the field "Question text" to "[[1:selectmenu:1]]"
    And I expand all fieldsets
    And I set the following fields to these values:
      | id_subqselectmenu1defaultmark | 100%    |
      | id_subqselectmenu1answer_0    | Kitten  |
      | id_subqselectmenu1answer_1    | Tadpole |
      | id_subqselectmenu2defaultmark | 50%     |
      | id_subqselectmenu2answer_0    | Puppy   |
      | id_subqselectmenu2answer_1    | Foal    |
    And I press "id_submitbutton"
    Then I should see "One or more embedded questions have been removed from the question text"
    And I press "id_submitbutton"
    And I click on "Preview" "link" in the "Duplication test" "table_row"
    # Check entering the correct answer for original question.
    And I switch to "questionpreview" window
    And I set the field "Answer 1" to "Kitten"
    And I set the field "Answer 2" to "Puppy"
    And I press "Submit and finish"
    And I should see "Your answer is correct."
    And I switch to the main window
    And I click on "Preview" "link" in the "The new question" "table_row"
    # Check entering the correct answer for original question.
    And I switch to "questionpreview" window
    And I set the field "Answer 1" to "Kitten"
    And I press "Submit and finish"
    And I should see "Your answer is correct."
    And I switch to the main window
