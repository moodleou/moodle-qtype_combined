@ou @ou_vle @qtype @qtype_combined
Feature: Test editing a Combined question
  As a teacher
  In order to be able to update my Combined questions
  I need to edit them

  Background:
    Given the following "users" exist:
      | username |
      | teacher  |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
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
  Scenario: Edit a Combined question
    When I am on the "A combined question" "core_question > edit" page logged in as teacher
    And the following fields match these values:
      | Question name   | A combined question                                                                                                                                                                                                                                                                                                                                      |
      | Question text   | What is the pH of a 0.1M solution? [[1:numeric:__10__]]<br/>What is the IUPAC name of the molecule? [[2:pmatch:__20__]]<br/>Which elements are shown? [[3:multiresponse]]<br/>Which element is shown as white? [[6:singlechoice]]<br/>When a solution is combined with oil the result is a [[4:selectmenu:2]]<br/>Showworking [[5:showworking:__80x5__]] |

      | id_subqnumeric1defaultmark     | 20%                                     |
      | id_subqnumeric1answer_0        | 2.88                                    |
      | Scientific notation            | No                                      |
      | id_subqnumeric1generalfeedback | You have the incorrect value for the pH |

      | id_subqpmatch2defaultmark     | 20%                                |
      | Spell checking                | Do not check spelling of student   |
      | id_subqpmatch2answer_0        | match_mw (ethanoic acid)           |
      | id_subqpmatch2generalfeedback | You have the incorrect IUPAC name. |

      | id_subqmultiresponse3defaultmark     | 20%                                                                                                                                                                                                                                                         |
      | id_subqmultiresponse3answer_0        | C/carbon                                                                                                                                                                                                                                                    |
      | id_subqmultiresponse3correctanswer_0 | 1                                                                                                                                                                                                                                                           |
      | id_subqmultiresponse3answer_1        | H/hydrogen                                                                                                                                                                                                                                                  |
      | id_subqmultiresponse3correctanswer_1 | 1                                                                                                                                                                                                                                                           |
      | id_subqmultiresponse3answer_2        | O/oxygen                                                                                                                                                                                                                                                    |
      | id_subqmultiresponse3correctanswer_2 | 1                                                                                                                                                                                                                                                           |
      | id_subqmultiresponse3answer_3        | N/nitrogen                                                                                                                                                                                                                                                  |
      | id_subqmultiresponse3answer_4        | F/fluorine                                                                                                                                                                                                                                                  |
      | id_subqmultiresponse3answer_5        | Cl/chlorine                                                                                                                                                                                                                                                 |
      | id_subqmultiresponse3answer_6        | <b>Br/bromine</b>                                                                                                                                                                                                                                           |
      | id_subqmultiresponse3generalfeedback | Your choice of elements is not entirely correct.                                                                                                                                                                                                            |

      | id_subqselectmenu4defaultmark        | 20%           |
      | id_subqselectmenu4answer_0           | Wine          |
      | id_subqselectmenu4answer_1           | Vinagrette    |
      | id_subqselectmenu4answer_2           | Paint Thinner |
      | id_subqselectmenu4answer_3           | Mayonnaise    |
      | id_subqselectmenu4generalfeedback    |Your name for the mixture is incorrect. |

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

      | Hint 1                               | First hint                                 |
      | Hint 2                               | Second hint                                |
      | id_hintclearwrong_0                  | 1                                          |
      | id_hintclearwrong_1                  | 1                                          |

    And I set the following fields to these values:
      | Question name | Edited combined question |
    And I press "id_submitbutton"

    Then I should see "Edited combined question"
    And I am on the "Edited combined question" "core_question > edit" page logged in as teacher
    # The next line can only be un-commented once we no longer support 3.11.
    # And I should see "Version 2"
    And the following fields match these values:
      | Question name   | Edited combined question                                                                                                                                                                                                                                                                                                                                 |
      | Question text   | What is the pH of a 0.1M solution? [[1:numeric:__10__]]<br/>What is the IUPAC name of the molecule? [[2:pmatch:__20__]]<br/>Which elements are shown? [[3:multiresponse]]<br/>Which element is shown as white? [[6:singlechoice]]<br/>When a solution is combined with oil the result is a [[4:selectmenu:2]]<br/>Showworking [[5:showworking:__80x5__]] |

      | id_subqnumeric1defaultmark     | 20%                                     |
      | id_subqnumeric1answer_0        | 2.88                                    |
      | Scientific notation            | No                                      |
      | id_subqnumeric1generalfeedback | You have the incorrect value for the pH |

      | id_subqpmatch2defaultmark     | 20%                                |
      | Spell checking                | Do not check spelling of student   |
      | id_subqpmatch2answer_0        | match_mw (ethanoic acid)           |
      | id_subqpmatch2generalfeedback | You have the incorrect IUPAC name. |

      | id_subqmultiresponse3defaultmark     | 20%                                                                                                                                                                                                                                                         |
      | id_subqmultiresponse3answer_0        | C/carbon                                                                                                                                                                                                                                                    |
      | id_subqmultiresponse3correctanswer_0 | 1                                                                                                                                                                                                                                                           |
      | id_subqmultiresponse3answer_1        | H/hydrogen                                                                                                                                                                                                                                                  |
      | id_subqmultiresponse3correctanswer_1 | 1                                                                                                                                                                                                                                                           |
      | id_subqmultiresponse3answer_2        | O/oxygen                                                                                                                                                                                                                                                    |
      | id_subqmultiresponse3correctanswer_2 | 1                                                                                                                                                                                                                                                           |
      | id_subqmultiresponse3answer_3        | N/nitrogen                                                                                                                                                                                                                                                  |
      | id_subqmultiresponse3answer_4        | F/fluorine                                                                                                                                                                                                                                                  |
      | id_subqmultiresponse3answer_5        | Cl/chlorine                                                                                                                                                                                                                                                 |
      | id_subqmultiresponse3answer_6        | <b>Br/bromine</b>                                                                                                                                                                                                                                           |
      | id_subqmultiresponse3generalfeedback | Your choice of elements is not entirely correct.                                                                                                                                                                                                            |

      | id_subqselectmenu4defaultmark        | 20%           |
      | id_subqselectmenu4answer_0           | Wine          |
      | id_subqselectmenu4answer_1           | Vinagrette    |
      | id_subqselectmenu4answer_2           | Paint Thinner |
      | id_subqselectmenu4answer_3           | Mayonnaise    |
      | id_subqselectmenu4generalfeedback    |Your name for the mixture is incorrect. |

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

      | Hint 1                               | First hint                                 |
      | Hint 2                               | Second hint                                |
      | id_hintclearwrong_0                  | 1                                          |
      | id_hintclearwrong_1                  | 1                                          |

  @javascript
  Scenario: Test duplicating a combined question and editing subquestions before saving
    Given the following "questions" exist:
      | questioncategory | qtype    | template  | name               |
      | Test questions   | combined | numerical | Combined numerical |
    When I am on the "Course 1" "core_question > course question bank" page logged in as teacher
    And I choose "Duplicate" action for "Combined numerical" in the question bank

    And I set the field "Question name" to "The duplicated question"
    And I set the field "Question text" to "What 4.5 + 4.5? [[no1:numeric:__10__]]"
    And I expand all fieldsets
    And I set the following fields to these values:
      | id_subqnumericno1defaultmark | 100%                             |
      | id_subqnumericno1answer_0    | 9                                |
      | General feedback             | General feedback: 4.5 + 4.5 = 9. |
      | id_subqnumericno2defaultmark | 50%                              |
    And I press "id_submitbutton"
    And I should see "One or more embedded questions have been removed from the question text"
    And I press "id_submitbutton"

    # Verify that both questions now exist in the question bank.
    Then I should see "Combined numerical"
    And I should see "The duplicated question"

    # Verify the original question still works the way it always did.
    And I am on the "Combined numerical" "core_question > preview" page
    And I set the field "Answer no1" to "6.5"
    And I set the field "Answer no2" to "11"
    And I press "Submit and finish"
    And I should see "Well done!"

    # Check the modified question is as expected.
    And I am on the "The duplicated question" "core_question > preview" page
    And I set the field "Answer no1" to "9"
    And I press "Submit and finish"
    And I should see "Well done!"
