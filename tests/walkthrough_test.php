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
                        $this->get_contains_submit_button_expectation(false),
                $this->get_contains_try_again_button_expectation(true),
                $this->get_does_not_contain_correctness_expectation(),
                new question_pattern_expectation('/' . preg_quote(
                        get_string('notcomplete', 'qbehaviour_interactive'), '/') . '/'),
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
                $this->get_contains_submit_button_expectation(false),
                $this->get_contains_correct_expectation(),
                $this->get_no_hint_visible_expectation());

        // Check regrading does not mess anything up.
        $this->quba->regrade_all_questions();

        // Verify.
        $this->check_current_state(question_state::$gradedright);
        $this->check_current_mark(2);
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
            $this->get_contains_submit_button_expectation(false),
            $this->get_contains_try_again_button_expectation(true),
            $this->get_does_not_contain_correctness_expectation(),
            new question_pattern_expectation('/' .
                                                 preg_quote(get_string('notcomplete', 'qbehaviour_interactive'), '/') . '/'),
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
            $this->get_contains_submit_button_expectation(false),
            $this->get_contains_try_again_button_expectation(true),
            $this->get_does_not_contain_correctness_expectation(),
            new question_pattern_expectation('/'.preg_quote(get_string('notcomplete', 'qbehaviour_interactive'), '/') . '/'),
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
            $this->get_contains_submit_button_expectation(false),
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
            $this->get_contains_submit_button_expectation(false),
            $this->get_contains_try_again_button_expectation(true),
            $this->get_does_not_contain_correctness_expectation(),
            new question_pattern_expectation('/' .
                                                 preg_quote(get_string('notcomplete', 'qbehaviour_interactive'), '/') . '/'),
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
            $this->get_contains_submit_button_expectation(false),
            $this->get_contains_try_again_button_expectation(true),
            $this->get_does_not_contain_correctness_expectation(),
            new question_pattern_expectation('/'.preg_quote(get_string('notcomplete', 'qbehaviour_interactive'), '/') . '/'),
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
            $this->get_contains_submit_button_expectation(false),
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
            $this->get_contains_submit_button_expectation(false),
            $this->get_contains_try_again_button_expectation(true),
            $this->get_does_not_contain_correctness_expectation(),
            new question_pattern_expectation('/' .
                                                 preg_quote(get_string('notcomplete', 'qbehaviour_interactive'), '/') . '/'),
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
            $this->get_contains_submit_button_expectation(false),
            $this->get_contains_try_again_button_expectation(true),
            $this->get_does_not_contain_correctness_expectation(),
            new question_pattern_expectation('/'.preg_quote(get_string('notcomplete', 'qbehaviour_interactive'), '/') . '/'),
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
            $this->get_contains_submit_button_expectation(false),
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
            $this->get_contains_submit_button_expectation(false),
            $this->get_contains_try_again_button_expectation(true),
            $this->get_does_not_contain_correctness_expectation(),
            new question_pattern_expectation('/' .
                                                 preg_quote(get_string('notcomplete', 'qbehaviour_interactive'), '/') . '/'),
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
            $this->get_contains_submit_button_expectation(false),
            $this->get_contains_try_again_button_expectation(true),
            $this->get_does_not_contain_correctness_expectation(),
            new question_pattern_expectation('/'.preg_quote(get_string('notcomplete', 'qbehaviour_interactive'), '/') . '/'),
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
            $this->get_contains_submit_button_expectation(false),
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
}
