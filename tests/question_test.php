<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace qtype_combined;

use question_attempt_step;
use question_state;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/question/engine/tests/helpers.php');
require_once($CFG->dirroot . '/question/type/combined/tests/helper.php');


/**
 * Unit tests for qtype_combined_question.
 *
 * @package    qtype_combined
 * @copyright  2022 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \qtype_combined_question
 */
class question_test extends \advanced_testcase {
    public function test_get_expected_data() {
        if ($notfound = \qtype_combined_test_helper::safe_include_test_helpers('oumultiresponse', 'gapselect')) {
            $this->markTestSkipped($notfound);
        }

        $question = \qtype_combined_test_helper::make_a_combined_question_with_oumr_and_gapselect_subquestion();
        $question->start_attempt(new question_attempt_step(), 1);

        $this->assertEquals(
                ['mc:choice0' => PARAM_BOOL, 'mc:choice1' => PARAM_BOOL, 'mc:choice2' => PARAM_BOOL, 'mc:choice3' => PARAM_BOOL,
                        'gs:p1' => PARAM_INT, 'gs:p2' => PARAM_INT, 'gs:p3' => PARAM_INT],
                $question->get_expected_data());
    }

    public function test_is_complete_response() {
        if ($notfound = \qtype_combined_test_helper::safe_include_test_helpers('oumultiresponse', 'gapselect')) {
            $this->markTestSkipped($notfound);
        }

        $question = \qtype_combined_test_helper::make_a_combined_question_with_oumr_and_gapselect_subquestion();
        $question->start_attempt(new question_attempt_step(), 1);

        $this->assertFalse($question->is_complete_response([]));
        $this->assertTrue($question->is_complete_response(
                ['mc:choice0' => 1, 'mc:choice1' => 0, 'mc:choice2' => 0, 'mc:choice3' => 0,
                        'gs:p1' => 1, 'gs:p2' => 2, 'gs:p3' => 3]));
        $this->assertFalse($question->is_complete_response(['gs:p1' => 1, 'gs:p2' => 2, 'gs:p3' => 3]));
    }

    public function test_is_gradable_response() {
        if ($notfound = \qtype_combined_test_helper::safe_include_test_helpers('oumultiresponse', 'gapselect')) {
            $this->markTestSkipped($notfound);
        }

        $question = \qtype_combined_test_helper::make_a_combined_question_with_oumr_and_gapselect_subquestion();
        $question->start_attempt(new question_attempt_step(), 1);

        $this->assertFalse($question->is_gradable_response([]));
        $this->assertTrue($question->is_gradable_response(
                ['mc:choice0' => 1, 'mc:choice1' => 0, 'mc:choice2' => 0, 'mc:choice3' => 0,
                        'gs:p1' => 1, 'gs:p2' => 2, 'gs:p3' => 3]));
        $this->assertTrue($question->is_gradable_response(['gs:p1' => 1, 'gs:p2' => 2, 'gs:p3' => 3]));
        $this->assertTrue($question->is_gradable_response(['gs:p1' => 1]));
    }

    public function test_grading() {
        if ($notfound = \qtype_combined_test_helper::safe_include_test_helpers('oumultiresponse')) {
            $this->markTestSkipped($notfound);
        }

        $question = \qtype_combined_test_helper::make_a_combined_question_with_oumr_subquestion();
        $question->start_attempt(new question_attempt_step(), 1);

        $this->assertEquals([1, question_state::$gradedright], $question->grade_response(
                ['mc:choice0' => 1, 'mc:choice1' => 0, 'mc:choice2' => 1, 'mc:choice3' => 0]));

        $this->assertEquals([0, question_state::$gradedwrong], $question->grade_response(
                ['mc:choice0' => 0, 'mc:choice1' => 0, 'mc:choice2' => 0, 'mc:choice3' => 1]));
    }

    public function test_get_correct_response() {
        if ($notfound = \qtype_combined_test_helper::safe_include_test_helpers('oumultiresponse', 'gapselect')) {
            $this->markTestSkipped($notfound);
        }

        $question = \qtype_combined_test_helper::make_a_combined_question_with_oumr_and_gapselect_subquestion();
        $question->start_attempt(new question_attempt_step(), 1);

        $this->assertEquals(
                ['mc:choice0' => 1, 'mc:choice2' => 1,
                        'gs:p1' => 1, 'gs:p2' => 1, 'gs:p3' => 1],
                $question->get_correct_response());
    }

