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
 * Code that deals with constructing, loading data into form and validating question editing form for sub questions.
 *
 * @package    qtype_combined
 * @copyright  2013 The Open University
 * @author     James Pratt <me@jamiep.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/question/type/combined/combiner/base.php');

/**
 * Class qtype_combined_combiner_for_form
 */
class qtype_combined_combiner_for_form extends qtype_combined_combiner_base {

    /**
     * Construct the part of the form for the user to fill in the details for each subq.
     * This method must determine which subqs should appear in the form based on the user submitted question text and also what
     * items have previously been in the form. We don't want to lose any data submitted without a warning
     * when the user removes a subq from the question text.
     * @param integer         $questionid
     * @param string          $questiontext
     * @param moodleform      $combinedform
     * @param MoodleQuickForm $mform
     * @param boolean         $repeatenabled
     */
    public function form_for_subqs($questionid, $questiontext, moodleform $combinedform, MoodleQuickForm $mform, $repeatenabled) {
        $this->find_included_subqs_in_question_text($questiontext);

        $this->find_subqs_in_submitted_data();

        $this->load_subq_data_from_db($questionid, true);

        foreach ($this->subqs as $i => $subq) {
            if (!$subq->is_in_question_text() && !$subq->preserve_submitted_data()) {
                if ($subq->is_in_db()) {
                    $subq->delete();
                }
                unset($this->subqs[$i]);
            }
        }
        foreach ($this->subqs as $subq) {

            $weightingdefault = round(1/count($this->subqs), 7);
            $weightingdefault = "$weightingdefault";

            $a = new stdClass();
            $qtypeid = $a->qtype = $subq->type->get_identifier();
            $qid = $a->qid = $subq->get_identifier();

            if ($subq->is_in_question_text()) {
                $headerlegend = get_string('subqheader', 'qtype_combined', $a);
            } else {
                $headerlegend = get_string('subqheader_not_in_question_text', 'qtype_combined', $a);
                $headerlegend ='<span class="not_in_question_text">'.$headerlegend.'</span>';
            }

            $mform->addElement('header', $subq->form_field_name('subqheader'), $headerlegend);

            if (!$subq->is_in_question_text()) {
                $mform->addElement('hidden', $subq->form_field_name('notincludedinquestiontextwilldelete'), true);
                $mform->setType($subq->form_field_name('notincludedinquestiontextwilldelete'), PARAM_BOOL);
            }

            $gradeoptions = question_bank::fraction_options();
            $mform->addElement('select', $subq->form_field_name('defaultmark'), get_string('weighting', 'qtype_combined'),
                               $gradeoptions);
            $mform->setDefault($subq->form_field_name('defaultmark'), $weightingdefault);
            $subq->add_form_fragment($combinedform, $mform, $repeatenabled);
            $mform->addElement('editor', $subq->form_field_name('generalfeedback'),
                               get_string('incorrectfeedback', 'qtype_combined'),
                               array('rows' => 5), $combinedform->editoroptions);
            $mform->setType($subq->form_field_name('generalfeedback'), PARAM_RAW);
            // Array key is ignored but we need to make sure that submitted values do not override new element values, so we want
            // the key to be unique for every subq in a question.
            $mform->addElement('hidden', "subqfragment_id[{$qtypeid}_{$qid}]", $qid);
            $mform->addElement('hidden', "subqfragment_type[{$qtypeid}_{$qid}]", $qtypeid);
        }
        $mform->setType("subqfragment_id", PARAM_ALPHANUM);
        $mform->setType("subqfragment_type", PARAM_ALPHANUMEXT);
    }

    /**
     * Validate both the embedded codes in question text and the data from subq form fragments.
     * @param $fromform array data from form
     * @param $files array not used for now no subq type requires this.
     * @return array of errors to display in form or empty array if no errors.
     */
    public function validate_subqs_data_in_form($fromform, $files) {
        $errors = $this->validate_question_text($fromform['questiontext']['text']);
        $errors += $this->validate_subqs($fromform);
        return $errors;
    }

    /**
     * Validate embedded codes in question text.
     * @param string $questiontext
     * @return array of errors or empty array if there are no errors.
     */
    protected function validate_question_text($questiontext) {
        $questiontexterror = $this->find_included_subqs_in_question_text($questiontext);
        if ($questiontexterror !== null) {
            $errors = array('questiontext' => $questiontexterror);
        } else {
            $errors = array();
        }
        return $errors;
    }

    /**
     * Validate the subq form fragments data.
     * @param $fromform array all data from form
     * @return array of errors from subq form elements or empty array if no errors.
     */
    protected function validate_subqs($fromform) {
        $this->get_subq_data_from_form_data((object)$fromform);
        $errors = array();
        $fractionsum = 0;

        foreach ($this->subqs as $subq) {
            if ($subq->is_in_form() && $subq->has_submitted_data()) {
                $errors += $subq->validate();
            } else {
                $errors += array($subq->form_field_name('defaultmark') => get_string('err_fillinthedetailshere', 'qtype_combined'));
            }
            if ($subq->is_in_question_text()) {
                $defaultmarkfieldname = $subq->form_field_name('defaultmark');
                $fractionsum += $fromform[$defaultmarkfieldname];
            } else {
                $message = $subq->message_in_form_if_not_included_in_question_text();
                $message = '<span class="not_in_question_text_message">'.$message.'</span>';
                $qtmessage = get_string('embeddedquestionremovedfromform', 'qtype_combined');
                $qtmessage = '<span class="not_in_question_text_message">'.$qtmessage.'</span>';

                $errors += array($subq->form_field_name('defaultmark') => $message,
                                 'questiontext' => $qtmessage);
            }
        }
        if (abs($fractionsum - 1) > 0.00001) {
            foreach ($this->subqs as $subq) {
                if ($subq->is_in_form()) {
                    $errors += array($subq->form_field_name('defaultmark') =>
                                     get_string('err_weightingsdonotaddup', 'qtype_combined'));
                }
            }
        }

        return $errors;
    }

    /**
     * Get data from db for subqs and transform it into data to populate form fields for subqs.
     * @param $questionid
     * @param $toform stdClass data for other parts of form to add the subq data to.
     * @param $context context question context
     * @param $fileoptions stdClass file options for form
     * @return stdClass data for form with subq data added
     */
    public function data_to_form($questionid, $toform, $context, $fileoptions) {
        $this->load_subq_data_from_db($questionid, true);
        foreach ($this->subqs as $subq) {
            $fromsubqtoform = $subq->data_to_form($context, $fileoptions);
            foreach ($fromsubqtoform as $property => $value) {
                $fieldname = $subq->form_field_name($property);
                $toform->{$fieldname} = $value;
            }
        }
        return $toform;
    }

    /**
     * Adds subq objects for anything in submitted form data that is not in question text.
     */
    public function find_subqs_in_submitted_data() {
        $ids = optional_param_array('subqfragment_id', array(), PARAM_ALPHANUM);
        $qtypeids = optional_param_array('subqfragment_type', array(), PARAM_ALPHANUM);
        foreach ($ids as $subqkey => $id) {
            $this->find_or_create_question_instance($qtypeids[$subqkey], $id);
        }
    }
}
