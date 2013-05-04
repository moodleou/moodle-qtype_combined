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
 * Code that deals with finding and loading code from subqs.
 *
 * @package    qtype
 * @subpackage combined
 * @copyright  2013 The Open University
 * @author     James Pratt <me@jamiep.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/question/type/combined/combinable/combinablebase.php');

/**
 * Class qtype_combined_combiner_base
 * This is a base class which holds common code used by combiner classes that are used to produce forms,
 * save and produce run time questions.
 * An instance of this class stores everything to do with the sub questions for one combined question.
 *
 */
abstract class qtype_combined_combiner_base {

    /**
     * @var qtype_combined_combinable_base[] array of sub questions, in question text, in form and in db. One instance for each
     *                                          question instance.
     */
    protected $subqs = array();

    /**
     * EMBEDDED_CODE_* and VALID_QUESTION_* defines the syntax for embedded subqs in question text.
     */
    const EMBEDDED_CODE_PREFIX = '[[';

    const EMBEDDED_CODE_POSTFIX = ']]';

    const EMBEDDED_CODE_SEPARATOR = ':';

    /** Question identifier must be one or more alphanumeric characters. */
    const VALID_QUESTION_IDENTIFIER_PATTTERN = '[a-zA-Z0-9]+';

    /**
     * Prefix both for field names in sub question form fragments and also for collecting student responses in run-time question.
     */
    const FIELD_NAME_PREFIX = 'subq:{qtype}:{qid}:';


    /**
     * Creates array of subq objects from the embedded codes in the question text.
     * @param $questiontext string the question text
     * @return null|string either null if no error or an error message.
     */
    public function find_included_subqs_in_question_text($questiontext) {
        $this->subqs = array();
        $pattern = '!'.
                    preg_quote(static::EMBEDDED_CODE_PREFIX, '!') .
                    '(.*?)'.
                    preg_quote(static::EMBEDDED_CODE_POSTFIX, '!')
                    .'!';

        $matches = array();
        if (0 === preg_match_all($pattern, $questiontext, $matches)) {
            return  get_string('noembeddedquestions', 'qtype_combined');
        }

        $controlno = 1;
        foreach ($matches[1] as $codeinsideprepostfix) {
            $error = $this->make_combinable_instance_from_code_in_question_text($codeinsideprepostfix, $controlno);
            $controlno++;
            if ($error !== null) {
                return $error;
            }
        }
        $duplicatedids = $this->find_duplicate_question_identifiers();
        if (count($duplicatedids) !== 0) {
            return get_string('err_duplicateids', 'qtype_combined', join(',', $duplicatedids));
        }
        return null;
    }

    /**
     * @return string[] array of duplicate ids.
     */
    protected function find_duplicate_question_identifiers() {
        $listofsubqids = array();
        $duplicateids = array();
        foreach ($this->subqs as $subq) {
            $subqidentifier = $subq->get_identifier();
            if (false !== array_search($subqidentifier, $listofsubqids, true)) {
                $duplicateids[] = $subqidentifier;
            }
            $listofsubqids[] = $subqidentifier;
        }
        return $duplicateids;
    }

