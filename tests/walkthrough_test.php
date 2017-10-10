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

/**
 * This file contains tests that walks a question through simulated student attempts.
 *
 * @package   qtype_combined
 * @copyright 2013 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();
global $CFG;

require_once($CFG->dirroot . '/question/engine/tests/helpers.php');
require_once($CFG->dirroot . '/question/type/combined/tests/helper.php');


/**
 * Unit tests for the combined question type.
 *
 * @copyright 2013 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @group qtype_combined
 */
class qtype_combined_walkthrough_test extends qbehaviour_walkthrough_test_base {
    public function test_interactive_behaviour_for_combined_question_with_gapselect_subquestion() {
        if ($notfound = qtype_combined_test_helper::safe_include_test_helpers('gapselect')) {
            $this->markTestSkipped($notfound);
        }
        // Create a combined question.
        $q = qtype_combined_test_helper::make_a_combined_question_with_gapselect_subquestion();

        $this->start_attempt_at_question($q, 'interactive', 3);

        // Check the initial state.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
                $this->get_contains_select_expectation('gs:p1',
                                array('' => get_string('choosedots'), '1' => 'quick', '2' => 'slow'), null, true),
                $this->get_contains_select_expectation('gs:p2',
                                array('' => get_string('choosedots'), '1' => 'fox', '2' => 'dog'), null, true),
                $this->get_contains_select_expectation('gs:p3',
                                array('' => get_string('choosedots'), '1' => 'lazy', '2' => 'assiduous'), null, true),
                $this->get_contains_submit_button_expectation(true),
                $this->get_does_not_contain_feedback_expectation(),
                $this->get_tries_remaining_expectation(3),
                $this->get_no_hint_visible_expectation());

        // Save the wrong answer.
        $this->process_submission(array('gs:p1' => '2', 'gs:p2' => '2', 'gs:p3' => '2'));