    public function test_get_question_summary() {
        if ($notfound = \qtype_combined_test_helper::safe_include_test_helpers('oumultiresponse', 'gapselect')) {
            $this->markTestSkipped($notfound);
        }

        $question = \qtype_combined_test_helper::make_a_combined_question_with_oumr_and_gapselect_subquestion();
        $question->start_attempt(new question_attempt_step(), 1);

        $this->assertEquals('Choose correct 2 check boxes [[mc:multiresponse]]. ' .
                'The [[gs:selectmenu:1]] brown [[gs:selectmenu:2]] jumped over the [[gs:selectmenu:3]] dog.',
                $question->get_question_summary());
    }

    public function test_summarise_response() {
        if ($notfound = \qtype_combined_test_helper::safe_include_test_helpers('oumultiresponse', 'gapselect')) {
            $this->markTestSkipped($notfound);
        }

        $question = \qtype_combined_test_helper::make_a_combined_question_with_oumr_and_gapselect_subquestion();
        $question->start_attempt(new question_attempt_step(), 1);

        $this->assertEquals(
                'mc [One; Three], gs [{quick} {fox} {lazy}]',
                $question->summarise_response(
                        ['mc:choice0' => 1, 'mc:choice2' => 1, 'gs:p1' => 1, 'gs:p2' => 1, 'gs:p3' => 1]));
    }

    public function test_classify_response() {
        if ($notfound = \qtype_combined_test_helper::safe_include_test_helpers('oumultiresponse')) {
            $this->markTestSkipped($notfound);
        }

        $question = \qtype_combined_test_helper::make_a_combined_question_with_oumr_and_showworking_subquestion();
        $question->start_attempt(new question_attempt_step(), 1);

        $this->assertEquals(
                [
                    'mc:multiresponse:13' => new \question_classified_response(13, 'One', 0.5),
                    'mc:multiresponse:15' => new \question_classified_response(15, 'Three', 0.5),
                ],
                $question->classify_response(
                        ['mc:choice0' => 1, 'mc:choice2' => 1, 'sw:answer' => 'A frog told me.']));
    }

    public function test_get_num_parts_right() {
        if ($notfound = \qtype_combined_test_helper::safe_include_test_helpers('oumultiresponse', 'gapselect')) {
            $this->markTestSkipped($notfound);
        }

        $question = \qtype_combined_test_helper::make_a_combined_question_with_oumr_and_gapselect_subquestion();
        $question->start_attempt(new question_attempt_step(), 1);

        [$numpartsright, $numparts] = $question->get_num_parts_right(
                ['mc:choice0' => 1, 'mc:choice1' => 0, 'mc:choice2' => 1, 'mc:choice3' => 0,
                        'gs:p1' => 1, 'gs:p2' => 1, 'gs:p3' => 1]);
        $this->assertEquals(5, $numpartsright);
        $this->assertEquals(7, $numparts); // Is this really right?

        [$numpartsright] = $question->get_num_parts_right(
                ['mc:choice0' => 1, 'mc:choice1' => 0, 'mc:choice2' => 1, 'mc:choice3' => 0]);
        $this->assertEquals(2, $numpartsright);

        [$numpartsright] = $question->get_num_parts_right(
                ['gs:p1' => 1, 'gs:p2' => 1, 'gs:p3' => 1]);
        $this->assertEquals(3, $numpartsright);

        [$numpartsright] = $question->get_num_parts_right(
                ['mc:choice0' => 0, 'mc:choice2' => 0, 'gs:p1' => 0, 'gs:p2' => 0, 'gs:p3' => 0]);
        $this->assertEquals(0, $numpartsright);
    }

    public function test_compute_final_grade() {
        if ($notfound = \qtype_combined_test_helper::safe_include_test_helpers('oumultiresponse', 'gapselect')) {
            $this->markTestSkipped($notfound);
        }

        $question = \qtype_combined_test_helper::make_a_combined_question_with_oumr_and_gapselect_subquestion();
        $question->start_attempt(new question_attempt_step(), 1);

        // Get subquestion 1 right at 2nd try and subquestion 2 right at 3rd try.
        $responses = [
                ['mc:choice0' => 1, 'mc:choice1' => 0, 'mc:choice2' => 0, 'mc:choice3' => 0,
                        'gs:p1' => 1, 'gs:p2' => 2, 'gs:p3' => 3],
                ['mc:choice0' => 1, 'mc:choice1' => 0, 'mc:choice2' => 1, 'mc:choice3' => 0,
                        'gs:p1' => 1, 'gs:p2' => 2, 'gs:p3' => 1],
                ['mc:choice0' => 1, 'mc:choice1' => 0, 'mc:choice2' => 1, 'mc:choice3' => 0,
                        'gs:p1' => 1, 'gs:p2' => 1, 'gs:p3' => 1],
        ];
        // Replace 0.00000005 with \question_testcase::GRADE_DELTA below, when possible.
        $this->assertEqualsWithDelta(0.5 * (3 / 3 + 2 / 3) / 2 + 0.5 * (3 / 3 + 1 / 3 + 2 / 3) / 3,
                $question->compute_final_grade($responses, 1), 0.00000005);
    }