    /**
     * Create or just pass through the third embedded code param to each subq from question text.
     * @param $codeinsideprepostfix string The embedded code minus the enclosing brackets.
     * @param $controlno integer the control no, each subq can be responsible for more than one control in the question text.
     * @return string|null first error encountered or null if no error.
     */
    protected function make_combinable_instance_from_code_in_question_text($codeinsideprepostfix, $controlno) {
        list($questionidentifier, $qtypeidentifier, $thirdparam) =
                                                    $this->decode_code_in_question_text($codeinsideprepostfix);
        $getstringhash = new stdClass();
        $getstringhash->fullcode = static::EMBEDDED_CODE_PREFIX.$codeinsideprepostfix.static::EMBEDDED_CODE_POSTFIX;
        $getstringhash->qtype = $qtypeidentifier;
        $getstringhash->qid = $questionidentifier;
        if ($questionidentifier === null || $qtypeidentifier === null || $qtypeidentifier === '') {
            return get_string('err_insufficientnoofcodeparts', 'qtype_combined', $getstringhash);
        }
        $qidpattern = '!'. self::VALID_QUESTION_IDENTIFIER_PATTTERN . '$!A';
        if (1 !== preg_match($qidpattern, $questionidentifier)) {
            return get_string('err_invalidquestionidentifier', 'qtype_combined', $getstringhash);
        }
        if (strlen($questionidentifier) > 10) {
            return get_string('err_questionidentifiertoolong', 'qtype_combined', $questionidentifier);
        }
        if (!qtype_combined_type_manager::is_identifier_known($qtypeidentifier)) {
            return get_string('err_unrecognisedqtype', 'qtype_combined', $getstringhash);
        }

        $subq = $this->find_or_create_question_instance($qtypeidentifier, $questionidentifier);

        return $subq->found_in_question_text($thirdparam, $controlno);
    }

    /**
     * Access the array of subqs using the qtype and question identifier (identifiers as in question text).
     * @param $qtypeidentifier the identifier as used in the question text ie. not the internal Moodle question type name.
     * @param $questionidentifier the question identifier that is the first param of the embedded code.
     * @return null|qtype_combined_combinable_base null if not found or the existing subq if there is one that matches.
     */
    protected function find_question_instance($qtypeidentifier, $questionidentifier) {
        foreach ($this->subqs as $subq) {
            if ($subq->get_identifier() == $questionidentifier && $subq->type->get_identifier() == $qtypeidentifier) {
                return $subq;
            }
        }
        return null;
    }

    /**
     * Same as @see find_question_instance but creates subq instance if it does not exist.
     * @param $qtypeidentifier
     * @param $questionidentifier
     * @return qtype_combined_combinable_base
     */
    public function find_or_create_question_instance($qtypeidentifier, $questionidentifier) {
        $existing = $this->find_question_instance($qtypeidentifier, $questionidentifier);
        if ($existing !== null) {
            return $existing;
        } else {
            $new = qtype_combined_type_manager::new_subq_instance($qtypeidentifier, $questionidentifier);
            $this->subqs[] = $new;
            return $new;
        }
    }

    /**
     * Break down code in question text into three params, with null meaning no param.
     * @param $codeinsideprepostfix code taken from inside square brackets.
     * @return array three params.
     */
    protected function decode_code_in_question_text($codeinsideprepostfix) {
        $codeparts = explode(static::EMBEDDED_CODE_SEPARATOR, $codeinsideprepostfix, 3);
        // Replace any missing parts with null before return.
        $codeparts = $codeparts + array(null, null, null);
        return $codeparts;
    }

    /**
     * Used for question subq validation and saving. Run through question data and find or create the subq object
     * and pass through the form data to be stored in the subq object.
     * @param $questiondata stdClass submitted question data
     */
    protected function get_subq_data_from_form_data($questiondata) {
        foreach ($questiondata->subqfragment_id as $subqkey => $qid) {
            $subq = $this->find_or_create_question_instance($questiondata->subqfragment_type[$subqkey], $qid);
            $subq->get_this_form_data_from($questiondata);
        }
    }

    /**
     * @param integer  $questionid The question id
     * @param bool     $getoptions Whether to also fetch the question options for each subq.
     */
    public function load_subq_data_from_db($questionid, $getoptions = false) {
        $subquestions = static::get_subq_data_from_db($questionid, $getoptions);
        $this->create_subqs_from_subq_data($subquestions);
    }

    /**
     * The db operation to fetch all sub-question data from the db. For run time question instances this is run before
     * question instance data caching as it seems more straight forward to have Moodle MUC cache stdClass rather than other
     * classes.
     * @param integer  $questionid The question id
     * @param bool     $getoptions Whether to also fetch the question options for each subq.
     * @return stdClass[]
     */
    public static function get_subq_data_from_db($questionid, $getoptions = false) {
        global $DB;
        $sql = 'SELECT q.*, qc.contextid FROM {question} q '.
            'JOIN {question_categories} qc ON q.category = qc.id ' .
            'WHERE q.parent = $1';

        // Load the questions.
        if (!$subqrecs = $DB->get_records_sql($sql, array($questionid))) {
            return array();
        }
        if ($getoptions) {
            get_question_options($subqrecs);
        }
        return $subqrecs;
    }

