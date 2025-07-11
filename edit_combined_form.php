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
 * @package    qtype_combined
 * @copyright  2013 The Open University
 * @author     Jamie Pratt <me@jamiep.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot .'/question/type/combined/combiner/forform.php');

/**
 * Combined question editing form definition.
 *
 * @copyright  2013 The Open University
 * @author     Jamie Pratt <me@jamiep.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_combined_edit_form extends question_edit_form {

    /**
     * @var qtype_combined_combiner_for_form used throughout the form
     */
    protected $combiner;

    /**
     * Constructor.
     * @param string $submiturl The URL to submit the form to.
     * @param question_definition $question The question being edited.
     * @param stdClass|core_course_category $category The category the question belongs to.
     * @param context[] $contexts The contexts the question belongs to.
     * @param bool $formeditable Whether the form is editable.
     */
    public function __construct($submiturl, $question, $category, $contexts, $formeditable = true) {
        $this->combiner = new qtype_combined_combiner_for_form();
        parent::__construct($submiturl, $question, $category, $contexts, $formeditable);
    }

    /**
     * Get the current question text.
     */
    protected function get_current_question_text() {
        if ($submitteddata = optional_param_array('questiontext', null, PARAM_RAW)) {
            return $submitteddata['text'];
        } else if (isset($this->question->id)) {
            return $this->question->questiontext;
        }
    }

    #[\Override]
    protected function definition_inner($mform) {
        if (isset($this->question->id)) {
            $qid = $this->question->id;
        } else {
            $qid = null;
        }
        $this->combiner->form_for_subqs($qid,
                $this->get_current_question_text(),
                $this,
                $mform,
                $this->question->formoptions->repeatelements);

        if ($mform->elementExists('status')) {
            $insertbefore = 'status'; // Moodle 4.x.
        } else {
            $insertbefore = 'defaultmark'; // Moodle 3.x.
        }

        $placeholders = array_map(
                function($placeholder) {
                    return html_writer::empty_tag('input', ['type' => 'text', 'readonly' => 'readonly', 'size' => '26',
                            'value' => $placeholder, 'onfocus' => 'this.select()',
                            'class' => 'form-control-plaintext d-inline-block qtype_combined_placeholder me-3']);
                }, qtype_combined_type_manager::get_example_placeholders());
        $subq = $mform->createElement('static', 'subq', get_string('subquestiontypes', 'qtype_combined'),
                implode("\n", $placeholders));
        $mform->insertElementBefore($subq, $insertbefore);
        $mform->addHelpButton('subq', 'subquestiontypes', 'qtype_combined');

        $verify = $mform->createElement('submit', 'updateform', get_string('updateform', 'qtype_combined'));
        $mform->insertElementBefore($verify, $insertbefore);
        $mform->registerNoSubmitButton('updateform');

        $this->add_combined_feedback_fields(true);

        $this->add_interactive_settings(true, true);
    }

    #[\Override]
    protected function data_preprocessing($toform) {
        $toform = parent::data_preprocessing($toform);
        $toform = $this->data_preprocessing_combined_feedback($toform, true);
        $toform = $this->data_preprocessing_hints($toform, true, true);
        if (!empty($toform->id)) {
            $toform = $this->combiner->data_to_form($toform->id, $toform, $this->context, $this->fileoptions);
        }
        return $toform;
    }

    #[\Override]
    public function validation($fromform, $files) {
        $errors = parent::validation($fromform, $files);

        $errors += $this->combiner->validate_subqs_data_in_form($fromform, $files);

        return $errors;
    }


    #[\Override]
    public function qtype() {
        return 'combined';
    }
}
