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

defined('MOODLE_INTERNAL') || die();

/**
 * This class implements just enough of the question_definition API so that we can
 * handle showworking instances like other question types.
 *
 * @package    qtype_showworking
 * @copyright  2022 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_combined_showworking_fake_question {

    /** @var question_type the question type this question is. */
    public $qtype;

    /** @var string question name. */
    public $name;

    /** @var float what this quetsion is marked out of, by default. */
    public $defaultmark = 0.0;

    /** @var float penalty factor of this question. */
    public $penalty = 0;

    public function __construct(string $name) {
        $this->qtype = new qtype_combined_showworking_fake_qtype();
        $this->name = $name;
    }

    /**
     * Overwrite start_attempt method in question_definition.
     *
     * @param question_attempt_step The first step of the {@link question_attempt} being started.
     * @param int $variant which variant of this question to start.
     */
    public function start_attempt(question_attempt_step $step, int $variant): void {
    }

    /**
     * Overwrite apply_attempt_state method in question_definition.
     *
     * @param question_attempt_step The first step of the question_attempt being loaded.
     */
    public function apply_attempt_state(question_attempt_step $step): void {
    }

    /**
     * Overwrite is_gradable_response method in question_definition.
     *
     * @param array $response A responses.
     * @return bool This response can be graded.
     */
    public function is_gradable_response(array $response): bool {
        return true;
    }

    /**
     * Overwrite is_complete_response method in question_definition.
     *
     * @param array $response A responses.
     * @return bool Whether this response is a complete answer to this question.
     */
    public function is_complete_response(array $response): bool {
        return true;
    }

    /**
     * Overwrite get_validation_error method in question_definition. Get error messages validation.
     *
     * @param array $response A responses.
     * @return string The message error.
     */
    public function get_validation_error(array $response): ?string {
        return null;
    }

    /**
     * Overwrite get_expected_data method in question_definition. Get data in form submission.
     *
     * @return array Array with name => value to take all the raw submitted data belonging to this question.
     */
    public function get_expected_data(): array {
        return ['answer' => PARAM_RAW_TRIMMED];
    }

    /**
     * Overwrite get_expected_data method in question_definition. Get data in form submission.
     *
     * @param array $prevresponse The responses previously recorded for this question,
     * @param array $newresponse The new responses, in the same format.
     * @return bool Are the two sets of responses the same?
     */
    public function is_same_response(array $prevresponse, array $newresponse): bool {
        return question_utils::arrays_same_at_key_missing_is_blank($prevresponse, $newresponse, 'answer');
    }

    /**
     * Overwrite summarise_response method in question_definition.
     *
     * @param array $response A response.
     * @return string|null The summarise response.
     */
    public function summarise_response(array $response): ?string {
        if (isset($response['answer'])) {
            return $response['answer'];
        } else {
            return null;
        }
    }

    /**
     * Implement the classify_response method in question_definition.
     *
     * @param array $response a response, as might be passed to {@link grade_response()}.
     * @return array subpartid => {@link question_classified_response} objects.
     *      returns an empty array if no analysis is possible.
     */
    public function classify_response(array $response): array {
        return [];
    }

    /**
     * Overwrite get_correct_response method in question_definition.
     *
     * @return array The correct response.
     */
    public function get_correct_response(): array {
        return [];
    }

    /**
     * Overwrite compute_final_grade method in question_definition.
     *
     * @param array $responses The response.
     * @param int $totaltries The maximum number of tries allowed.
     * @return int The correct response.
     */
    public function compute_final_grade(array $responses, int $totaltries): int {
        return 1;
    }

    /**
     * Overwrite grade_response in the question_definition return grade a response to the question.
     *
     * @param array $response A responses.
     * @return array Array with 2 elements: the fraction, and the state.
     */
    public function grade_response(array $response): array {
        return [1, null];
    }

    /**
     * Overwrite get_num_parts_right in the question_definition return the number of subparts of this response that are right.
     *
     * @param array $response A response.
     * @return array Array with two elements, the number of correct subparts, and the total number of subparts.
     */
    public function get_num_parts_right(array $response): array {
        return [0, 0];
    }

    /**
     * Overwrite format_generalfeedback in the question_definition. Return feedback.
     *
     * @param question_attempt $qa the question attempt.
     * @return string The result of applying to the general feedback.
     */
    public function format_generalfeedback(question_attempt $qa): string {
        return "";
    }

    /**
     * Overwrite clear_wrong_from_response in the question_definition. Return feedback.
     *
     * @param array $response A response.
     * @return array A cleaned up response with the wrong bits reset.
     */
    public function clear_wrong_from_response(array $response): array {
        return [];
    }

    /**
     * Provide validate_can_regrade_with_other_version from question_definition.
     *
     * @param question_definition $otherversion
     * @return string|null
     */
    public function validate_can_regrade_with_other_version(question_definition $otherversion): ?string {
        if (get_class($otherversion) !== get_class($this)) {
            return get_string('cannotregradedifferentqtype', 'question');
        }

        return null;
    }

    /**
     * Provide update_attempt_state_data_for_new_version from question_definition.
     *
     * @param question_attempt_step $oldstep
     * @param question_definition $oldquestion
     * @return array
     */
    public function update_attempt_state_data_for_new_version(
            question_attempt_step $oldstep, question_definition $oldquestion) {
        $message = $this->validate_can_regrade_with_other_version($oldquestion);
        if ($message) {
            throw new coding_exception($message);
        }

        return $oldstep->get_qt_data();
    }
}


/**
 * This class implements just enough of the question_type API so that we can
 * handle showworking instances like other question types.
 */
class qtype_combined_showworking_fake_qtype {

    /**
     * Get name of the question type.
     *
     * @return string The name of this question type.
     */
    public function name(): string {
        return 'showworking';
    }
}