    /**
     * @param $subquestions
     */
    public function create_subqs_from_subq_data($subquestions) {
        foreach ($subquestions as $subquestion) {
            $qtypeid = qtype_combined_type_manager::translate_qtype_to_qtype_identifier($subquestion->qtype);
            $subq = $this->find_or_create_question_instance($qtypeid, $subquestion->name);
            $subq->found_in_db($subquestion);
        }
    }

}

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
            $mform->addElement('editor', $subq->form_field_name('generalfeedback'), get_string('incorrectfeedback', 'qtype_combined'),
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

/**
 * Class qtype_combined_combiner_for_saving_subqs
 */
class qtype_combined_combiner_for_saving_subqs extends qtype_combined_combiner_base {

    /**
     * Save subq data. Default values are added to the values from the form and then the data is passed through to the
     * save_question method for that question_type.
     * @param $fromform stdClass Data from form
     * @param $contextid integer question context id
     */
    public function save_subqs($fromform, $contextid) {
        $this->find_included_subqs_in_question_text($fromform->questiontext);
        $this->load_subq_data_from_db($fromform->id);
        $this->get_subq_data_from_form_data($fromform);
        foreach ($this->subqs as $subq) {
            $subq->save($contextid);
        }
    }


}

/**
 * Class qtype_combined_combiner_for_run_time_question_instance
 */
class qtype_combined_combiner_for_run_time_question_instance extends qtype_combined_combiner_base {

    /**
     * Instantiate question_definition classes for all subqs.
     */
    public function make_subqs() {
        foreach ($this->subqs as $subq) {
            $subq->make();
        }
    }

    /**
     * @param string                   $questiontext question text with embed codes to replace
     * @param question_attempt         $qa
     * @param question_display_options $options
     * @return string                  question text with embed codes replaced
     */
    public function render_subqs($questiontext, question_attempt $qa, question_display_options $options) {
        foreach ($this->subqs as $subq) {
            $embedcodes = $subq->question_text_embed_codes();
            foreach ($embedcodes as $placeno => $embedcode) {
                $renderedembeddedquestion = $subq->type->embedded_renderer()->subquestion($qa, $options, $subq, $placeno);
                $questiontext = str_replace($embedcode, $renderedembeddedquestion, $questiontext);
            }
        }
        return $questiontext;
    }

    /**
     * Take a response array from a subq and add prefixes.
     * @param question_attempt_step_subquestion_adapter|null $substep
     * @param array $response
     * @return array
     */
    protected function add_prefixes_to_response_array($substep, $response) {
        $keysadded= array();
        foreach ($response as $key => $value) {
            $keysadded[$substep->add_prefix($key)] = $value;
        }
        return $keysadded;
    }

    /**
     * Call a method on question_definition object for all sub questions.
     * @param string $methodname
     * @param qtype_combined_param_to_pass_through_to_subq_base|mixed  $params,.... a variable number of arguments (or none)
     * @return array of return values returned from method call on all subqs.
     */
    public function call_all_subqs($methodname/*, ... */) {
        $returned = array();
        $args = func_get_args();

        foreach ($this->subqs as $i => $unused) {
            // Call $this->call_subq($i, then same arguments as used to call this method).
            $returned[$i] = call_user_func_array(array($this, 'call_subq'), array_merge(array($i), $args));
        }
        return $returned;
    }

    /**
     * Call a method on question_definition object for all sub questions.
     * @param integer $i the index no of the sub question
     * @param string $methodname
     * @param qtype_combined_param_to_pass_through_to_subq_base|mixed  $params,....
     * @return array of return values returned from method call on all subqs.
     */
    public function call_subq($i, $methodname/*, ... */) {
        $subq = $this->subqs[$i];
        $paramsarray = array_slice(func_get_args(), 2);
        $paramsarrayfiltered = array();
        foreach ($paramsarray as $paramno => $param) {
            if (is_a($param, 'qtype_combined_param_to_pass_through_to_subq_base')) {
                $paramsarrayfiltered[$paramno] = $param->for_subq($subq);
            } else {
                $paramsarrayfiltered[$paramno] = $param;
            }
        }
        return call_user_func_array(array($subq->question, $methodname), $paramsarrayfiltered);
    }

    /**
     * @param $i
     * @param $propertyname
     * @return mixed
     */
    public function get_subq_property($i, $propertyname) {
        return $this->subqs[$i]->question->{$propertyname};
    }

    /**
     * @param array $arrays array of response arrays returned from method subq_method_calls.
     * @param null|question_attempt_step $step
     * @return array aggregated array with prefixes added to each subqs response array keys.
     */
    public function aggregate_response_arrays($arrays, $step = null) {
        $aggregated = array();
        foreach ($arrays as $i => $array) {
            $substep = $this->subqs[$i]->get_substep($step);
            $aggregated += $this->add_prefixes_to_response_array($substep, $array);
        }
        return $aggregated;
    }

    /**
     * @param $responses
     * @param $totaltries
     * @return number
     */
    public function compute_final_grade($responses, $totaltries) {
        $allresponses = new qtype_combined_array_of_response_arrays_param($responses);
        foreach ($this->subqs as $subqno => $subq) {
            $subqresponses = $allresponses->for_subq($subq);
            if (is_a($subq->question, 'question_automatically_gradable_with_countback')) {
                // Question may still need some help to get grading right.
                // Look at final response and see if that response has been given before.
                // If it has, grade that response given before and ignore all responses after.
                $responsestograde =
                    $this->responses_upto_first_response_identical_to_final_response($subq->question, $subqresponses);
                $subqfinalgrade = $subq->question->compute_final_grade($responsestograde, $totaltries);
            } else {
                // No compute final grade method for this question type.
                $subqfinalgrade = $this->compute_subq_final_grade($subq, $subqresponses);
            }
            // Weight grade by subq weighting stored in default mark.
            $finalgrades[$subqno] = $subqfinalgrade * $subq->question->defaultmark;
        }
        return array_sum($finalgrades);
    }

    /**
     * Used for computing final grade for sub question. Find first identical response to final response for a question and remove
     * all responses  after that response.
     * @param $question question_automatically_gradable
     * @param $subqresponses
     * @return array all responses up to the first response that matches the final one.
     */
    protected function responses_upto_first_response_identical_to_final_response($question, $subqresponses) {
        $finalresponse = end($subqresponses);
        foreach (array_values($subqresponses) as $responseno => $subqresponse) {
            if ($question->is_same_response($subqresponse, $finalresponse)) {
                return array_slice($subqresponses, 0, $responseno+1);
            }
        }
        return $subqresponses;
    }

    /**
     * If the subq is not a question_automatically_gradable_with_countback then we need to implement the count back grading
     * for the subq.
     * @param $subq qtype_combined_combinable_base
     * @param $subqresponses array
     * @return number fraction between 0 and 1.
     */
    public function compute_subq_final_grade($subq, $subqresponses) {
        $subqlastresponse = array_pop($subqresponses);
        $penalty = count($subqresponses) * $subq->question->penalty;
        foreach ($subqresponses as $subqresponseno => $subqresponse) {
            if ($subq->question->is_same_response($subqresponse, $subqlastresponse)) {
                $penalty = $subqresponseno * $subq->question->penalty;
                break;
            }
        }
        list($finalresponsegrade, ) = $subq->question->grade_response($subqlastresponse);
        return max(0, $finalresponsegrade * (1 - $penalty));
    }

    /**
     * @param $id integer
     * @return null|qtype_combined_combinable_base
     */
    public function find_subq_with_id($id) {
        foreach ($this->subqs as $subq) {
            if ($subq->question->id === $id) {
                return $subq;
            }
        }
        return null;
    }


    /**
     * @param $response qtype_combined_response_array_param
     * @return string errors
     */
    public function get_validation_error($response) {
        $errors = array();
        foreach ($this->subqs as $subqno => $subq) {
            if (!$this->call_subq($subqno, 'is_complete_response', $response)) {
                $questionerror = $this->call_subq($subqno, 'get_validation_error', $response);
                $controlnos = $subq->get_control_nos();
                $a = new stdClass();
                $a->error = $questionerror;
                if (count($controlnos) > 1) {
                    $a->controlname = $subq->type->get_control_name(true);
                    $a->controlnos = join(', ', $controlnos);
                    $errors[] = get_string('validationerror_multiplecontrols', 'qtype_combined', $a);
                } else {
                    $a->controlname = $subq->type->get_control_name(false);
                    $a->controlno = array_pop($controlnos);
                    $errors[] = get_string('validationerror_singlecontrol', 'qtype_combined', $a);
                }
            }
        }
        $errorliststring = html_writer::alist($errors);

        if (count($errors) > 1) {
            return get_string('validationerrors', 'qtype_combined', $errorliststring);
        } else {
            return get_string('validationerror', 'qtype_combined', $errorliststring);
        }
    }

}

/**
 * Class qtype_combined_param_to_pass_through_to_subq_base
 * Children of this class is used in run time combiner class calls to subqs to transform params from main question to pass to subq.
 * @see qtype_combined_combiner_for_run_time_question_instance::call_subq
 * @see qtype_combined_combiner_for_run_time_question_instance::call_all_subqs
 */
abstract class qtype_combined_param_to_pass_through_to_subq_base {
    /**
     * @param $alldata
     */
    abstract public function __construct($alldata);

    /**
     * @param $subq qtype_combined_combinable_base
     * @return mixed
     */
    abstract public function for_subq($subq);
}

/**
 * Class qtype_combined_response_array_param
 * Take main question response array and find part for each subq.
 */
class qtype_combined_response_array_param extends qtype_combined_param_to_pass_through_to_subq_base {
    /**
     * @var array
     */
    protected $responsearray;

    /**
     * @param array $responsearray
     */
    public function __construct($responsearray) {
        $this->responsearray = $responsearray;
    }

    /**
     * @param $subq qtype_combined_combinable_base
     * @return array response filtered for subq
     */
    public function for_subq($subq) {
        return $subq->get_substep(null)->filter_array($this->responsearray);
    }

}

/**
 * Class qtype_combined_array_of_response_arrays_param
 * Take an array of response arrays and return an array of response arrays for each subq.
 */
class qtype_combined_array_of_response_arrays_param extends qtype_combined_param_to_pass_through_to_subq_base {
    /**
     * @var array
     */
    protected $responsearrays;

    /**
     * @param $responsearrays
     */
    public function __construct($responsearrays) {
        $this->responsearrays = $responsearrays;
    }

    /**
     * @param $subq qtype_combined_combinable_base
     * @return array of arrays of filtered response for subq
     */
    public function for_subq($subq) {
        $filtered = array();
        foreach ($this->responsearrays as $responseno => $responsearray) {
            $filtered[$responseno] = $subq->get_substep(null)->filter_array($responsearray);
        }
        return $filtered;
    }
}

/**
 * Class qtype_combined_step_param
 */
class qtype_combined_step_param extends qtype_combined_param_to_pass_through_to_subq_base {
    /**
     * @var question_attempt_step
     */
    protected $step;

    /**
     * @param $step question_attempt_step for the main question.
     */
    public function __construct($step) {
        $this->step = $step;
    }

    /**
     * @param $subq qtype_combined_combinable_base
     * @return question_attempt_step_subquestion_adapter
     */
    public function for_subq($subq) {
        return $subq->get_substep($this->step);
    }
}

/**
 * Class qtype_combined_type_manager
 */
class qtype_combined_type_manager {

    /**
     * @var qtype_combined_combinable_type_base[] key is qtype identifier string.
     */
    protected static $combinableplugins = null;

    /**
     * The combinable class is in question/type/combined/combinable/{qtypename}/combinable.php.
     * We expect to find renderer.php for subq in the same directory.
     */
    const FOUND_IN_COMBINABLE_DIR_OF_COMBINED = 1;

    /**
     * The combinable class is in question/type/{qtypename}/combinable.php
     * Subq renderer class should be in question/type/{qtypename}/renderer.php.
     */
    const FOUND_IN_OTHER_QTYPE_DIR = 2;

    /**
     * Finds and loads all hook classes. And saves plugin names for use later.
     */
    protected static function find_and_load_all_combinable_qtype_hook_classes() {
        global $CFG;
        if (null === self::$combinableplugins) {
            self::$combinableplugins = array();
            $pluginselsewhere = get_plugin_list_with_file('qtype', 'combinable/combinable.php', true);
            foreach ($pluginselsewhere as $qtypename => $unused) {
                self::instantiate_type_class($qtypename, self::FOUND_IN_OTHER_QTYPE_DIR);
            }
            $pluginshere = get_list_of_plugins('question/type/combined/combinable');
            foreach ($pluginshere as $qtypename) {
                require_once($CFG->dirroot.'/question/type/combined/combinable/'.$qtypename.'/combinable.php');
                self::instantiate_type_class($qtypename, self::FOUND_IN_COMBINABLE_DIR_OF_COMBINED);
            }
        }
        if (count(self::$combinableplugins) === 0) {
            print_error('nosubquestiontypesinstalled', 'qtype_combined');
        }
    }

    /**
     * @param string $qtypename
     * @param integer $where FOUND_IN_COMBINABLE_DIR_OF_COMBINED or FOUND_IN_OTHER_QTYPE_DIR
     */
    protected static function instantiate_type_class($qtypename, $where) {
        $classname = 'qtype_combined_combinable_type_'.$qtypename;
        $typeobj = new $classname($qtypename, $where);
        self::$combinableplugins[$typeobj->get_identifier()] = $typeobj;
    }

    /**
     * @param $typeidentifier the identifier as found in the question text.
     * @return bool
     */
    public static function is_identifier_known($typeidentifier) {
        self::find_and_load_all_combinable_qtype_hook_classes();
        return isset(self::$combinableplugins[$typeidentifier]);
    }

    /**
     * @param $typeidentifier string
     * @param $questionidentifier string
     * @return qtype_combined_combinable_base
     */
    public static function new_subq_instance($typeidentifier, $questionidentifier) {
        self::find_and_load_all_combinable_qtype_hook_classes();
        $type = self::$combinableplugins[$typeidentifier];
        return $type->new_subq_instance($questionidentifier);
    }

    /**
     * @param $qtypename string the qtype name as used within Moodle
     * @return null|string null or identifier used as second param in question text embedded code.
     */
    public static function translate_qtype_to_qtype_identifier($qtypename) {
        self::find_and_load_all_combinable_qtype_hook_classes();
        foreach (self::$combinableplugins as $type) {
            if ($type->get_qtype_name() === $qtypename) {
                return $type->get_identifier();
            }
        }
        if (!isset(self::$combinableplugins[$qtypename])) {
            print_error('subquestiontypenotinstalled', 'qtype_combined', '', $qtypename);
        }

    }

    /**
     * @return string the default question text when you first open the form. Also used to determine what subq form fragments
     * should be shown when you first start to create a question.
     */
    public static function default_question_text() {
        $i = 1;
        $codes = array();
        self::find_and_load_all_combinable_qtype_hook_classes();
        $identifiers = array_keys(self::$combinableplugins);
        sort($identifiers);
        foreach ($identifiers as $identifier) {
            $type = self::$combinableplugins[$identifier];
            $codes[] = $type->embedded_code_for_default_question_text($i);
            $i++;
        }
        return join("\n\n", $codes);
    }

}

