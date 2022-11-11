@ou @ou_vle @qtype @qtype_combined
Feature: Test creating a Combined question
  As a teacher
  In order to test my students in flexible ways
  I need to be able to create a Combined question

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

  @javascript
  Scenario: Create a Combined question with a full range of sub-parts
    When I am on the "Course 1" "core_question > course question bank" page logged in as teacher
    And I press "Create a new question ..."
    And I set the field "Combined" to "1"
    And I click on "Add" "button" in the "Choose a question type to add" "dialogue"
    And I should see "Adding a combined question"
    And I set the field "Question name" to "New combined question"
    And I set the field "Question text" to "What is the pH of a 0.1M solution? [[1:numeric:__10__]]<br/>What is the IUPAC name of the molecule? [[2:pmatch:__20__]]<br/>Which elements are shown? [[3:multiresponse]]<br/>Which element is shown as white? [[6:singlechoice]]<br/>When a solution is combined with oil the result is a [[4:selectmenu:2]]<br/>Showworking [[5:showworking:__80x5__]]"
    And I set the field "General feedback" to "The molecule is ethanoic acid which is more commonly known as acetic acid or in dilute solution as vinegar. The constituent elements are carbon (grey), hydrogen (white) and oxygen (red). A 0.1M solution has a pH of 2.88 and when a solution is combined with oil the result is a vinaigrette."
    And I press "Update the form"

    # Follow sub-questions (The order of sub-questions comes from the question text).
    # Numeric part.
    And I expand all fieldsets
    And I set the following fields to these values:
      | id_subqnumeric1defaultmark     | 20%                                     |
      | id_subqnumeric1answer_0        | 2.88                                    |
      | Scientific notation            | No                                      |
      | id_subqnumeric1generalfeedback | You have the incorrect value for the pH |

    # Pattern-match part.
    And I set the following fields to these values:
      | id_subqpmatch2defaultmark     | 20%                                |
      | Spell checking                | Do not check spelling of student   |
      | id_subqpmatch2answer_0        | match_mw (ethanoic acid)           |
      | id_subqpmatch2generalfeedback | You have the incorrect IUPAC name. |
      | Model answer                  | ethanoic acid                      |

    # Multi-response part.
    And I press "Blanks for 3 more choices"
    And I set the following fields to these values:
      | id_subqmultiresponse3defaultmark     | 20%                                              |
      | id_subqmultiresponse3answer_0        | C/carbon                                         |
      | id_subqmultiresponse3correctanswer_0 | 1                                                |
      | id_subqmultiresponse3answer_1        | H/hydrogen                                       |
      | id_subqmultiresponse3correctanswer_1 | 1                                                |
      | id_subqmultiresponse3answer_2        | O/oxygen                                         |
      | id_subqmultiresponse3correctanswer_2 | 1                                                |
      | id_subqmultiresponse3answer_3        | N/nitrogen                                       |
      | id_subqmultiresponse3answer_4        | F/fluorine                                       |
      | id_subqmultiresponse3answer_5        | Cl/chlorine                                      |
      | id_subqmultiresponse3answer_6        | <b>Br/bromine</b>                                |
      | id_subqmultiresponse3generalfeedback | Your choice of elements is not entirely correct. |

    # Select missing words part.
    And I set the following fields to these values:
      | id_subqselectmenu4defaultmark        | 20%           |
      | id_subqselectmenu4answer_0           | Wine          |
      | id_subqselectmenu4answer_1           | Vinagrette    |
      | id_subqselectmenu4answer_2           | Paint Thinner |
      | id_subqselectmenu4answer_3           | Mayonnaise    |
      | id_subqselectmenu4generalfeedback    |Your name for the mixture is incorrect. |

    # Single choice part.
    And I set the following fields to these values:
      | id_subqsinglechoice6defaultmark     | 20%                                         |
      | id_subqsinglechoice6answer_0        | C carbon                                    |
      | id_subqsinglechoice6fraction_0      | 0.0                                         |
      | id_subqsinglechoice6feedback_0      | Carbon is conventionally black              |
      | id_subqsinglechoice6answer_1        | H hydrogen                                  |
      | id_subqsinglechoice6fraction_1      | 1.0                                         |
      | id_subqsinglechoice6feedback_1      | That is correct                             |
      | id_subqsinglechoice6answer_2        | <b>O oxygen</b>                             |
      | id_subqsinglechoice6fraction_2      | 0.0                                         |
      | id_subqsinglechoice6feedback_2      | Oxygen is conventionally red                |
      | id_subqsinglechoice6generalfeedback | Your name for the white atoms is incorrect. |

    # Set hints for Multiple tries
    And I set the field "Hint 1" to "First hint"
    And I set the field "id_hintclearwrong_0" to "1"
    And I set the field "Hint 2" to "Second hint"
    And I set the field "id_hintclearwrong_1" to "1"

    And I press "id_submitbutton"
    Then I should see "New combined question"
