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
 * Combined question renderer class.
 *
 * @package    qtype_combined
 * @copyright  2013 The Open University
 * @author     Jamie Pratt <me@jamiep.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * Generates the output for combined questions.
 *
 * @copyright  2013 The Open University
 * @author     Jamie Pratt <me@jamiep.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_combined_renderer extends qtype_with_combined_feedback_renderer {

    #[\Override]
    public function formulation_and_controls(question_attempt $qa,
            question_display_options $options) {

        $question = $qa->get_question();

        $questiontext = $question->format_questiontext($qa);

        $questiontext = $question->combiner->render_subqs($questiontext, $qa, $options);

        $result = html_writer::tag('div', $questiontext, ['class' => 'qtext']);

        if ($qa->get_state() == question_state::$invalid) {
            $result .= html_writer::nonempty_tag('div',
                $question->get_validation_error($qa->get_last_step()->get_all_data()), ['class' => 'validationerror']);
        }
        return $result;
    }

    #[\Override]
    public function specific_feedback(question_attempt $qa) {
        return $this->combined_feedback($qa);
    }

    #[\Override]
    public function feedback(question_attempt $qa, question_display_options $options) {
        $output = '';
        $hint = null;

        if ($options->feedback) {
            $output .= html_writer::nonempty_tag('div', $this->specific_feedback($qa), ['class' => 'specificfeedback']);
            $hint = $qa->get_applicable_hint();
        }

        if ($options->numpartscorrect) {
            $output .= html_writer::nonempty_tag('div', $this->num_parts_correct($qa), ['class' => 'numpartscorrect']);
        }

        if ($options->feedback) {
            $output .= html_writer::nonempty_tag('div', $qa->get_question()->combiner->feedback_for_suqs($qa, $options),
                ['class' => 'subqfeedback']);
        }

        if ($hint) {
            $output .= $this->hint($qa, $hint);
        }

        if ($options->generalfeedback) {
            $output .= html_writer::nonempty_tag('div', $this->general_feedback($qa), ['class' => 'generalfeedback']);
        }

        return $output;
    }

    #[\Override]
    protected function num_parts_correct(question_attempt $qa) {
        $a = new stdClass();
        list($a->num, $a->outof) = $qa->get_question()->get_num_parts_right($qa->get_last_qt_data());
        if (is_null($a->outof)) {
            return '';
        } else if ($a->num == 1) {
            return get_string('yougot1right', 'qtype_combined');
        } else {
            return get_string('yougotnright', 'qtype_combined', $a);
        }
    }

    #[\Override]
    public function clear_wrong(question_attempt $qa) {
        $question = $qa->get_question();
        $cleanresponses = $question->combiner->clear_wrong_from_response_for_all_subqs($qa->get_last_qt_data());

        $output = '';
        foreach ($cleanresponses as $name => $value) {
            $attr = [
                'type' => 'hidden',
                'name' => $qa->get_qt_field_name($name),
                'value' => $value,
            ];
            $output .= html_writer::empty_tag('input', $attr);
        }
        return $output;
    }
}

/**
 * Interface that must be implemented for generating the bits of output specific to sub-questions.
 *
 * @copyright 2013 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
interface qtype_combined_subquestion_renderer_interface {

    /**
     * Sub-question renderer method.
     *
     * @param question_attempt $qa The question attempt.
     * @param question_display_options $options Controls what should and should not be displayed.
     * @param qtype_combined_combinable_base $subq The sub-question to render.
     * @param int $placeno The place number of the sub-question.
     */
    public function subquestion(question_attempt $qa,
                                         question_display_options $options,
                                         qtype_combined_combinable_base $subq,
                                         $placeno);
}

/**
 * Combined text entry renderer base class.
 */
class qtype_combined_text_entry_renderer_base extends qtype_renderer
    implements qtype_combined_subquestion_renderer_interface {

    #[\Override]
    public function subquestion(question_attempt $qa,
                                         question_display_options $options,
                                         qtype_combined_combinable_base $subq,
                                         $placeno) {
        $question = $subq->question;
        $currentanswer = $this->prepare_current_answer($options, $qa->get_last_qt_var($subq->step_data_name('answer')), $subq);

        $inputname = $qa->get_qt_field_name($subq->step_data_name('answer'));

        $inputclass = 'answer';
        $requireclass = '';
        if (!$currentanswer) {
            $requireclass = ' required';
        }
        $generalattributes = array_merge([
            'id' => $inputname,
            'class' => $inputclass . $requireclass,
        ], $this->get_extra_input_attributes($question));
        $size = $subq->get_width();

        $feedbackimg = '';
        if ($options->correctness) {
            list($fraction, ) = $question->grade_response(['answer' => $currentanswer]);
            $generalattributes['class'] .= ' '.$this->feedback_class($fraction);
            $feedbackimg = $this->feedback_image($fraction);
        }

        $usehtml = false;
        $supsuboption = $subq->get_sup_sub_editor_option();
        if (null !== $supsuboption) {
            $editor = get_texteditor('ousupsub');
            if ($editor !== false) {
                $usehtml = true;
            }
        }

        if ($usehtml && $options->readonly) {
            $generalattributes['class'] .= ' mw-100 text-wrap';
            $input = html_writer::tag('span', $currentanswer, $generalattributes);
        } else if ($usehtml) {
            $textareaattributes = ['name' => $inputname, 'rows' => 2, 'cols' => $size];
            $input = html_writer::tag('span', html_writer::tag('textarea', $currentanswer,
                                                               $textareaattributes + $generalattributes),
                                                               ['class' => 'answerwrap' . $requireclass]);
            $supsuboptions = ['supsub' => $supsuboption];
            $editor->use_editor($generalattributes['id'], $supsuboptions);
        } else {
            $inputattributes = [
                'type' => 'text',
                'size' => $size,
                'name' => $inputname,
                'value' => $currentanswer,
            ];
            if ($options->readonly) {
                $inputattributes['readonly'] = 'readonly';
            }
            $input = html_writer::empty_tag('input', $inputattributes + $generalattributes);
        }

        $input .= $feedbackimg;

        // Add accessibility label for input.
        $inputinplace = html_writer::tag('label', get_string('answer') . ' ' . $subq->get_identifier(),
            ['for' => $generalattributes['id'], 'class' => 'accesshide']);
        $input = $inputinplace .= $input;

        return $input;
    }

    /**
     * Get the current answer. Allow the subq to override so they can modify the current answer.
     *
     * @param question_display_options $options $options controls what should and should not be displayed.
     * @param string|null $currentanswer
     * @param qtype_combined_combinable_base $subq
     * @return string|null
     */
    protected function prepare_current_answer(question_display_options $options, ?string $currentanswer,
        qtype_combined_combinable_base $subq): ?string {
        return $currentanswer;
    }

    /**
     * Get extra attributes for answer field.
     *
     * @param question_graded_automatically $question question object contain properties for each question type.
     * @return array
     */
    protected function get_extra_input_attributes(question_graded_automatically $question): array {
        return [];
    }
}
