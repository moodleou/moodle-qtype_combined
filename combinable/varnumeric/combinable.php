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
 * Defines the hooks necessary to make the numerical question type combinable
 *
 * @package    qtype
 * @subpackage combined
 * @copyright  2013 The Open University
 * @author     Jamie Pratt <me@jamiep.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/question/type/varnumericset/number_interpreter.php');

class qtype_combined_combinable_type_varnumeric extends qtype_combined_combinable_type_base {

    protected $identifier = 'numeric';

    protected function extra_question_properties() {
        return array('randomseed' => '', 'vartype' => array(0), 'varname' => array(''), 'variant' => array(''), 'novars' => 1);
    }

    protected function extra_answer_properties() {
        return array('sigfigs' => 0, 'fraction' => '1.0', 'feedback'  => array('text' => '', 'format' => FORMAT_PLAIN),
                        'checknumerical' => 0, 'checkscinotation' => 0, 'checkpowerof10' => 0, 'checkrounding' => 0,
                        'syserrorpenalty' => '0.0');
    }

    public function subq_form_fragment_question_option_fields() {
        return array('requirescinotation' => null);
    }

    protected function third_param_for_default_question_text() {
        return '__10__';
    }

}

class qtype_combined_combinable_varnumeric extends qtype_combined_combinable_text_entry {

    public function add_form_fragment(moodleform $combinedform, MoodleQuickForm $mform, $repeatenabled) {

        $answergroupels = array();
        $answergroupels[] = $mform->createElement('text', $this->form_field_name('answer[0]'),
                                                  get_string('answer', 'question'), array('size' => 25));
        $answergroupels[] = $mform->createElement('text',
                                                 $this->form_field_name('error[0]'),
                                                 get_string('error', 'qtype_varnumericset'),
                                                 array('size' => 16));
        $mform->setType($this->form_field_name('answer'), PARAM_TEXT);
        $mform->setType($this->form_field_name('error'), PARAM_TEXT);
        $mform->addElement('group',
                           $this->form_field_name('answergroup'),
                           get_string('answer', 'question'),
                           $answergroupels,
                           '',
                           false);
        $mform->addElement('selectyesno', $this->form_field_name('requirescinotation'),
                           get_string('scinotation', 'qtype_combined'));
    }

    public function data_to_form($context, $fileoptions) {
        $numericoptions = array('answer' => array(), 'error' => array());

        if ($this->questionrec !== null) {
            foreach ($this->questionrec->options->answers as $answer) {
                $numericoptions['answer'][] = $answer->answer;
                $numericoptions['error'][] = $answer->error;
            }
        }
        return parent::data_to_form($context, $fileoptions) + $numericoptions;
    }

    public function validate() {
        $errors = array();
        $interpret = new qtype_varnumericset_number_interpreter_number_with_optional_sci_notation(false);
        if ('' !== trim($this->formdata->error[0])) {
            if (!$interpret->match($this->formdata->error[0])) {
                $errors[$this->form_field_name('answergroup')] = get_string('err_notavalidnumberinerrortolerance', 'qtype_combined');
            }
        }
        if (!$interpret->match($this->formdata->answer[0])) {
            $errors[$this->form_field_name('answergroup')] = get_string('err_notavalidnumberinanswer', 'qtype_combined');
        }

        return $errors;
    }

    public function get_sup_sub_editor_option() {
        if ($this->question->usesupeditor) {
            return 'sup';
        } else {
            return null;
        }
    }

    public function has_submitted_data() {
        return $this->submitted_data_array_not_empty('answer') ||
                $this->submitted_data_array_not_empty('error') ||
                parent::has_submitted_data();
    }

    public function make() {
        parent::make();
        // Require scientific notation is always off for this question type even when the sup sub editor is on.
        $this->question->requirescinotation = false;
    }
}