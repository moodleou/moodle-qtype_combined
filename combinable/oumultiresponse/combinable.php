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
 * Defines the hooks necessary to make the oumultiresponse question type combinable
 *
 * @package    qtype
 * @subpackage combined
 * @copyright  2013 The Open University
 * @author     Jamie Pratt <me@jamiep.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class qtype_combined_combinable_type_oumultiresponse extends qtype_combined_combinable_type_base {

    protected $identifier = 'multiresponse';

    protected function extra_question_properties() {
        return array('answernumbering' => 'abc') + $this->combined_feedback_properties();
    }

    protected function extra_answer_properties() {
        return array('feedback' => array('text' => '', 'format' => FORMAT_PLAIN));
    }
    public function is_empty($subqformdata) {
        foreach ($subqformdata->correctanswer as $value) {
            if (!empty($value)) {
                return false;
            }
        }
        foreach ($subqformdata->answer as $value) {
            if ('' !== trim($value)) {
                return false;
            }
        }
        if (!empty($subqformdata->shuffleanswers)) {
            return false;
        }
        return parent::is_empty($subqformdata);
    }

    protected function transform_subq_form_data_to_full($subqdata) {
        $data = parent::transform_subq_form_data_to_full($subqdata);
        foreach ($data->answer as $anskey => $answer) {
            $data->answer[$anskey] = array('text' => $answer, 'format' => FORMAT_PLAIN);
        }
        return $this->add_per_answer_properties($data);
    }
}

class qtype_combined_combinable_oumultiresponse extends qtype_combined_combinable_accepts_vertical_or_horizontal_layout_param {

    /**
     * @param moodleform      $combinedform
     * @param MoodleQuickForm $mform
     * @param                 $repeatenabled
     */
    public function add_form_fragment(moodleform $combinedform, MoodleQuickForm $mform, $repeatenabled) {
        $mform->addElement('advcheckbox', $this->field_name('shuffleanswers'), get_string('shuffle', 'qtype_gapselect'));

        $answerels = array();
        $answerels[] = $mform->createElement('text',
                            $this->field_name('answer'),
                            get_string('choicex', 'qtype_gapselect'),
                            array('size'=>30, 'class'=>'tweakcss'));
        $mform->setType($this->field_name('answer'), PARAM_TEXT);
        $answerels[] = $mform->createElement('advcheckbox',
                                             $this->field_name('correctanswer'),
                                             get_string('correct', 'qtype_combined'),
                                             get_string('correct', 'qtype_combined'));

        $answergroupel = $mform->createElement('group',
                                               $this->field_name('answergroup'),
                                               get_string('choiceno', 'qtype_multichoice', '{no}'),
                                               $answerels,
                                               null,
                                               false);
        /* TODO need some way to check no of choices in db
        if (isset($this->question->options)) {
            $countanswers = count($this->question->options->answers);
        } else {
            $countanswers = 0;
        }*/
        $countanswers = 1;


        if ($repeatenabled) {
            $defaultstartnumbers = QUESTION_NUMANS_START * 2;
            $repeatsatstart = max($defaultstartnumbers, QUESTION_NUMANS_START, $countanswers + QUESTION_NUMANS_ADD);
        } else {
            $repeatsatstart = $countanswers;
        }

        $combinedform->repeat_elements(array($answergroupel),
            $repeatsatstart,
            array(),
            $this->field_name('noofchoices'),
            $this->field_name('morechoices'),
            QUESTION_NUMANS_ADD,
            get_string('addmorechoiceblanks', 'qtype_gapselect'),
            true);

    }

/*    public function data_to_form() {

    }*/

    public function validate() {
        $errors = array();
        $nonemptyanswerblanks = array();
        foreach ($this->formdata->answer as $anskey => $answer) {
            if ('' !== trim($answer)) {
                $nonemptyanswerblanks[] = $anskey;
            } else if ($this->formdata->correctanswer[$anskey]) {
                $errors[$this->field_name("answergroup[{$anskey}]")] = get_string('err_correctanswerblank', 'qtype_combined');
            }
        }
        if (count($nonemptyanswerblanks) < 2) {
            $errors[$this->field_name("answergroup[0]")] = get_string('err_youneedmorechoices', 'qtype_combined');
        }
        if (count(array_filter($this->formdata->correctanswer)) === 0) {
            $errors[$this->field_name("answergroup[0]")] = get_string('err_nonecorrect', 'qtype_combined');
        }
        return $errors;
    }

}