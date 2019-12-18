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
 * Defines the hooks necessary to make the multichoice question type combinable
 *
 * @package   qtype_combined
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/question/type/combined/combiner/base.php');
require_once($CFG->dirroot . '/question/type/multichoice/questiontype.php');


class qtype_combined_combinable_type_multichoice extends qtype_combined_combinable_type_base {

    protected $identifier = 'singlechoice';

    protected function extra_question_properties() {
        $properties = $this->combined_feedback_properties();
        $properties['single'] = 1;
        $properties['answernumbering'] = 'none';
        return $properties;
    }

    protected function extra_answer_properties() {
        return array();
    }

    public function subq_form_fragment_question_option_fields() {
        return array(
            'shuffleanswers' => false,
        );
    }

    protected function transform_subq_form_data_to_full($subqdata) {
        $data = parent::transform_subq_form_data_to_full($subqdata);
        foreach ($data->answer as $anskey => $answer) {
            $data->answer[$anskey] = array('text' => $answer['text'], 'format' => $answer['format']);
        }
        foreach ($data->feedback as $anskey => $feedback) {
            $data->feedback[$anskey] = array('text' => $feedback['text'], 'format' => $feedback['format']);
        }
        return $this->add_per_answer_properties($data);
    }

    protected function third_param_for_default_question_text() {
        return 'v';
    }
}

class qtype_combined_combinable_multichoice extends qtype_combined_combinable_accepts_vertical_or_horizontal_layout_param {

    /**
     * @param moodleform      $combinedform
     * @param MoodleQuickForm $mform
     * @param                 $repeatenabled
     */
    public function add_form_fragment(moodleform $combinedform, MoodleQuickForm $mform, $repeatenabled) {
        $mform->addElement('advcheckbox', $this->form_field_name('shuffleanswers'),
            get_string('shuffle', 'qtype_combined'));
        $mform->setDefault('shuffleanswers', get_config('qtype_multichoice', 'shuffleanswers'));

        $answerels = array();

        // Answer text.
        $answerels[] = $mform->createElement('editor', $this->form_field_name('answer'),
            get_string('choiceno', 'qtype_multichoice', '{no}'), ['rows' => 1]);
        $mform->setType($this->form_field_name('answer'), PARAM_RAW);

        // Answer grade.
        $answerels[] = $mform->createElement('select', $this->form_field_name('fraction'),
            get_string('grade'), question_bank::fraction_options_full());
        $mform->setDefault($this->form_field_name('fraction'), 0);

        // Answer feedback.
        $answerels[] = $mform->createElement('editor', $this->form_field_name('feedback'),
            get_string('feedback', 'qtype_multichoice'), ['rows' => 1]);
        $mform->setType($this->form_field_name('feedback'), PARAM_RAW);

        if (isset($this->questionrec->options)) {
            $repeatsatstart = count($this->questionrec->options->answers);
        } else {
            $repeatsatstart = max(5, QUESTION_NUMANS_START);
        }

        $combinedform->repeat_elements($answerels,
            $repeatsatstart,
            array(),
            $this->form_field_name('noofchoices'),
            $this->form_field_name('morechoices'),
            QUESTION_NUMANS_ADD,
            get_string('addmorechoiceblanks', 'question'),
            true);
    }

    public function data_to_form($context, $fileoptions) {
        $mcoptions = array('answer' => [], 'fraction' => [], 'feedback' => []);
        if ($this->questionrec !== null) {
            $mcoptions['single'] = $this->questionrec->options->single;
            foreach ($this->questionrec->options->answers as $answer) {
                $mcoptions['answer'][] = [
                    'text' => $answer->answer,
                    'format' => $answer->answerformat,
                ];
                $mcoptions['fraction'][] = $answer->fraction;
                $mcoptions['feedback'][] = [
                    'text' => $answer->feedback,
                    'format' => $answer->feedbackformat,
                ];
            }
        }
        return parent::data_to_form($context, $fileoptions) + $mcoptions;
    }

    public function validate() {
        $errors = array();
        $answercount = 0;

        $maxfraction = -1;

        foreach ($this->formdata->answer as $key => $answer) {
            // Check no of choices.
            $trimmedanswer = trim($answer['text']);
            $fraction = (float) $this->formdata->fraction[$key];
            if ($trimmedanswer === '' && empty($fraction)) {
                continue;
            }
            if ($trimmedanswer === '' && $fraction > 0) {
                $errors[$this->form_field_name("answer[{$key}]")] = get_string('errgradesetanswerblank', 'qtype_multichoice');
            }

            $answercount++;

            // Check grades.
            if ($this->formdata->fraction[$key] > $maxfraction) {
                $maxfraction = $this->formdata->fraction[$key];
            }
        }
        if ($answercount == 0) {
            $errors['answer[0]'] = get_string('notenoughanswers', 'qtype_multichoice', 2);
            $errors['answer[1]'] = get_string('notenoughanswers', 'qtype_multichoice', 2);
        } else if ($answercount == 1) {
            $errors['answer[1]'] = get_string('notenoughanswers', 'qtype_multichoice', 2);
        }

        if ($maxfraction != 1) {
            $errors[$this->form_field_name('fraction[0]')] =
                get_string('errfractionsnomax', 'qtype_multichoice', $maxfraction * 100);
        }
        return $errors;
    }

    public function has_submitted_data() {
        return $this->submitted_data_array_not_empty('answer') ||
                $this->submitted_data_array_not_empty('shuffleanswers') ||
                $this->html_field_has_submitted_data($this->form_field_name('answer')) ||
            parent::has_submitted_data();
    }
}
