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

class qtype_combined_combinable_type_varnumeric extends qtype_combined_combinable_type_base {

    protected $identifier = 'numeric';

}

class qtype_combined_combinable_varnumeric extends qtype_combined_combinable_accepts_width_specifier {

    /**
     * string used to identify this question type, in question type syntax after first colon
     * @throws coding_exception
     */
    static public function qtype_identifier_in_question_text() {
        return 'numeric';
    }

    /**
     * @return mixed
     */
    public function add_form_fragment(moodleform $combinedform, MoodleQuickForm $mform, $repeatenabled) {

        $answergroupels = array();
        $answergroupels[] = $mform->createElement('text',
                                                 $this->field_name('answer'),
                                                 get_string('answer', 'question'),
                                                 array('size' => 25));
        $answergroupels[] = $mform->createElement('text',
                                                 $this->field_name('error'),
                                                 get_string('error', 'qtype_varnumericset'),
                                                 array('size' => 25));
        $mform->addGroup($answergroupels,
                         $this->field_name('answergroup'),
                         get_string('answer', 'question'),
                         '&nbsp;'.get_string('error', 'qtype_varnumericset'),
                         true);
        $mform->addElement('selectyesno', $this->field_name('scinotation'),
                           get_string('scinotation', 'qtype_combined'));
    }

    /**
     * @return mixed
     */
    public function set_form_data() {

    }


}