        // Verify.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
                $this->get_contains_select_expectation('gs:p1',
                                array('' => get_string('choosedots'), '1' => 'quick', '2' => 'slow'), 2, true),
                $this->get_contains_select_expectation('gs:p2',
                                array('' => get_string('choosedots'), '1' => 'fox', '2' => 'dog'), 2, true),
                $this->get_contains_select_expectation('gs:p3',
                                array('' => get_string('choosedots'), '1' => 'lazy', '2' => 'assiduous'), 2, true),
                $this->get_contains_submit_button_expectation(true),
                $this->get_does_not_contain_correctness_expectation(),
                $this->get_does_not_contain_feedback_expectation(),
                $this->get_tries_remaining_expectation(3),
                $this->get_no_hint_visible_expectation());

        // Submit the wrong answer.
        $this->process_submission(array('gs:p1' => '2', 'gs:p2' => '2', 'gs:p3' => '2', '-submit' => 1));

        // Verify.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
                $this->get_contains_select_expectation('gs:p1',
                                array('' => get_string('choosedots'), '1' => 'quick', '2' => 'slow'), 2, false),
                $this->get_contains_select_expectation('gs:p2',
                                array('' => get_string('choosedots'), '1' => 'fox', '2' => 'dog'), 2, false),
                $this->get_contains_select_expectation('gs:p3',
                                array('' => get_string('choosedots'), '1' => 'lazy', '2' => 'assiduous'), 2, false),
                $this->get_contains_try_again_button_expectation(true),
                $this->get_does_not_contain_correctness_expectation(),
                $this->get_contains_hint_expectation('This is the first hint'));

        // Do try again.
        $this->process_submission(array('-tryagain' => 1));

        // Verify.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
                $this->get_contains_select_expectation('gs:p1',
                                array('' => get_string('choosedots'), '1' => 'quick', '2' => 'slow'), 2, true),
                $this->get_contains_select_expectation('gs:p2',
                                array('' => get_string('choosedots'), '1' => 'fox', '2' => 'dog'), 2, true),
                $this->get_contains_select_expectation('gs:p3',
                                array('' => get_string('choosedots'), '1' => 'lazy', '2' => 'assiduous'), 2, true),
                $this->get_contains_submit_button_expectation(true),
                $this->get_does_not_contain_correctness_expectation(),
                $this->get_does_not_contain_feedback_expectation(),
                $this->get_tries_remaining_expectation(2),
                $this->get_no_hint_visible_expectation());

        // Submit the right answer.
        $this->process_submission(array('gs:p1' => '1', 'gs:p2' => '1', 'gs:p3' => '1', '-submit' => 1));

        // Verify.
        $this->check_current_state(question_state::$gradedright);
        $this->check_current_mark(2);
        $this->check_current_output(
                $this->get_contains_select_expectation('gs:p1',
                                array('' => get_string('choosedots'), '1' => 'quick', '2' => 'slow'), 1, false),
                $this->get_contains_select_expectation('gs:p2',
                                array('' => get_string('choosedots'), '1' => 'fox', '2' => 'dog'), 1, false),
                $this->get_contains_select_expectation('gs:p3',
                                array('' => get_string('choosedots'), '1' => 'lazy', '2' => 'assiduous'), 1, false),
                $this->get_contains_correct_expectation(),
                $this->get_no_hint_visible_expectation());

        // Check regrading does not mess anything up.
        $this->quba->regrade_all_questions();

        // Verify.
        $this->check_current_state(question_state::$gradedright);
        $this->check_current_mark(2);
    }

    /**
     * There used to be a bug if two parts of the same varnumeric subquestion
     * had the same right answer. This test checks for that.
     */
    public function test_interactive_behaviour_combined_gapselect_with_repeats() {
        if ($notfound = qtype_combined_test_helper::safe_include_test_helpers('gapselect')) {
            $this->markTestSkipped($notfound);
        }

        // Create a combined question.
        question_bank::load_question_definition_classes('combined');
        $combined = new qtype_combined_question();

        test_question_maker::initialise_a_question($combined);

        $combined->name = 'Selection from drop down list question';
        $combined->questiontext = 'Classify: Cat [[gs:selectmenu:1]], Dog [[gs:selectmenu:1]].';
        $combined->generalfeedback = '';
        $combined->qtype = question_bank::get_qtype('combined');

        test_question_maker::set_standard_combined_feedback_fields($combined);

        $combined->combiner = new qtype_combined_combiner_for_run_time_question_instance();
        $combined->combiner->find_included_subqs_in_question_text($combined->questiontext);

        $subq = $combined->combiner->find_or_create_question_instance('selectmenu', 'gs');

        $gapselect = new qtype_gapselect_question();
        test_question_maker::initialise_a_question($gapselect);
        $gapselect->qtype = question_bank::get_qtype('gapselect');
        $gapselect->name = 'gs';
        $gapselect->questiontext = '[[1]] [[1]]';
        $gapselect->generalfeedback = 'You made at least one incorrect choice.';
        $gapselect->shufflechoices = true;
        $gapselect->choices = array(
            1 => array(
                1 => new qtype_gapselect_choice('mammal', 1),
                2 => new qtype_gapselect_choice('insect', 1)
            ));
        $gapselect->places = array(1 => 1, 2 => 1);
        $gapselect->rightchoices = array(1 => 1, 2 => 1);
        $gapselect->textfragments = array('', ' ', ' ', '');

        $subq->question = $gapselect;

        $combined->hints = array(
                new question_hint_with_parts(1, 'This is the first hint.', FORMAT_HTML, false, false),
                new question_hint_with_parts(2, 'This is the second hint.', FORMAT_HTML, true, true),
        );

        // Start an attempt.
        $this->start_attempt_at_question($combined, 'interactive', 6);
        $orderedchoices = $combined->combiner->call_subq(0, 'get_ordered_choices', 1);
        $selectoptions = array('' => get_string('choosedots'));
        foreach ($orderedchoices as $orderedchoicevalue => $orderedchoice) {
            $selectoptions[$orderedchoicevalue] = $orderedchoice->text;
        }
        if ($selectoptions[1] == 'insect') {
            $this->assertEquals('mammal', $selectoptions[2]);
            $mammal = 2;
            $insect = 1;
        } else {
            $this->assertEquals('mammal', $selectoptions[1]);
            $this->assertEquals('insect', $selectoptions[2]);
            $mammal = 1;
            $insect = 2;
        }

        // Check the initial state.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
                $this->get_contains_select_expectation('gs:p1', $selectoptions, null, true),
                $this->get_contains_select_expectation('gs:p2', $selectoptions, null, true),
                $this->get_contains_submit_button_expectation(true),
                $this->get_does_not_contain_feedback_expectation(),
                $this->get_tries_remaining_expectation(3),
                $this->get_no_hint_visible_expectation());

        // Check a partially right answer.
        $this->process_submission(array('gs:p1' => $mammal, 'gs:p2' => $insect, '-submit' => 1));

        // Verify.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
                $this->get_contains_select_expectation('gs:p1', $selectoptions, $mammal, true),
                $this->get_contains_select_expectation('gs:p2', $selectoptions, $insect, true),
                $this->get_contains_try_again_button_expectation(true),
                $this->get_does_not_contain_correctness_expectation(),
                $this->get_contains_hint_expectation('This is the first hint'));

        // Do try again.
        $this->process_submission(array('-tryagain' => 1));

        // Verify.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
                $this->get_contains_select_expectation('gs:p1', $selectoptions, $mammal, true),
                $this->get_contains_select_expectation('gs:p2', $selectoptions, $insect, true),
                $this->get_contains_submit_button_expectation(true),
                $this->get_does_not_contain_correctness_expectation(),
                $this->get_does_not_contain_feedback_expectation(),
                $this->get_tries_remaining_expectation(2),
                $this->get_no_hint_visible_expectation());

        // Submit the right answer.
        $this->process_submission(array('gs:p1' => $mammal, 'gs:p2' => $mammal, '-submit' => 1));

        // Verify.
        $this->check_current_state(question_state::$gradedright);
        $this->check_current_mark(5);
        $this->check_current_output(
                $this->get_contains_select_expectation('gs:p1', $selectoptions, $mammal, false),
                $this->get_contains_select_expectation('gs:p2', $selectoptions, $mammal, false),
                $this->get_contains_correct_expectation(),
                $this->get_no_hint_visible_expectation());
    }

    /**
     * There used to be a bug if the student typed input into one box which
     * matches a placeholder for a subquestion later in the question text.
     * This test checks for that.
     */
    public function test_interactive_behaviour_combined_hack_attempt() {
        if ($notfound = qtype_combined_test_helper::safe_include_test_helpers('pmatch')) {
            $this->markTestSkipped($notfound);
        }

        // Create a combined question.
        question_bank::load_question_definition_classes('combined');
        $combined = new qtype_combined_question();

        test_question_maker::initialise_a_question($combined);

        $combined->name = 'Selection from drop down list question';
        $combined->questiontext = 'Type &#x5b;&#x5b;2:pmatch&#x5d;&#x5d; in the first box, ' .
                'if you dare! [[1:pmatch]][[2:pmatch]].';
        $combined->generalfeedback = '';
        $combined->qtype = question_bank::get_qtype('combined');
        $combined->hints = array(
                new question_hint_with_parts(1, 'This is the first hint.', FORMAT_HTML, false, false),
                new question_hint_with_parts(2, 'This is the second hint.', FORMAT_HTML, true, true),
        );
        test_question_maker::set_standard_combined_feedback_fields($combined);

        $combined->combiner = new qtype_combined_combiner_for_run_time_question_instance();
        $combined->combiner->find_included_subqs_in_question_text($combined->questiontext);

        // First pmatch subquestion.
        question_bank::load_question_definition_classes('pmatch');
        $subq = $combined->combiner->find_or_create_question_instance('pmatch', '1');

        $pmatch = new qtype_pmatch_question();
        test_question_maker::initialise_a_question($pmatch);
        $pmatch->qtype = question_bank::get_qtype('pmatch');
        $pmatch->name = '1';
        $pmatch->questiontext = '';
        $pmatch->generalfeedback = '';
        $pmatch->pmatchoptions = new pmatch_options();
        $pmatch->answers = array(
            13 => new question_answer(13, 'match(frog)', 1.0, '', FORMAT_HTML),
        );
        $pmatch->applydictionarycheck = false;
        $subq->question = $pmatch;

        // Second pmatch subquestion.
        $subq = $combined->combiner->find_or_create_question_instance('pmatch', '2');

        $pmatch = new qtype_pmatch_question();
        test_question_maker::initialise_a_question($pmatch);
        $pmatch->qtype = question_bank::get_qtype('pmatch');
        $pmatch->name = '2';
        $pmatch->questiontext = '';
        $pmatch->generalfeedback = '';
        $pmatch->pmatchoptions = new pmatch_options();
        $pmatch->answers = array(
                14 => new question_answer(13, 'match(toad)', 1.0, '', FORMAT_HTML),
        );
        $pmatch->applydictionarycheck = false;
        $subq->question = $pmatch;

        // Start an attempt.
        $this->start_attempt_at_question($combined, 'interactive', 6);

        // Check the initial state.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
                $this->get_contains_text_expectation('1:answer', '', true),
                $this->get_contains_text_expectation('2:answer', '', true),
                $this->get_contains_submit_button_expectation(true),
                $this->get_does_not_contain_feedback_expectation(),
                $this->get_tries_remaining_expectation(3),
                $this->get_no_hint_visible_expectation());

        // Submit a malicious response.
        $this->process_submission(array('1:answer' => '[[2:pmatch]]', '2:answer' => 'Ha! Ha!', '-submit' => 1));

        // Verify.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
                $this->get_contains_text_expectation('1:answer', '[[2:pmatch]]', false),
                $this->get_contains_text_expectation('2:answer', 'Ha! Ha!', false),
                $this->get_contains_try_again_button_expectation(true),
                $this->get_does_not_contain_correctness_expectation(),
                $this->get_contains_hint_expectation('This is the first hint'));
    }


    /**
     * There used to be a bug if the student typed input into one box which
     * matches a placeholder for a subquestion later in the question text.
     * This test checks for that.
     */
    public function test_interactive_behaviour_combined_interleaved_subqs() {
        if ($notfound = qtype_combined_test_helper::safe_include_test_helpers('pmatch', 'gapselect')) {
            $this->markTestSkipped($notfound);
        }

        // Create a combined question.
        question_bank::load_question_definition_classes('combined');
        $combined = new qtype_combined_question();

        test_question_maker::initialise_a_question($combined);

        $combined->name = 'Selection from drop down list question';
        $combined->questiontext = 'Tricky: [[1:selectmenu:1]][[2:pmatch]][[1:selectmenu:1]].';
        $combined->generalfeedback = '';
        $combined->qtype = question_bank::get_qtype('combined');
        $combined->hints = array(
                new question_hint_with_parts(1, 'This is the first hint.', FORMAT_HTML, false, false),
                new question_hint_with_parts(2, 'This is the second hint.', FORMAT_HTML, true, true),
        );
        test_question_maker::set_standard_combined_feedback_fields($combined);

        $combined->combiner = new qtype_combined_combiner_for_run_time_question_instance();
        $combined->combiner->find_included_subqs_in_question_text($combined->questiontext);

        $subq = $combined->combiner->find_or_create_question_instance('selectmenu', '1');

        // Gap-select subquestion.
        question_bank::load_question_definition_classes('gapselect');
        $gapselect = new qtype_gapselect_question();
        test_question_maker::initialise_a_question($gapselect);
        $gapselect->qtype = question_bank::get_qtype('gapselect');
        $gapselect->defaultmark = 0.5;
        $gapselect->name = 'gs';
        $gapselect->questiontext = '[[1]] [[1]]';
        $gapselect->generalfeedback = 'You made at least one incorrect choice.';
        $gapselect->shufflechoices = true;
        $gapselect->choices = array(
                1 => array(
                        1 => new qtype_gapselect_choice('mammal', 1),
                        2 => new qtype_gapselect_choice('insect', 1)
                ));
        $gapselect->places = array(1 => 1, 2 => 1);
        $gapselect->rightchoices = array(1 => 1, 2 => 1);
        $gapselect->textfragments = array('', ' ', '');

        $subq->question = $gapselect;

        // Pmatch subquestion.
        question_bank::load_question_definition_classes('pmatch');
        $subq = $combined->combiner->find_or_create_question_instance('pmatch', '2');

        $pmatch = new qtype_pmatch_question();
        test_question_maker::initialise_a_question($pmatch);
        $pmatch->qtype = question_bank::get_qtype('pmatch');
        $pmatch->defaultmark = 0.5;
        $pmatch->name = '1';
        $pmatch->questiontext = '';
        $pmatch->generalfeedback = '';
        $pmatch->pmatchoptions = new pmatch_options();
        $pmatch->answers = array(
                13 => new question_answer(13, 'match(frog)', 1.0, '', FORMAT_HTML),
        );
        $pmatch->applydictionarycheck = false;
        $subq->question = $pmatch;

        // Start an attempt.
        $this->start_attempt_at_question($combined, 'interactive', 9);

        // Work out gapselect choice order.
        $orderedchoices = $combined->combiner->call_subq(0, 'get_ordered_choices', 1);
        $selectoptions = array('' => get_string('choosedots'));
        foreach ($orderedchoices as $orderedchoicevalue => $orderedchoice) {
            $selectoptions[$orderedchoicevalue] = $orderedchoice->text;
        }
        if ($selectoptions[1] == 'insect') {
            $this->assertEquals('mammal', $selectoptions[2]);
            $mammal = 2;
            $insect = 1;
        } else {
            $this->assertEquals('mammal', $selectoptions[1]);
            $this->assertEquals('insect', $selectoptions[2]);
            $mammal = 1;
            $insect = 2;
        }

        // Check the initial state.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
                $this->get_contains_select_expectation('1:p1', $selectoptions, null, true),
                $this->get_contains_text_expectation('2:answer', '', true),
                $this->get_contains_select_expectation('1:p2', $selectoptions, null, true),
                $this->get_contains_submit_button_expectation(true),
                $this->get_does_not_contain_feedback_expectation(),
                $this->get_tries_remaining_expectation(3),
                $this->get_no_hint_visible_expectation());

        // Submit a correct response.
        $this->process_submission(array('1:p1' => $mammal, '2:answer' => 'frog', '1:p2' => $mammal, '-submit' => 1));

        // Verify.
        $this->check_current_state(question_state::$gradedright);
        $this->check_current_mark(9);
        $this->check_current_output(
                $this->get_contains_select_expectation('1:p1', $selectoptions, $mammal, false),
                $this->get_contains_text_expectation('2:answer', 'frog', false),
                $this->get_contains_select_expectation('1:p2', $selectoptions, $mammal, false),
                $this->get_contains_correct_expectation(),
                $this->get_no_hint_visible_expectation());
    }

    protected function get_contains_num_parts_correct($num) {
        $a = new stdClass();
        $a->num = $num;
        if ($a->num == 1) {
            return new question_pattern_expectation('/<div class="numpartscorrect">' .
                                                        preg_quote(get_string('yougot1right', 'qtype_combined'), '/') . '/');
        } else {
            return new question_pattern_expectation('/<div class="numpartscorrect">' .
                                                        preg_quote(get_string('yougotnright', 'qtype_combined', $a), '/') . '/');
        }
    }

    public function test_interactive_behaviour_for_combined_question_with_ou_mr_subq() {
        if ($notfound = qtype_combined_test_helper::safe_include_test_helpers('oumultiresponse')) {
            $this->markTestSkipped($notfound);
        }
        // Create a combined question.
        $combined = qtype_combined_test_helper::make_a_combined_question_with_oumr_subquestion();

        $this->start_attempt_at_question($combined, 'interactive', 3);

        // Check the initial state.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
            $this->get_contains_mc_checkbox_expectation('mc:choice0', true, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice1', true, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice2', true, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice3', true, false),
            $this->get_contains_submit_button_expectation(true),
            $this->get_does_not_contain_feedback_expectation(),
            $this->get_does_not_contain_num_parts_correct(),
            $this->get_tries_remaining_expectation(3),
            $this->get_no_hint_visible_expectation());

        // Save the wrong answer.
        $this->process_submission(array('mc:choice1' => '1', 'mc:choice3' => '1'));

        // Verify.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
            $this->get_contains_mc_checkbox_expectation('mc:choice0', true, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice1', true, true),
            $this->get_contains_mc_checkbox_expectation('mc:choice2', true, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice3', true, true),
            $this->get_contains_submit_button_expectation(true),
            $this->get_does_not_contain_feedback_expectation(),
            $this->get_does_not_contain_num_parts_correct(),
            $this->get_tries_remaining_expectation(3),
            $this->get_no_hint_visible_expectation());

        // Submit the wrong answer.
        $this->process_submission(array('mc:choice1' => '1', 'mc:choice3' => '1', '-submit' => '1'));

        // Verify.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
            $this->get_contains_mc_checkbox_expectation('mc:choice0', false, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice1', false, true),
            $this->get_contains_mc_checkbox_expectation('mc:choice2', false, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice3', false, true),
            $this->get_contains_try_again_button_expectation(true),
            $this->get_does_not_contain_correctness_expectation(),
            $this->get_contains_hint_expectation('Hint 1'),
            $this->get_contains_num_parts_correct(0),
            $this->get_contains_standard_incorrect_combined_feedback_expectation(),
            $this->get_does_not_contain_hidden_expectation(
                $this->quba->get_field_prefix($this->slot) . 'mc:choice0'),
            $this->get_does_not_contain_hidden_expectation(
                $this->quba->get_field_prefix($this->slot) . 'mc:choice1'),
            $this->get_does_not_contain_hidden_expectation(
                $this->quba->get_field_prefix($this->slot) . 'mc:choice2'),
            $this->get_does_not_contain_hidden_expectation(
                $this->quba->get_field_prefix($this->slot) . 'mc:choice3'));

        // Do try again.
        $this->process_submission(array('-tryagain' => 1));

        // Verify.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
            $this->get_contains_mc_checkbox_expectation('mc:choice0', true, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice1', true, true),
            $this->get_contains_mc_checkbox_expectation('mc:choice2', true, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice3', true, true),
            $this->get_contains_submit_button_expectation(true),
            $this->get_does_not_contain_feedback_expectation(),
            $this->get_does_not_contain_num_parts_correct(),
            $this->get_tries_remaining_expectation(2),
            $this->get_no_hint_visible_expectation());

        // Submit a partially right answer.
        $this->process_submission(array('mc:choice0' => '1', 'mc:choice3' => '1', '-submit' => '1'));

        // Verify.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
            $this->get_contains_mc_checkbox_expectation('mc:choice0', false, true),
            $this->get_contains_mc_checkbox_expectation('mc:choice1', false, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice2', false, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice3', false, true),
            $this->get_contains_try_again_button_expectation(true),
            $this->get_does_not_contain_correctness_expectation(),
            $this->get_contains_hint_expectation('Hint 2'),
            $this->get_contains_num_parts_correct(1),
            $this->get_contains_standard_partiallycorrect_combined_feedback_expectation(),
            $this->get_does_not_contain_hidden_expectation($this->quba->get_field_prefix($this->slot) . 'mc:choice0'),
            $this->get_does_not_contain_hidden_expectation($this->quba->get_field_prefix($this->slot) . 'mc:choice1'),
            $this->get_does_not_contain_hidden_expectation($this->quba->get_field_prefix($this->slot) . 'mc:choice2'),
            $this->get_does_not_contain_hidden_expectation($this->quba->get_field_prefix($this->slot) . 'mc:choice3'));

        // Do try again.
        $this->process_submission(array('mc:choice0' => '1', '-tryagain' => 1));

        // Verify.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
            $this->get_contains_mc_checkbox_expectation('mc:choice0', true, true),
            $this->get_contains_mc_checkbox_expectation('mc:choice1', true, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice2', true, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice3', true, false),
            $this->get_contains_submit_button_expectation(true),
            $this->get_does_not_contain_feedback_expectation(),
            $this->get_does_not_contain_num_parts_correct(),
            $this->get_tries_remaining_expectation(1),
            $this->get_no_hint_visible_expectation());

        // Submit the right answer.
        $this->process_submission(array('mc:choice0' => '1', 'mc:choice2' => '1', '-submit' => '1'));

        // Verify.
        $this->check_current_state(question_state::$gradedright);
        $this->check_current_mark(1.5);
        $this->check_current_output(
            $this->get_contains_mc_checkbox_expectation('mc:choice0', false, true),
            $this->get_contains_mc_checkbox_expectation('mc:choice1', false, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice2', false, true),
            $this->get_contains_mc_checkbox_expectation('mc:choice3', false, false),
            $this->get_does_not_contain_try_again_button_expectation(),
            $this->get_contains_correct_expectation(),
            $this->get_contains_standard_correct_combined_feedback_expectation());
    }
    public function test_interactive_behaviour_for_combined_question_with_ou_mr_and_gapselect_subq() {
        if ($notfound = qtype_combined_test_helper::safe_include_test_helpers('oumultiresponse', 'gapselect')) {
            $this->markTestSkipped($notfound);
        }
        // Create a combined question.
        $combined = qtype_combined_test_helper::make_a_combined_question_with_oumr_and_gapselect_subquestion();

        $this->start_attempt_at_question($combined, 'interactive', 6);

        // Check the initial state.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
            $this->get_contains_mc_checkbox_expectation('mc:choice0', true, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice1', true, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice2', true, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice3', true, false),
            $this->get_contains_select_expectation('gs:p1',
                                   array('' => get_string('choosedots'), '1' => 'quick', '2' => 'slow'), null, true),
            $this->get_contains_select_expectation('gs:p2',
                                   array('' => get_string('choosedots'), '1' => 'fox', '2' => 'dog'), null, true),
            $this->get_contains_select_expectation('gs:p3',
                                   array('' => get_string('choosedots'), '1' => 'lazy', '2' => 'assiduous'), null, true),
            $this->get_contains_submit_button_expectation(true),
            $this->get_does_not_contain_feedback_expectation(),
            $this->get_does_not_contain_num_parts_correct(),
            $this->get_tries_remaining_expectation(4),
            $this->get_no_hint_visible_expectation());

        // Save the wrong answer.
        $this->process_submission(array('mc:choice1' => '1', 'mc:choice3' => '1', 'gs:p1' => '2', 'gs:p2' => '2', 'gs:p3' => '2'));

        // Verify.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
            $this->get_contains_mc_checkbox_expectation('mc:choice0', true, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice1', true, true),
            $this->get_contains_mc_checkbox_expectation('mc:choice2', true, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice3', true, true),
            $this->get_contains_select_expectation('gs:p1',
                                   array('' => get_string('choosedots'), '1' => 'quick', '2' => 'slow'), 2, true),
            $this->get_contains_select_expectation('gs:p2',
                                   array('' => get_string('choosedots'), '1' => 'fox', '2' => 'dog'), 2, true),
            $this->get_contains_select_expectation('gs:p3',
                                   array('' => get_string('choosedots'), '1' => 'lazy', '2' => 'assiduous'), 2, true),
            $this->get_contains_submit_button_expectation(true),
            $this->get_does_not_contain_feedback_expectation(),
            $this->get_does_not_contain_num_parts_correct(),
            $this->get_tries_remaining_expectation(4),
            $this->get_no_hint_visible_expectation());

        // Submit the wrong answer.
        $this->process_submission(array('mc:choice1' => '1', 'mc:choice3' => '1',
                                        'gs:p1' => '2', 'gs:p2' => '2', 'gs:p3' => '2',
                                        '-submit' => '1'));

        // Verify.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
            $this->get_contains_mc_checkbox_expectation('mc:choice0', false, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice1', false, true),
            $this->get_contains_mc_checkbox_expectation('mc:choice2', false, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice3', false, true),
            $this->get_contains_select_expectation('gs:p1',
                                   array('' => get_string('choosedots'), '1' => 'quick', '2' => 'slow'), 2, false),
            $this->get_contains_select_expectation('gs:p2',
                                   array('' => get_string('choosedots'), '1' => 'fox', '2' => 'dog'), 2, false),
            $this->get_contains_select_expectation('gs:p3',
                                   array('' => get_string('choosedots'), '1' => 'lazy', '2' => 'assiduous'), 2, false),
            $this->get_contains_try_again_button_expectation(true),
            $this->get_does_not_contain_correctness_expectation(),
            $this->get_contains_hint_expectation('Hint 1'),
            $this->get_contains_num_parts_correct(0),
            $this->get_contains_standard_incorrect_combined_feedback_expectation(),
            $this->get_does_not_contain_hidden_expectation(
                $this->quba->get_field_prefix($this->slot) . 'mc:choice0'),
            $this->get_does_not_contain_hidden_expectation(
                $this->quba->get_field_prefix($this->slot) . 'mc:choice1'),
            $this->get_does_not_contain_hidden_expectation(
                $this->quba->get_field_prefix($this->slot) . 'mc:choice2'),
            $this->get_does_not_contain_hidden_expectation(
                $this->quba->get_field_prefix($this->slot) . 'mc:choice3'));

        // Do try again.
        $this->process_submission(array('-tryagain' => 1));

        // Verify.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
            $this->get_contains_mc_checkbox_expectation('mc:choice0', true, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice1', true, true),
            $this->get_contains_mc_checkbox_expectation('mc:choice2', true, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice3', true, true),
            $this->get_contains_select_expectation('gs:p1',
                                   array('' => get_string('choosedots'), '1' => 'quick', '2' => 'slow'), 2, true),
            $this->get_contains_select_expectation('gs:p2',
                                   array('' => get_string('choosedots'), '1' => 'fox', '2' => 'dog'), 2, true),
            $this->get_contains_select_expectation('gs:p3',
                                   array('' => get_string('choosedots'), '1' => 'lazy', '2' => 'assiduous'), 2, true),
            $this->get_contains_submit_button_expectation(true),
            $this->get_does_not_contain_feedback_expectation(),
            $this->get_does_not_contain_num_parts_correct(),
            $this->get_tries_remaining_expectation(3),
            $this->get_no_hint_visible_expectation());

        // Submit a partially right answer.
        $this->process_submission(array('mc:choice0' => '1', 'mc:choice3' => '1',
                                        'gs:p1' => '2', 'gs:p2' => '2', 'gs:p3' => '2',
                                        '-submit' => '1'));

        // Verify.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
            $this->get_contains_mc_checkbox_expectation('mc:choice0', false, true),
            $this->get_contains_mc_checkbox_expectation('mc:choice1', false, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice2', false, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice3', false, true),
            $this->get_contains_select_expectation('gs:p1',
                                   array('' => get_string('choosedots'), '1' => 'quick', '2' => 'slow'), 2, false),
            $this->get_contains_select_expectation('gs:p2',
                                   array('' => get_string('choosedots'), '1' => 'fox', '2' => 'dog'), 2, false),
            $this->get_contains_select_expectation('gs:p3',
                                   array('' => get_string('choosedots'), '1' => 'lazy', '2' => 'assiduous'), 2, false),
            $this->get_contains_try_again_button_expectation(true),
            $this->get_does_not_contain_correctness_expectation(),
            $this->get_contains_hint_expectation('Hint 2'),
            $this->get_contains_num_parts_correct(1),
            $this->get_contains_standard_partiallycorrect_combined_feedback_expectation(),
            $this->get_does_not_contain_hidden_expectation($this->quba->get_field_prefix($this->slot) . 'mc:choice0'),
            $this->get_does_not_contain_hidden_expectation($this->quba->get_field_prefix($this->slot) . 'mc:choice1'),
            $this->get_does_not_contain_hidden_expectation($this->quba->get_field_prefix($this->slot) . 'mc:choice2'),
            $this->get_does_not_contain_hidden_expectation($this->quba->get_field_prefix($this->slot) . 'mc:choice3'));

        // Do try again.
        $this->process_submission(array('-tryagain' => 1));

        // Verify.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
            $this->get_contains_mc_checkbox_expectation('mc:choice0', true, true),
            $this->get_contains_mc_checkbox_expectation('mc:choice1', true, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice2', true, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice3', true, true),
            $this->get_contains_select_expectation('gs:p1',
                                   array('' => get_string('choosedots'), '1' => 'quick', '2' => 'slow'), 2, true),
            $this->get_contains_select_expectation('gs:p2',
                                   array('' => get_string('choosedots'), '1' => 'fox', '2' => 'dog'), 2, true),
            $this->get_contains_select_expectation('gs:p3',
                                   array('' => get_string('choosedots'), '1' => 'lazy', '2' => 'assiduous'), 2, true),
            $this->get_contains_submit_button_expectation(true),
            $this->get_does_not_contain_feedback_expectation(),
            $this->get_does_not_contain_num_parts_correct(),
            $this->get_tries_remaining_expectation(2),
            $this->get_no_hint_visible_expectation());

        // Submit the right answer.
        $this->process_submission(array('mc:choice0' => '1', 'mc:choice2' => '1',
                                        'gs:p1' => '1', 'gs:p2' => '1', 'gs:p3' => '1',
                                        '-submit' => '1'));

        // Verify.
        $this->check_current_state(question_state::$gradedright);
        $this->check_current_mark(2.5);
        $this->check_current_output(
            $this->get_contains_mc_checkbox_expectation('mc:choice0', false, true),
            $this->get_contains_mc_checkbox_expectation('mc:choice1', false, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice2', false, true),
            $this->get_contains_mc_checkbox_expectation('mc:choice3', false, false),
            $this->get_contains_select_expectation('gs:p1',
                                   array('' => get_string('choosedots'), '1' => 'quick', '2' => 'slow'), 1, false),
            $this->get_contains_select_expectation('gs:p2',
                                   array('' => get_string('choosedots'), '1' => 'fox', '2' => 'dog'), 1, false),
            $this->get_contains_select_expectation('gs:p3',
                                   array('' => get_string('choosedots'), '1' => 'lazy', '2' => 'assiduous'), 1, false),
            $this->get_does_not_contain_try_again_button_expectation(),
            $this->get_contains_correct_expectation(),
            $this->get_contains_standard_correct_combined_feedback_expectation());

    }
    public function test_deferred_feedback_behaviour_for_combined_question_with_ou_mr_and_gapselect_subq_wrong() {
        if ($notfound = qtype_combined_test_helper::safe_include_test_helpers('oumultiresponse', 'gapselect')) {
            $this->markTestSkipped($notfound);
        }
        // Create a combined question.
        $combined = qtype_combined_test_helper::make_a_combined_question_with_oumr_and_gapselect_subquestion();

        $this->start_attempt_at_question($combined, 'deferredfeedback', 6);

        // Check the initial state.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
            $this->get_contains_mc_checkbox_expectation('mc:choice0', true, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice1', true, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice2', true, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice3', true, false),
            $this->get_contains_select_expectation('gs:p1',
                                           array('' => get_string('choosedots'), '1' => 'quick', '2' => 'slow'), null, true),
            $this->get_contains_select_expectation('gs:p2',
                                           array('' => get_string('choosedots'), '1' => 'fox', '2' => 'dog'), null, true),
            $this->get_contains_select_expectation('gs:p3',
                                           array('' => get_string('choosedots'), '1' => 'lazy', '2' => 'assiduous'), null, true),
            $this->get_does_not_contain_feedback_expectation(),
            $this->get_does_not_contain_num_parts_correct(),
            $this->get_no_hint_visible_expectation());

        // Save the wrong answer.
        $this->process_submission(array('mc:choice1' => '1', 'mc:choice3' => '1', 'gs:p1' => '2', 'gs:p2' => '2', 'gs:p3' => '2'));

        // Verify.
        $this->check_current_state(question_state::$complete);
        $this->check_current_mark(null);
        $this->check_current_output(
            $this->get_contains_mc_checkbox_expectation('mc:choice0', true, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice1', true, true),
            $this->get_contains_mc_checkbox_expectation('mc:choice2', true, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice3', true, true),
            $this->get_contains_select_expectation('gs:p1',
                                           array('' => get_string('choosedots'), '1' => 'quick', '2' => 'slow'), 2, true),
            $this->get_contains_select_expectation('gs:p2',
                                           array('' => get_string('choosedots'), '1' => 'fox', '2' => 'dog'), 2, true),
            $this->get_contains_select_expectation('gs:p3',
                                           array('' => get_string('choosedots'), '1' => 'lazy', '2' => 'assiduous'), 2, true),
            $this->get_does_not_contain_try_again_button_expectation(),
            $this->get_does_not_contain_feedback_expectation(),
            $this->get_does_not_contain_num_parts_correct(),
            $this->get_no_hint_visible_expectation());

        // Submit.
        $this->process_submission(array('-finish' => '1'));

        // Verify.
        $this->check_current_state(question_state::$gradedwrong);
        $this->check_current_mark(0);
        $this->check_current_output(
            $this->get_contains_mc_checkbox_expectation('mc:choice0', false, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice1', false, true),
            $this->get_contains_mc_checkbox_expectation('mc:choice2', false, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice3', false, true),
            $this->get_contains_select_expectation('gs:p1',
                                           array('' => get_string('choosedots'), '1' => 'quick', '2' => 'slow'), 2, false),
            $this->get_contains_select_expectation('gs:p2',
                                           array('' => get_string('choosedots'), '1' => 'fox', '2' => 'dog'), 2, false),
            $this->get_contains_select_expectation('gs:p3',
                                           array('' => get_string('choosedots'), '1' => 'lazy', '2' => 'assiduous'), 2, false),
            $this->get_does_not_contain_try_again_button_expectation(),
            $this->get_does_not_contain_correctness_expectation(),
            $this->get_contains_standard_incorrect_combined_feedback_expectation(),
            $this->get_does_not_contain_hidden_expectation(
                $this->quba->get_field_prefix($this->slot) . 'mc:choice0'),
            $this->get_does_not_contain_hidden_expectation(
                $this->quba->get_field_prefix($this->slot) . 'mc:choice1'),
            $this->get_does_not_contain_hidden_expectation(
                $this->quba->get_field_prefix($this->slot) . 'mc:choice2'),
            $this->get_does_not_contain_hidden_expectation(
                $this->quba->get_field_prefix($this->slot) . 'mc:choice3'));

    }

    public function test_deferred_feedback_behaviour_for_combined_question_with_ou_mr_and_gapselect_subq_partially_correct() {
        if ($notfound = qtype_combined_test_helper::safe_include_test_helpers('oumultiresponse', 'gapselect')) {
            $this->markTestSkipped($notfound);
        }
        // Create a combined question.
        $combined = qtype_combined_test_helper::make_a_combined_question_with_oumr_and_gapselect_subquestion();

        $this->start_attempt_at_question($combined, 'deferredfeedback', 6);

        // Check the initial state.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
            $this->get_contains_mc_checkbox_expectation('mc:choice0', true, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice1', true, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice2', true, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice3', true, false),
            $this->get_contains_select_expectation('gs:p1',
                                           array('' => get_string('choosedots'), '1' => 'quick', '2' => 'slow'), null, true),
            $this->get_contains_select_expectation('gs:p2',
                                           array('' => get_string('choosedots'), '1' => 'fox', '2' => 'dog'), null, true),
            $this->get_contains_select_expectation('gs:p3',
                                           array('' => get_string('choosedots'), '1' => 'lazy', '2' => 'assiduous'), null, true),
            $this->get_does_not_contain_feedback_expectation(),
            $this->get_does_not_contain_num_parts_correct(),
            $this->get_no_hint_visible_expectation());

        // Submit partially correct, partially complete answer.
        // 'mc' is correct but no response submitted for 'gs'.
        $this->process_submission(array('mc:choice0' => '1', 'mc:choice2' => '1'));

        // Verify.
        $this->check_current_state(question_state::$invalid);
        $this->check_current_mark(null);
        $this->check_current_output(
            $this->get_contains_mc_checkbox_expectation('mc:choice0', true, true),
            $this->get_contains_mc_checkbox_expectation('mc:choice1', true, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice2', true, true),
            $this->get_contains_mc_checkbox_expectation('mc:choice3', true, false),
            $this->get_contains_select_expectation('gs:p1',
                                           array('' => get_string('choosedots'), '1' => 'quick', '2' => 'slow'), null, true),
            $this->get_contains_select_expectation('gs:p2',
                                           array('' => get_string('choosedots'), '1' => 'fox', '2' => 'dog'), null, true),
            $this->get_contains_select_expectation('gs:p3',
                                           array('' => get_string('choosedots'), '1' => 'lazy', '2' => 'assiduous'), null, true),
            $this->get_does_not_contain_try_again_button_expectation(),
            $this->get_does_not_contain_feedback_expectation(),
            $this->get_does_not_contain_num_parts_correct(),
            $this->get_no_hint_visible_expectation());

        // Submit.
        $this->process_submission(array('-finish' => '1'));

        // Verify.
        $this->check_current_state(question_state::$gradedpartial);
        $this->check_current_mark(3);
        $this->check_current_output(
            $this->get_contains_mc_checkbox_expectation('mc:choice0', false, true),
            $this->get_contains_mc_checkbox_expectation('mc:choice1', false, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice2', false, true),
            $this->get_contains_mc_checkbox_expectation('mc:choice3', false, false),
            $this->get_contains_select_expectation('gs:p1',
                                       array('' => get_string('choosedots'), '1' => 'quick', '2' => 'slow'), null, false),
            $this->get_contains_select_expectation('gs:p2',
                                       array('' => get_string('choosedots'), '1' => 'fox', '2' => 'dog'), null, false),
            $this->get_contains_select_expectation('gs:p3',
                                       array('' => get_string('choosedots'), '1' => 'lazy', '2' => 'assiduous'), null, false),
            $this->get_does_not_contain_try_again_button_expectation(),
            $this->get_does_not_contain_correctness_expectation(),
            $this->get_contains_standard_partiallycorrect_combined_feedback_expectation(),
            $this->get_does_not_contain_hidden_expectation($this->quba->get_field_prefix($this->slot) . 'mc:choice0'),
            $this->get_does_not_contain_hidden_expectation($this->quba->get_field_prefix($this->slot) . 'mc:choice1'),
            $this->get_does_not_contain_hidden_expectation($this->quba->get_field_prefix($this->slot) . 'mc:choice2'),
            $this->get_does_not_contain_hidden_expectation($this->quba->get_field_prefix($this->slot) . 'mc:choice3'));

    }

    protected function get_contains_text_expectation($name, $value = null, $enabled = true) {
        $expectedattributes = array('type' => 'text', 'name' => $this->quba->get_field_prefix($this->slot) . s($name));
        $forbiddenattributes = array();
        if (!is_null($value)) {
            $expectedattributes['value'] = s($value);
        }
        $readonlyattribute = array('readonly' => 'readonly');
        if ($enabled === true) {
            $forbiddenattributes += $readonlyattribute;
        } else if ($enabled === false) {
            $expectedattributes += $readonlyattribute;
        }
        return new question_contains_tag_with_attributes('input', $expectedattributes, $forbiddenattributes);
    }

    public function test_interactive_behaviour_for_combined_question_with_ou_mr_pmatch_and_gapselect_subq() {
        if ($notfound = qtype_combined_test_helper::safe_include_test_helpers('oumultiresponse', 'gapselect', 'pmatch')) {
            $this->markTestSkipped($notfound);
        }
        // Create a combined question.
        $combined = qtype_combined_test_helper::make_a_combined_question_with_oumr_pmatch_and_gapselect_subquestion();

        $this->start_attempt_at_question($combined, 'interactive', 6);

        // Check the initial state.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
            $this->get_contains_mc_checkbox_expectation('mc:choice0', true, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice1', true, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice2', true, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice3', true, false),
            $this->get_contains_select_expectation('gs:p1',
                                           array('' => get_string('choosedots'), '1' => 'quick', '2' => 'slow'), null, true),
            $this->get_contains_select_expectation('gs:p2',
                                           array('' => get_string('choosedots'), '1' => 'fox', '2' => 'dog'), null, true),
            $this->get_contains_select_expectation('gs:p3',
                                           array('' => get_string('choosedots'), '1' => 'lazy', '2' => 'assiduous'), null, true),
            $this->get_contains_text_expectation('pm:answer', '', true),
            $this->get_contains_submit_button_expectation(true),
            $this->get_does_not_contain_feedback_expectation(),
            $this->get_does_not_contain_num_parts_correct(),
            $this->get_tries_remaining_expectation(4),
            $this->get_no_hint_visible_expectation());

        // Save the wrong answer.
        $this->process_submission(array('mc:choice1' => '1', 'mc:choice3' => '1',
                                        'gs:p1' => '2', 'gs:p2' => '2', 'gs:p3' => '2',
                                        'pm:answer' => 'Sarah'));

        // Verify.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
            $this->get_contains_mc_checkbox_expectation('mc:choice0', true, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice1', true, true),
            $this->get_contains_mc_checkbox_expectation('mc:choice2', true, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice3', true, true),
            $this->get_contains_select_expectation('gs:p1',
                                               array('' => get_string('choosedots'), '1' => 'quick', '2' => 'slow'), 2, true),
            $this->get_contains_select_expectation('gs:p2',
                                               array('' => get_string('choosedots'), '1' => 'fox', '2' => 'dog'), 2, true),
            $this->get_contains_select_expectation('gs:p3',
                                               array('' => get_string('choosedots'), '1' => 'lazy', '2' => 'assiduous'), 2, true),
            $this->get_contains_text_expectation('pm:answer', 'Sarah', true),
            $this->get_contains_submit_button_expectation(true),
            $this->get_does_not_contain_feedback_expectation(),
            $this->get_does_not_contain_num_parts_correct(),
            $this->get_tries_remaining_expectation(4),
            $this->get_no_hint_visible_expectation());

        // Submit the wrong answer.
        $this->process_submission(array('mc:choice1' => '1', 'mc:choice3' => '1',
                                        'gs:p1' => '2', 'gs:p2' => '2', 'gs:p3' => '2',
                                        'pm:answer' => 'Sarah',
                                        '-submit' => '1'));

        // Verify.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
            $this->get_contains_mc_checkbox_expectation('mc:choice0', false, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice1', false, true),
            $this->get_contains_mc_checkbox_expectation('mc:choice2', false, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice3', false, true),
            $this->get_contains_select_expectation('gs:p1',
                                           array('' => get_string('choosedots'), '1' => 'quick', '2' => 'slow'), 2, false),
            $this->get_contains_select_expectation('gs:p2',
                                           array('' => get_string('choosedots'), '1' => 'fox', '2' => 'dog'), 2, false),
            $this->get_contains_select_expectation('gs:p3',
                                           array('' => get_string('choosedots'), '1' => 'lazy', '2' => 'assiduous'), 2, false),
            $this->get_contains_text_expectation('pm:answer', 'Sarah', false),
            $this->get_contains_try_again_button_expectation(true),
            $this->get_does_not_contain_correctness_expectation(),
            $this->get_contains_hint_expectation('Hint 1'),
            $this->get_contains_num_parts_correct(0),
            $this->get_contains_standard_incorrect_combined_feedback_expectation(),
            $this->get_does_not_contain_hidden_expectation(
                $this->quba->get_field_prefix($this->slot) . 'mc:choice0'),
            $this->get_does_not_contain_hidden_expectation(
                $this->quba->get_field_prefix($this->slot) . 'mc:choice1'),
            $this->get_does_not_contain_hidden_expectation(
                $this->quba->get_field_prefix($this->slot) . 'mc:choice2'),
            $this->get_does_not_contain_hidden_expectation(
                $this->quba->get_field_prefix($this->slot) . 'mc:choice3'));

        // Do try again.
        $this->process_submission(array('-tryagain' => 1));

        // Verify.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
            $this->get_contains_mc_checkbox_expectation('mc:choice0', true, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice1', true, true),
            $this->get_contains_mc_checkbox_expectation('mc:choice2', true, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice3', true, true),
            $this->get_contains_select_expectation('gs:p1',
                                               array('' => get_string('choosedots'), '1' => 'quick', '2' => 'slow'), 2, true),
            $this->get_contains_select_expectation('gs:p2',
                                               array('' => get_string('choosedots'), '1' => 'fox', '2' => 'dog'), 2, true),
            $this->get_contains_select_expectation('gs:p3',
                                               array('' => get_string('choosedots'), '1' => 'lazy', '2' => 'assiduous'), 2, true),
            $this->get_contains_text_expectation('pm:answer', 'Sarah', true),
            $this->get_contains_submit_button_expectation(true),
            $this->get_does_not_contain_feedback_expectation(),
            $this->get_does_not_contain_num_parts_correct(),
            $this->get_tries_remaining_expectation(3),
            $this->get_no_hint_visible_expectation());

        // Submit a partially right answer.
        // Multichoice half right, gapselect wrong, pmatch is right. This is the second try, so 33% penalty applied.
        // Sub question weighting is gs 0.5, pm 0.25 and mc 0.25.
        // Total grade = 6 * (0.5 * 0.25 + 0.25) * 66% = 1.5.
        $this->process_submission(array('mc:choice0' => '1', 'mc:choice3' => '1',
                                        'gs:p1' => '2', 'gs:p2' => '2', 'gs:p3' => '2',
                                        'pm:answer' => 'Tom',
                                        '-submit' => '1'));

        // Verify.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
            $this->get_contains_mc_checkbox_expectation('mc:choice0', false, true),
            $this->get_contains_mc_checkbox_expectation('mc:choice1', false, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice2', false, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice3', false, true),
            $this->get_contains_select_expectation('gs:p1',
                                               array('' => get_string('choosedots'), '1' => 'quick', '2' => 'slow'), 2, false),
            $this->get_contains_select_expectation('gs:p2',
                                               array('' => get_string('choosedots'), '1' => 'fox', '2' => 'dog'), 2, false),
            $this->get_contains_select_expectation('gs:p3',
                                               array('' => get_string('choosedots'), '1' => 'lazy', '2' => 'assiduous'), 2, false),
            $this->get_contains_text_expectation('pm:answer', 'Tom', false),
            $this->get_contains_try_again_button_expectation(true),
            $this->get_does_not_contain_correctness_expectation(),
            $this->get_contains_hint_expectation('Hint 2'),
            $this->get_contains_num_parts_correct(2),
            $this->get_contains_standard_partiallycorrect_combined_feedback_expectation(),
            $this->get_does_not_contain_hidden_expectation($this->quba->get_field_prefix($this->slot) . 'mc:choice0'),
            $this->get_does_not_contain_hidden_expectation($this->quba->get_field_prefix($this->slot) . 'mc:choice1'),
            $this->get_does_not_contain_hidden_expectation($this->quba->get_field_prefix($this->slot) . 'mc:choice2'),
            $this->get_does_not_contain_hidden_expectation($this->quba->get_field_prefix($this->slot) . 'mc:choice3'));

        // Do try again.
        $this->process_submission(array('-tryagain' => 1));

        // Verify.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
            $this->get_contains_mc_checkbox_expectation('mc:choice0', true, true),
            $this->get_contains_mc_checkbox_expectation('mc:choice1', true, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice2', true, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice3', true, true),
            $this->get_contains_select_expectation('gs:p1',
                                               array('' => get_string('choosedots'), '1' => 'quick', '2' => 'slow'), 2, true),
            $this->get_contains_select_expectation('gs:p2',
                                               array('' => get_string('choosedots'), '1' => 'fox', '2' => 'dog'), 2, true),
            $this->get_contains_select_expectation('gs:p3',
                                               array('' => get_string('choosedots'), '1' => 'lazy', '2' => 'assiduous'), 2, true),
            $this->get_contains_text_expectation('pm:answer', 'Tom', true),
            $this->get_contains_submit_button_expectation(true),
            $this->get_does_not_contain_feedback_expectation(),
            $this->get_does_not_contain_num_parts_correct(),
            $this->get_tries_remaining_expectation(2),
            $this->get_no_hint_visible_expectation());

        // Submit the right answer.
        // Right this try - gs and half or mc.
        // Additional grade for right this time :
        // gs : 6 * 0.5 * 33%
        // mc : 6 * 0.5 * 0.25 * 33%
        // Sub total addition for this try : 1.25
        // Plus right last try : 1.5
        // Total : 2.75.
        $this->process_submission(array('mc:choice0' => '1', 'mc:choice2' => '1',
                                        'gs:p1' => '1', 'gs:p2' => '1', 'gs:p3' => '1',
                                        'pm:answer' => 'Tom',
                                        '-submit' => '1'));

        // Verify.
        $this->check_current_state(question_state::$gradedright);
        $this->check_current_mark(2.75);
        $this->check_current_output(
            $this->get_contains_mc_checkbox_expectation('mc:choice0', false, true),
            $this->get_contains_mc_checkbox_expectation('mc:choice1', false, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice2', false, true),
            $this->get_contains_mc_checkbox_expectation('mc:choice3', false, false),
            $this->get_contains_select_expectation('gs:p1',
                                               array('' => get_string('choosedots'), '1' => 'quick', '2' => 'slow'), 1, false),
            $this->get_contains_select_expectation('gs:p2',
                                               array('' => get_string('choosedots'), '1' => 'fox', '2' => 'dog'), 1, false),
            $this->get_contains_select_expectation('gs:p3',
                                               array('' => get_string('choosedots'), '1' => 'lazy', '2' => 'assiduous'), 1, false),
            $this->get_contains_text_expectation('pm:answer', 'Tom', false),
            $this->get_does_not_contain_try_again_button_expectation(),
            $this->get_contains_correct_expectation(),
            $this->get_contains_standard_correct_combined_feedback_expectation());

    }

    public function test_interactive_behaviour_for_combined_question_with_ou_mr_pmatch_varnum_and_gapselect_subq() {
        if ($notfound = qtype_combined_test_helper::safe_include_test_helpers('oumultiresponse', 'gapselect', 'pmatch',
                                                                              'varnumericset')) {
            $this->markTestSkipped($notfound);
        }
        // Create a combined question.
        $combined = qtype_combined_test_helper::make_a_combined_question_with_oumr_pmatch_varnum_and_gapselect_subquestion();

        $this->start_attempt_at_question($combined, 'interactive', 6);

        // Check the initial state.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
            $this->get_contains_mc_checkbox_expectation('mc:choice0', true, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice1', true, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice2', true, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice3', true, false),
            $this->get_contains_select_expectation('gs:p1',
                                           array('' => get_string('choosedots'), '1' => 'quick', '2' => 'slow'), null, true),
            $this->get_contains_select_expectation('gs:p2',
                                           array('' => get_string('choosedots'), '1' => 'fox', '2' => 'dog'), null, true),
            $this->get_contains_select_expectation('gs:p3',
                                           array('' => get_string('choosedots'), '1' => 'lazy', '2' => 'assiduous'), null, true),
            $this->get_contains_text_expectation('pm:answer', '', true),
            $this->get_contains_text_expectation('vn:answer', '', true),
            $this->get_contains_submit_button_expectation(true),
            $this->get_does_not_contain_feedback_expectation(),
            $this->get_does_not_contain_num_parts_correct(),
            $this->get_tries_remaining_expectation(4),
            $this->get_no_hint_visible_expectation());

        // Save the wrong answer.
        $this->process_submission(array('mc:choice1' => '1', 'mc:choice3' => '1',
                                        'gs:p1' => '2', 'gs:p2' => '2', 'gs:p3' => '2',
                                        'pm:answer' => 'Sarah',
                                        'vn:answer' => '-4'));

        // Verify.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
            $this->get_contains_mc_checkbox_expectation('mc:choice0', true, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice1', true, true),
            $this->get_contains_mc_checkbox_expectation('mc:choice2', true, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice3', true, true),
            $this->get_contains_select_expectation('gs:p1',
                                           array('' => get_string('choosedots'), '1' => 'quick', '2' => 'slow'), 2, true),
            $this->get_contains_select_expectation('gs:p2',
                                           array('' => get_string('choosedots'), '1' => 'fox', '2' => 'dog'), 2, true),
            $this->get_contains_select_expectation('gs:p3',
                                           array('' => get_string('choosedots'), '1' => 'lazy', '2' => 'assiduous'), 2, true),
            $this->get_contains_text_expectation('pm:answer', 'Sarah', true),
            $this->get_contains_text_expectation('vn:answer', '-4', true),
            $this->get_contains_submit_button_expectation(true),
            $this->get_does_not_contain_feedback_expectation(),
            $this->get_does_not_contain_num_parts_correct(),
            $this->get_tries_remaining_expectation(4),
            $this->get_no_hint_visible_expectation());

        // Submit the wrong answer.
        $this->process_submission(array('mc:choice1' => '1', 'mc:choice3' => '1',
                                        'gs:p1' => '2', 'gs:p2' => '2', 'gs:p3' => '2',
                                        'pm:answer' => 'Sarah',
                                        'vn:answer' => '-4',
                                        '-submit' => '1'));

        // Verify.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
            $this->get_contains_mc_checkbox_expectation('mc:choice0', false, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice1', false, true),
            $this->get_contains_mc_checkbox_expectation('mc:choice2', false, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice3', false, true),
            $this->get_contains_select_expectation('gs:p1',
                                               array('' => get_string('choosedots'), '1' => 'quick', '2' => 'slow'), 2, false),
            $this->get_contains_select_expectation('gs:p2',
                                               array('' => get_string('choosedots'), '1' => 'fox', '2' => 'dog'), 2, false),
            $this->get_contains_select_expectation('gs:p3',
                                               array('' => get_string('choosedots'), '1' => 'lazy', '2' => 'assiduous'), 2, false),
            $this->get_contains_text_expectation('pm:answer', 'Sarah', false),
            $this->get_contains_text_expectation('vn:answer', '-4', false),
            $this->get_contains_try_again_button_expectation(true),
            $this->get_does_not_contain_correctness_expectation(),
            $this->get_contains_hint_expectation('Hint 1'),
            $this->get_contains_num_parts_correct(0),
            $this->get_contains_standard_incorrect_combined_feedback_expectation(),
            $this->get_does_not_contain_hidden_expectation(
                $this->quba->get_field_prefix($this->slot) . 'mc:choice0'),
            $this->get_does_not_contain_hidden_expectation(
                $this->quba->get_field_prefix($this->slot) . 'mc:choice1'),
            $this->get_does_not_contain_hidden_expectation(
                $this->quba->get_field_prefix($this->slot) . 'mc:choice2'),
            $this->get_does_not_contain_hidden_expectation(
                $this->quba->get_field_prefix($this->slot) . 'mc:choice3'));

        // Do try again.
        $this->process_submission(array('-tryagain' => 1));

        // Verify.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
            $this->get_contains_mc_checkbox_expectation('mc:choice0', true, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice1', true, true),
            $this->get_contains_mc_checkbox_expectation('mc:choice2', true, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice3', true, true),
            $this->get_contains_select_expectation('gs:p1',
                                               array('' => get_string('choosedots'), '1' => 'quick', '2' => 'slow'), 2, true),
            $this->get_contains_select_expectation('gs:p2',
                                               array('' => get_string('choosedots'), '1' => 'fox', '2' => 'dog'), 2, true),
            $this->get_contains_select_expectation('gs:p3',
                                               array('' => get_string('choosedots'), '1' => 'lazy', '2' => 'assiduous'), 2, true),
            $this->get_contains_text_expectation('pm:answer', 'Sarah', true),
            $this->get_contains_text_expectation('vn:answer', '-4', true),
            $this->get_contains_submit_button_expectation(true),
            $this->get_does_not_contain_feedback_expectation(),
            $this->get_does_not_contain_num_parts_correct(),
            $this->get_tries_remaining_expectation(3),
            $this->get_no_hint_visible_expectation());

        // Submit a partially right answer.
        // Multichoice half right, gapselect wrong, numeric and pmatch is right. This is the second try, so 33% penalty applied.
        // Sub question weighting is gs 0.5, pm 0.25 and mc 0.25.
        // Total grade = 6 * (0.5 * 0.25 + 0.25 + 0.25) * 66% = 2.5.
        $this->process_submission(array('mc:choice0' => '1', 'mc:choice3' => '1',
                                        'gs:p1' => '2', 'gs:p2' => '2', 'gs:p3' => '2',
                                        'pm:answer' => 'Tom',
                                        'vn:answer' => '-4.2',
                                        '-submit' => '1'));

        // Verify.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
            $this->get_contains_mc_checkbox_expectation('mc:choice0', false, true),
            $this->get_contains_mc_checkbox_expectation('mc:choice1', false, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice2', false, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice3', false, true),
            $this->get_contains_select_expectation('gs:p1',
                                               array('' => get_string('choosedots'), '1' => 'quick', '2' => 'slow'), 2, false),
            $this->get_contains_select_expectation('gs:p2',
                                               array('' => get_string('choosedots'), '1' => 'fox', '2' => 'dog'), 2, false),
            $this->get_contains_select_expectation('gs:p3',
                                               array('' => get_string('choosedots'), '1' => 'lazy', '2' => 'assiduous'), 2, false),
            $this->get_contains_text_expectation('pm:answer', 'Tom', false),
            $this->get_contains_text_expectation('vn:answer', '-4.2', false),
            $this->get_contains_try_again_button_expectation(true),
            $this->get_does_not_contain_correctness_expectation(),
            $this->get_contains_hint_expectation('Hint 2'),
            $this->get_contains_num_parts_correct(3),
            $this->get_contains_standard_partiallycorrect_combined_feedback_expectation(),
            $this->get_does_not_contain_hidden_expectation($this->quba->get_field_prefix($this->slot) . 'mc:choice0'),
            $this->get_does_not_contain_hidden_expectation($this->quba->get_field_prefix($this->slot) . 'mc:choice1'),
            $this->get_does_not_contain_hidden_expectation($this->quba->get_field_prefix($this->slot) . 'mc:choice2'),
            $this->get_does_not_contain_hidden_expectation($this->quba->get_field_prefix($this->slot) . 'mc:choice3'));

        // Do try again.
        $this->process_submission(array('-tryagain' => 1));

        // Verify.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
            $this->get_contains_mc_checkbox_expectation('mc:choice0', true, true),
            $this->get_contains_mc_checkbox_expectation('mc:choice1', true, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice2', true, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice3', true, true),
            $this->get_contains_select_expectation('gs:p1',
                                               array('' => get_string('choosedots'), '1' => 'quick', '2' => 'slow'), 2, true),
            $this->get_contains_select_expectation('gs:p2',
                                               array('' => get_string('choosedots'), '1' => 'fox', '2' => 'dog'), 2, true),
            $this->get_contains_select_expectation('gs:p3',
                                               array('' => get_string('choosedots'), '1' => 'lazy', '2' => 'assiduous'), 2, true),
            $this->get_contains_text_expectation('pm:answer', 'Tom', true),
            $this->get_contains_text_expectation('vn:answer', '-4.2', true),
            $this->get_contains_submit_button_expectation(true),
            $this->get_does_not_contain_feedback_expectation(),
            $this->get_does_not_contain_num_parts_correct(),
            $this->get_tries_remaining_expectation(2),
            $this->get_no_hint_visible_expectation());

        // Submit the right answer.
        // Right this try - gs and half or mc.
        // Additional grade for right this time :
        // gs : 6 * 0.25 * 33%
        // mc : 6 * 0.5 * 0.25 * 33%
        // Sub total addition for this try : 0.75
        // Plus right last try : 2.5
        // Total : 3.25.
        $this->process_submission(array('mc:choice0' => '1', 'mc:choice2' => '1',
                                        'gs:p1' => '1', 'gs:p2' => '1', 'gs:p3' => '1',
                                        'pm:answer' => 'Tom',
                                        'vn:answer' => '-4.2',
                                        '-submit' => '1'));

        // Verify.
        $this->check_current_state(question_state::$gradedright);
        $this->check_current_mark(3.25);
        $this->check_current_output(
            $this->get_contains_mc_checkbox_expectation('mc:choice0', false, true),
            $this->get_contains_mc_checkbox_expectation('mc:choice1', false, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice2', false, true),
            $this->get_contains_mc_checkbox_expectation('mc:choice3', false, false),
            $this->get_contains_select_expectation('gs:p1',
                                               array('' => get_string('choosedots'), '1' => 'quick', '2' => 'slow'), 1, false),
            $this->get_contains_select_expectation('gs:p2',
                                               array('' => get_string('choosedots'), '1' => 'fox', '2' => 'dog'), 1, false),
            $this->get_contains_select_expectation('gs:p3',
                                               array('' => get_string('choosedots'), '1' => 'lazy', '2' => 'assiduous'), 1, false),
            $this->get_contains_text_expectation('pm:answer', 'Tom', false),
            $this->get_contains_text_expectation('vn:answer', '-4.2', false),
            $this->get_does_not_contain_try_again_button_expectation(),
            $this->get_contains_correct_expectation(),
            $this->get_contains_standard_correct_combined_feedback_expectation());

    }


    public function test_deferred_feedback_for_combined_question_with_ou_mr_pmatch_varnum_and_gapselect_subq() {
        if ($notfound = qtype_combined_test_helper::safe_include_test_helpers('oumultiresponse', 'gapselect', 'pmatch',
                                                                              'varnumericset')) {
            $this->markTestSkipped($notfound);
        }
        // Create a combined question.
        $combined = qtype_combined_test_helper::make_a_combined_question_with_oumr_pmatch_varnum_and_gapselect_subquestion();

        $this->start_attempt_at_question($combined, 'deferredfeedback', 6);

        // Check the initial state.
        $this->check_current_state(question_state::$todo);
        $this->check_current_mark(null);
        $this->check_current_output(
            $this->get_contains_mc_checkbox_expectation('mc:choice0', true, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice1', true, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice2', true, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice3', true, false),
            $this->get_contains_select_expectation('gs:p1',
                                       array('' => get_string('choosedots'), '1' => 'quick', '2' => 'slow'), null, true),
            $this->get_contains_select_expectation('gs:p2',
                                       array('' => get_string('choosedots'), '1' => 'fox', '2' => 'dog'), null, true),
            $this->get_contains_select_expectation('gs:p3',
                                       array('' => get_string('choosedots'), '1' => 'lazy', '2' => 'assiduous'), null, true),
            $this->get_contains_text_expectation('pm:answer', '', true),
            $this->get_contains_text_expectation('vn:answer', '', true),
            $this->get_does_not_contain_feedback_expectation(),
            $this->get_does_not_contain_num_parts_correct(),
            $this->get_no_hint_visible_expectation());

        // Save incomplete, not correct answer.
        $this->process_submission(array('mc:choice1' => '1', 'mc:choice3' => '1',
                                        'gs:p1' => '2', 'gs:p2' => '2', 'gs:p3' => '2',
                                        'pm:answer' => '',
                                        'vn:answer' => '-4'));

        // Verify.
        $this->check_current_state(question_state::$invalid);
        $this->check_current_mark(null);
        $this->check_current_output(
            $this->get_contains_mc_checkbox_expectation('mc:choice0', true, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice1', true, true),
            $this->get_contains_mc_checkbox_expectation('mc:choice2', true, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice3', true, true),
            $this->get_contains_select_expectation('gs:p1',
                                               array('' => get_string('choosedots'), '1' => 'quick', '2' => 'slow'), 2, true),
            $this->get_contains_select_expectation('gs:p2',
                                               array('' => get_string('choosedots'), '1' => 'fox', '2' => 'dog'), 2, true),
            $this->get_contains_select_expectation('gs:p3',
                                               array('' => get_string('choosedots'), '1' => 'lazy', '2' => 'assiduous'), 2, true),
            $this->get_contains_text_expectation('pm:answer', '', true),
            $this->get_contains_text_expectation('vn:answer', '-4', true),
            $this->get_does_not_contain_feedback_expectation(),
            $this->get_does_not_contain_num_parts_correct(),
            $this->get_no_hint_visible_expectation(),
            $this->get_contains_validation_error_expectation(),
            $this->get_does_not_contain_try_again_button_expectation());

        // Save the correct answer.
        $this->process_submission(array('mc:choice0' => '1', 'mc:choice2' => '1',
                                        'gs:p1' => '1', 'gs:p2' => '1', 'gs:p3' => '1',
                                        'pm:answer' => 'Tom',
                                        'vn:answer' => '-4.2'));

        // Verify.
        $this->check_current_state(question_state::$complete);
        $this->check_current_output(
            $this->get_contains_mc_checkbox_expectation('mc:choice0', true, true),
            $this->get_contains_mc_checkbox_expectation('mc:choice1', true, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice2', true, true),
            $this->get_contains_mc_checkbox_expectation('mc:choice3', true, false),
            $this->get_contains_select_expectation('gs:p1',
                                               array('' => get_string('choosedots'), '1' => 'quick', '2' => 'slow'), 1, true),
            $this->get_contains_select_expectation('gs:p2',
                                               array('' => get_string('choosedots'), '1' => 'fox', '2' => 'dog'), 1, true),
            $this->get_contains_select_expectation('gs:p3',
                                               array('' => get_string('choosedots'), '1' => 'lazy', '2' => 'assiduous'), 1, true),
            $this->get_contains_text_expectation('pm:answer', 'Tom', true),
            $this->get_contains_text_expectation('vn:answer', '-4.2', true),
            $this->get_does_not_contain_feedback_expectation(),
            $this->get_does_not_contain_num_parts_correct(),
            $this->get_no_hint_visible_expectation(),
            $this->get_does_not_contain_try_again_button_expectation());

        $this->process_submission(array('-finish' => '1'));

        // Verify.
        $this->check_current_state(question_state::$gradedright);
        $this->check_current_mark(6);
        $this->check_current_output(
            $this->get_contains_mc_checkbox_expectation('mc:choice0', false, true),
            $this->get_contains_mc_checkbox_expectation('mc:choice1', false, false),
            $this->get_contains_mc_checkbox_expectation('mc:choice2', false, true),
            $this->get_contains_mc_checkbox_expectation('mc:choice3', false, false),
            $this->get_contains_select_expectation('gs:p1',
                                               array('' => get_string('choosedots'), '1' => 'quick', '2' => 'slow'), 1, false),
            $this->get_contains_select_expectation('gs:p2',
                                               array('' => get_string('choosedots'), '1' => 'fox', '2' => 'dog'), 1, false),
            $this->get_contains_select_expectation('gs:p3',
                                               array('' => get_string('choosedots'), '1' => 'lazy', '2' => 'assiduous'), 1, false),
            $this->get_contains_text_expectation('pm:answer', 'Tom', false),
            $this->get_contains_text_expectation('vn:answer', '-4.2', false),
            $this->get_contains_standard_correct_combined_feedback_expectation(),
            $this->get_does_not_contain_num_parts_correct(),
            $this->get_no_hint_visible_expectation(),
            $this->get_does_not_contain_try_again_button_expectation());

    }

    /**
     * Test convert to space, synonyms and case sensitivity for combined pattern match question.
     */
    public function test_combined_question_synonyms_and_convert() {
        // Create a combined question.
        question_bank::load_question_definition_classes('combined');
        $combined = new qtype_combined_question();

        test_question_maker::initialise_a_question($combined);

        $combined->name = 'Selection from drop down list question';
        $combined->questiontext = '[[1:pmatch]]';
        $combined->generalfeedback = '';
        $combined->qtype = question_bank::get_qtype('combined');
        test_question_maker::set_standard_combined_feedback_fields($combined);

        $combined->combiner = new qtype_combined_combiner_for_run_time_question_instance();
        $combined->combiner->find_included_subqs_in_question_text($combined->questiontext);

        // Pattern match sub question.
        question_bank::load_question_definition_classes('pmatch');
        $subq = $combined->combiner->find_or_create_question_instance('pmatch', 1);

        $pmatch = new qtype_pmatch_question();
        test_question_maker::initialise_a_question($pmatch);
        $pmatch->qtype = question_bank::get_qtype('pmatch');
        $pmatch->name = '1';
        $pmatch->questiontext = '';
        $pmatch->generalfeedback = '';
        $pmatch->pmatchoptions = new pmatch_options();
        $pmatch->answers = array(
                1 => new question_answer(1, 'match(Number ten)', 1, '', FORMAT_HTML),
        );
        $pmatch->generalfeedback = '';
        $pmatch->applydictionarycheck = false;
        $pmatch->pmatchoptions->converttospace = ':';
        $pmatch->pmatchoptions->set_synonyms(array((object)array('word' => 'ten', 'synonyms' => '10')));
        $pmatch->pmatchoptions->ignorecase = true;
        $subq->question = $pmatch;

        // Check convert to space.
        $this->assertEquals(array(1, question_state::$gradedright), $combined->grade_response(array('1:answer' => 'Number ten')));
        $this->assertEquals(array(1, question_state::$gradedright), $combined->grade_response(array('1:answer' => 'Number:ten')));
        $this->assertEquals(array(0, question_state::$gradedwrong), $combined->grade_response(array('1:answer' => 'Number;ten')));

        // Check synonyms.
        $this->assertEquals(array(1, question_state::$gradedright), $combined->grade_response(array('1:answer' => 'Number 10')));
        $this->assertEquals(array(0, question_state::$gradedwrong), $combined->grade_response(array('1:answer' => 'Number eight')));

        // Check synonyms and convert to space.
        $this->assertEquals(array(1, question_state::$gradedright), $combined->grade_response(array('1:answer' => 'Number:10')));

        // Case sensitive.
        $this->assertEquals(array(1, question_state::$gradedright), $combined->grade_response(array('1:answer' => 'NUMBER TEN')));

        $subq2 = $combined->combiner->find_or_create_question_instance('pmatch', 2);
        // Add one more pattern match question to check partial grade.
        $pmatch2 = new qtype_pmatch_question();
        test_question_maker::initialise_a_question($pmatch);
        $pmatch2->qtype = question_bank::get_qtype('pmatch');
        $pmatch2->name = '2';
        $pmatch2->questiontext = '';
        $pmatch2->generalfeedback = '';
        $pmatch2->pmatchoptions = new pmatch_options();
        $pmatch2->answers = array(
                2 => new question_answer(2, 'match(Number nine)', 1, '', FORMAT_HTML),
        );
        $pmatch2->generalfeedback = '';
        $pmatch2->applydictionarycheck = false;
        $pmatch2->pmatchoptions->ignorecase = false;
        $subq2->question = $pmatch2;

        $this->assertEquals(array(2, question_state::$gradedright), $combined->grade_response(array(
                '1:answer' => 'NUMBER TEN', '2:answer' => 'Number nine'))
        );
        $this->assertEquals(array(1, question_state::$gradedpartial), $combined->grade_response(array(
                '1:answer' => 'NUMBER TEN', '2:answer' => 'NUMBER NINE'))
        );
        $this->assertEquals(array(0, question_state::$gradedwrong), $combined->grade_response(array(
                '1:answer' => 'NUMBER EIGHT', '2:answer' => 'NUMBER NINE'))
        );
    }

}
