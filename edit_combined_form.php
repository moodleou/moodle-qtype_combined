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
 * Defines the editing form for the combined question type.
 *
 * @package    qtype
 * @subpackage combined
 * @copyright  2013 The Open University
 * @author     Jamie Pratt <me@jamiep.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot .'/question/type/combined/combiner.php');

/**
 * Combined question editing form definition.
 *
 * @copyright  2013 The Open University
 * @author     Jamie Pratt <me@jamiep.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_combined_edit_form extends question_edit_form {

    /**
     * @var qtype_combined_combiner used throughout the form
     */
    protected $combiner;

    public function __construct($submiturl, $question, $category, $contexts, $formeditable = true) {
        $this->combiner = new qtype_combined_combiner();
        parent::__construct($submiturl, $question, $category, $contexts, $formeditable);
    }

    protected function definition_inner($mform) {
        $mform->addElement('submit', 'updateform', get_string('updateform', 'qtype_combined'));
        $mform->closeHeaderBefore('updateform');
        // We are using a hook in question type to redisplay the form and it expects a parameter
        // wizard, which we won't actually use but we need to pass it to avoid an error notice.
        $mform->addElement('hidden', 'wizard', '');

        if (isset($this->question->questiontext)) {
            $qt = $this->question->questiontext;
        } else {
            $qt = null;
        }
        $this->combiner->form_for_subqs($qt, $this, $mform, $this->question->formoptions->repeatelements);

        $this->add_interactive_settings();
    }

    protected function data_preprocessing($question) {
        $question = parent::data_preprocessing($question);
        $question = $this->data_preprocessing_hints($question);
        if (empty($question->id)) {
            $defaulttext = $this->combiner->default_question_text();
            if ($question->questiontext['format'] === FORMAT_HTML) {
                $question->questiontext['text'] = format_text($defaulttext, FORMAT_PLAIN);
            } else {
                $question->questiontext['text'] = $defaulttext;
            }
        }
        return $question;
    }

    public function validation($fromform, $files) {
        $errors = parent::validation($fromform, $files);

        $errors += $this->combiner->validate_subqs_data_in_form($fromform, $files);

        return $errors;
    }


    public function qtype() {
        return 'combined';
    }
}
