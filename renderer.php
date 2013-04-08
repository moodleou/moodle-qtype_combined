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
 * @package    qtype
 * @subpackage combined
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
class qtype_combined_renderer extends qtype_renderer {
    public function formulation_and_controls(question_attempt $qa,
            question_display_options $options) {

        $question = $qa->get_question();

        $questiontext = $question->format_questiontext($qa);

        $questiontext = $question->combiner->render_subqs($questiontext, $qa, $options);


        $result = html_writer::tag('div', $questiontext, array('class' => 'qtext'));

        if ($qa->get_state() == question_state::$invalid) {
            $result .= html_writer::nonempty_tag('div',
                    $question->get_validation_error($qa->get_last_step()->get_all_data()),
                    array('class' => 'validationerror'));
        }
        return $result;
    }

    public function specific_feedback(question_attempt $qa) {
        // TODO needs to pass through to sub-questions.
        return '';
    }

    public function correct_response(question_attempt $qa) {
        // TODO needs to pass through to sub-questions.
        return '';
    }
}

/**
 * Subclass for generating the bits of output specific to sub-questions.
 *
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class qtype_combined_embedded_renderer_base extends qtype_renderer {

    abstract public function subquestion(question_attempt $qa,
                                         question_display_options $options,
                                         qtype_combined_combinable_base $subq);
}

class qtype_combined_text_entry_renderer_base extends qtype_combined_embedded_renderer_base {

    public function subquestion(question_attempt $qa,
                                         question_display_options $options,
                                         qtype_combined_combinable_base $subq) {
        $question = $subq->question;
        $currentanswer = $qa->get_last_qt_var($subq->field_name('answer'));

        $inputname = $qa->get_qt_field_name($subq->field_name('answer'));
        $generalattributes = array(
            'id' => $inputname,
            'class' => 'answer'
        );

        $size = $subq->get_width();

        $feedbackimg = '';
        if ($options->correctness) {
            list($fraction, ) = $question->grade_response(array('answer' => $currentanswer));
            $generalattributes['class'] .= ' '.$this->feedback_class($fraction);
            $feedbackimg = $this->feedback_image($fraction);
        }

        $usehtml = false;
        $supsuboption = $subq->get_sup_sub_editor_option();
        if (null !== $supsuboption) {
            $editor = get_texteditor('supsub');
            if ($editor !== false) {
                $usehtml = true;
            }
        }

        if ($usehtml && $options->readonly) {
            $input = html_writer::tag('span', $currentanswer, $generalattributes);
        } else if ($usehtml) {
            $textareaattributes = array('name' => $inputname, 'rows' => 2, 'cols' => $size);
            $input = html_writer::tag('span', html_writer::tag('textarea', $currentanswer,
                                                               $textareaattributes + $generalattributes),
                                                               array('class'=>'answerwrap'));
            $supsuboptions = array(
                'supsub' => $supsuboption
            );
            $editor->use_editor($generalattributes['id'], $supsuboptions);
        } else {
            $inputattributes = array(
                'type' => 'text',
                'size' => $size,
                'name' => $inputname,
                'value' => $currentanswer
            );
            if ($options->readonly) {
                $inputattributes['readonly'] = 'readonly';
            }
            $input = html_writer::empty_tag('input', $inputattributes + $generalattributes);
        }
        $input .= $feedbackimg;

        return $input;
    }
}

class qtype_combined_pmatch_embedded_renderer extends qtype_combined_text_entry_renderer_base {

}

class qtype_combined_varnumeric_embedded_renderer extends qtype_combined_text_entry_renderer_base {

}

class qtype_combined_gapselect_embedded_renderer extends qtype_combined_embedded_renderer_base {

    public function subquestion(question_attempt $qa,
                                question_display_options $options,
                                qtype_combined_combinable_base $subq) {
        return 'gapselect';
    }
}
class qtype_combined_oumultiresponse_embedded_renderer extends qtype_combined_embedded_renderer_base {

    public function subquestion(question_attempt $qa,
                                question_display_options $options,
                                qtype_combined_combinable_base $subq) {
        $question = $subq->question;
        $fullresponse = new qtype_combined_response_array_param($qa->get_last_qt_data());
        $response = $fullresponse->for_subq($subq);

        $commonattributes = array(
            'type' => 'checkbox'
        );

        if ($options->readonly) {
            $commonattributes['disabled'] = 'disabled';
        }

        $checkboxes = array();
        $feedbackimg = array();
        $classes = array();
        foreach ($question->get_order($qa) as $value => $ansid) {
            $inputname = $qa->get_qt_field_name($subq->field_name('choice'.$value));
            $ans = $question->answers[$ansid];
            $inputattributes = array();
            $inputattributes['name'] = $inputname;
            $inputattributes['value'] = 1;
            $inputattributes['id'] = $inputname;
            $isselected = $question->is_choice_selected($response, $value);
            if ($isselected) {
                $inputattributes['checked'] = 'checked';
            }
            $hidden = '';
            if (!$options->readonly) {
                $hidden = html_writer::empty_tag('input', array(
                    'type' => 'hidden',
                    'name' => $inputattributes['name'],
                    'value' => 0,
                ));
            }
            $cblabel = $question->make_html_inline($question->format_text(
                                                $ans->answer, $ans->answerformat,
                                                $qa, 'question', 'answer', $ansid));

            $cblabeltag = html_writer::tag('label', $cblabel, array('for' => $inputattributes['id']));

            $checkboxes[] = $hidden . html_writer::empty_tag('input', $inputattributes + $commonattributes) . $cblabeltag;

            $class = 'r' . ($value % 2);
            if ($options->correctness && $isselected) {
                $iscbcorrect = ($ans->fraction > 0) ? 1 : 0;
                $feedbackimg[] = $this->feedback_image($iscbcorrect);
                $class .= ' ' . $this->feedback_class($iscbcorrect);
            } else {
                $feedbackimg[] = '';
            }
            $classes[] = $class;
        }

        $cbhtml = '';

        if ('h' === $subq->get_layout()) {
            $inputwraptag = 'span';
        } else {
            $inputwraptag = 'div';
        }

        foreach ($checkboxes as $key => $checkbox) {
            $cbhtml .= html_writer::tag($inputwraptag, $checkbox . ' ' . $feedbackimg[$key],
                                        array('class' => $classes[$key])) . "\n";
        }

        $result = html_writer::tag($inputwraptag, $cbhtml, array('class' => 'answer'));

        return $result;
    }
}
