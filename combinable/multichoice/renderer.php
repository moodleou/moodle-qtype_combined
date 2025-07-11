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
 * Combined question embedded sub-question renderer class.
 *
 * @package   qtype_combined
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/question/type/multichoice/renderer.php');

/**
 * Class question type combined multichoice embedded renderer.
 */
class qtype_combined_multichoice_embedded_renderer extends qtype_renderer
    implements qtype_combined_subquestion_renderer_interface {

    #[\Override]
    public function subquestion(question_attempt $qa,
                                question_display_options $options,
                                qtype_combined_combinable_base $subq,
                                $placeno) {
        $fullresponse = new qtype_combined_response_array_param($qa->get_last_qt_data());
        $response = $fullresponse->for_subq($subq);
        if (isset($response['answer'])) {
            $response = $response['answer'];
        } else {
            $response = -1;
        }
        $class = '';
        if ($response === -1) {
            $class = 'required';
        }
        $commonattributes = [
            'type' => 'radio',
            'class' => $class,
        ];
        if ($options->readonly) {
            $commonattributes['disabled'] = 'disabled';
        }
        $rbuttons = [];
        $feedbackimg = [];
        $classes = [];

        $question = $subq->question;
        foreach ($question->get_order($qa) as $value => $ansid) {
            $inputname = $qa->get_qt_field_name($subq->step_data_name('answer'));
            $ans = $question->answers[$ansid];
            $inputattributes = [];
            $inputattributes['name'] = $inputname;
            $inputattributes['value'] = $value;
            $inputattributes['id'] = $ansid;
            $inputattributes['aria-labelledby'] = $inputattributes['id'] . '_label';
            $isselected = $question->is_choice_selected($response, $value);
            if ($isselected) {
                $inputattributes['checked'] = 'checked';
            } else {
                unset($inputattributes['checked']);
            }

            $choice = html_writer::div($question->format_text($ans->answer, $ans->answerformat, $qa,
                'question', 'answer', $ansid), 'flex-fill ml-1');
            $rbuttons[] = html_writer::empty_tag('input', $inputattributes + $commonattributes) .
                html_writer::div(html_writer::span(\qtype_combined\utils::number_in_style($value, $question->answernumbering),
                'answernumber') . $choice, 'd-flex w-auto',
                ['data-region' => 'answer-label', 'id' => $inputattributes['id'] . '_label']);

            if ($options->feedback && $isselected && trim($ans->feedback)) {
                $feedback[] = html_writer::tag('span',
                    $question->make_html_inline($question->format_text($ans->feedback, $ans->feedbackformat,
                        $qa, 'question', 'answerfeedback', $ansid)), ['class' => ' subqspecificfeedback ']);
            } else {
                $feedback[] = '';
            }

            $class = 'r' . ($value % 2);
            if ($options->correctness && $isselected) {
                $feedbackimg[] = html_writer::span($this->feedback_image($ans->fraction), 'ml-1');
                $class .= ' ' . $this->feedback_class($ans->fraction);
            } else {
                $feedbackimg[] = '';
            }
            $classes[] = $class;
        }

        if ('h' === $subq->get_layout()) {
            $inputwraptag = 'span';
            $classname = 'horizontal';
        } else {
            $inputwraptag = 'div';
            $classname = 'vertical';
        }

        $rbhtml = '';
        foreach ($rbuttons as $key => $rb) {
            $feedbackcontent = '';
            if (!empty($feedback[$key])) {
                $feedbackcontent = html_writer::div($feedback[$key], 'feedback');
            }
            $rbhtml .= html_writer::tag($inputwraptag, $rb . ' ' . $feedbackimg[$key] . $feedbackcontent,
                ['class' => $classes[$key]]) . "\n";
        }

        $result = html_writer::tag($inputwraptag, $rbhtml, ['class' => 'answer']);
        $result = html_writer::div($result, $classname);

        // Load JS module for the question answers.
        if ($this->page->requires->should_create_one_time_item_now(
                'qtype_combined_choices_' . $qa->get_outer_question_div_unique_id())) {
            $this->page->requires->js_call_amd('qtype_multichoice/answers', 'init',
                [$qa->get_outer_question_div_unique_id()]);
        }

        return $result;
    }
}