    /**
     * Helper method to make a simulated second version of the standard _with_oumr_and_gapselect_subquestion test question.
     *
     * The key thing is that all the answer ids are changed (increased by 20).
     *
     * @param \qtype_combined_question $question
     * @return \qtype_combined_question
     */
    protected function make_second_version(\qtype_combined_question $question): \qtype_combined_question {

        $newquestion = fullclone($question);

        $subqs = $this->get_subqs_from_question($newquestion);
        $subqs[0]->question->answers = [
            33 => new \question_answer(33, 'One', 1, 'One is odd.', FORMAT_HTML),
            34 => new \question_answer(34, 'Two', 0, 'Two is even.', FORMAT_HTML),
            35 => new \question_answer(35, 'Three', 1, 'Three is odd.', FORMAT_HTML),
            36 => new \question_answer(36, 'Four', 0, 'Four is even.', FORMAT_HTML),
        ];

        return $newquestion;
    }

    /**
     * Helper to get the protected
     * @param \qtype_combined_question $question
     * @return \qtype_combined_combinable_base[]
     */
    protected function get_subqs_from_question(\qtype_combined_question $question): array {
        $subqsproperty = (new \ReflectionClass($question->combiner))->getProperty('subqs');
        $subqsproperty->setAccessible(true);
        return $subqsproperty->getValue($question->combiner);
    }

    public function test_validate_can_regrade_with_other_version_ok() {
        if (!method_exists('question_definition', 'validate_can_regrade_with_other_version')) {
            $this->markTestSkipped('This test is only relevant ot Moodle 4.0+.');
        }
        if ($notfound = \qtype_combined_test_helper::safe_include_test_helpers('oumultiresponse', 'gapselect')) {
            $this->markTestSkipped($notfound);
        }

        $question = \qtype_combined_test_helper::make_a_combined_question_with_oumr_and_gapselect_subquestion();

        $newquestion = $this->make_second_version($question);

        $this->assertNull($newquestion->validate_can_regrade_with_other_version($question));
    }

    public function test_validate_can_regrade_with_other_version_one_wrong_subquestion() {
        if (!method_exists('question_definition', 'validate_can_regrade_with_other_version')) {
            $this->markTestSkipped('This test is only relevant ot Moodle 4.0+.');
        }
        if ($notfound = \qtype_combined_test_helper::safe_include_test_helpers('oumultiresponse', 'gapselect')) {
            $this->markTestSkipped($notfound);
        }

        $question = \qtype_combined_test_helper::make_a_combined_question_with_oumr_and_gapselect_subquestion();

        $newquestion = $this->make_second_version($question);
        $subqs = $this->get_subqs_from_question($newquestion);
        unset($subqs[0]->question->answers[36]);

        $this->assertEquals(
                get_string('regradeissuenumchoiceschanged', 'qtype_multichoice'),
                $newquestion->validate_can_regrade_with_other_version($question));
    }

    public function test_update_attempt_state_date_from_old_version_ok() {
        if (!method_exists('question_definition', 'validate_can_regrade_with_other_version')) {
            $this->markTestSkipped('This test is only relevant ot Moodle 4.0+.');
        }
        if ($notfound = \qtype_combined_test_helper::safe_include_test_helpers('oumultiresponse', 'gapselect')) {
            $this->markTestSkipped($notfound);
        }

        $question = \qtype_combined_test_helper::make_a_combined_question_with_oumr_and_gapselect_subquestion();

        $newquestion = $this->make_second_version($question);

        $oldstep = new question_attempt_step();
        $oldstep->set_qt_var('_mc:order', '13,14,15,16');
        $oldstep->set_qt_var('_gs:choiceorder1', '1,2');

        $expected = [
            '_mc:order' => '33,34,35,36',
            '_gs:choiceorder1' => '1,2',
        ];

        $this->assertEquals($expected,
                $newquestion->update_attempt_state_data_for_new_version($oldstep, $question));
    }
}
