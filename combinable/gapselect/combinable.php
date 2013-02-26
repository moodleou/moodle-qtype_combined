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
 * Defines the hooks necessary to make the gapselect question type combinable
 *
 * @package    qtype
 * @subpackage combined
 * @copyright  2013 The Open University
 * @author     Jamie Pratt <me@jamiep.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class qtype_combined_combinable_type_gapselect extends qtype_combined_combinable_type_base {

    protected $identifier = 'selectmenu';

}

class qtype_combined_combinable_gapselect extends qtype_combined_combinable_accepts_numerical_param {


    /**
     * @param moodleform      $combinedform
     * @param MoodleQuickForm $mform
     * @param                 $repeatenabled
     */
    public function add_form_fragment(moodleform $combinedform, MoodleQuickForm $mform, $repeatenabled) {
        $mform->addElement('advcheckbox', $this->field_name('shuffleanswers'), get_string('shuffle', 'qtype_gapselect'));

        $answerel = array($mform->createElement('text',
                                                $this->field_name('answer'),
                                                get_string('choicex', 'qtype_gapselect'),
                                                array('size'=>30, 'class'=>'tweakcss')));

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

        $combinedform->repeat_elements($answerel,
                                        $repeatsatstart,
                                        array(),
                                        $this->field_name('noofchoices'),
                                        $this->field_name('morechoices'),
                                        QUESTION_NUMANS_ADD,
                                        get_string('addmorechoiceblanks', 'qtype_gapselect'),
                                        true);

    }

    /**
     * @return mixed
     */
    public function set_form_data() {

    }

    public function validate_third_param($thirdparam) {
        if ($thirdparam === null) {
            $qtype = static::qtype_identifier_in_question_text();
            return get_string('err_you_must_provide_third_param', 'qtype_combined', $qtype);
        } else {
            return parent::validate_third_param($thirdparam);
        }
    }

}