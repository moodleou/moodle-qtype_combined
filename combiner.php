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


    const EMBEDDED_CODE_PREFIX = '[[';
    const EMBEDDED_CODE_POSTFIX = ']]';
    const EMBEDDED_CODE_SEPARATOR = ':';

    const FIELD_NAME_PREFIX = 'subq:{qtype}:{qid}:';

    /**
     * Question identifier must be one or more alphanumeric characters
     */
    const VALID_QUESTION_IDENTIFIER_PATTTERN = '[a-zA-Z0-9]+';
    /**
     * Question identifier must be one or more alphanumeric characters
     */
    const VALID_QUESTION_TYPE_IDENTIFIER_PATTTERN = '[a-zA-Z0-9_-]+';

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

        foreach ($matches[1] as $codeinsideprepostfix) {
            $error = $this->make_combinable_instance_from_code_in_question_text($codeinsideprepostfix);
            if ($error !== null) {
                return $error;
            }
        }
        return null;
    }

    /**
     * Create or just pass through the third embedded code param to each subq from question text.
     * @param $codeinsideprepostfix string The embedded code minus the enclosing brackets.
     * @return string|null first error encountered or null if no error.
     */
    protected function make_combinable_instance_from_code_in_question_text($codeinsideprepostfix) {
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
        if (!qtype_combined_type_manager::is_identifier_known($qtypeidentifier)) {
            return get_string('err_unrecognisedqtype', 'qtype_combined', $getstringhash);
        }

        $subq = $this->get_question_instance($qtypeidentifier, $questionidentifier);

        $error = $subq->found_in_question_text($thirdparam);
        if (null !== $error) {
            return $error;
        }
        return null; // Done, no error.
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
    protected function get_question_instance($qtypeidentifier, $questionidentifier) {
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
        foreach ($questiondata as $key => $unused) {
            $qidpart = preg_quote('{qid}', '!');
            $qtypepart = preg_quote('{qtype}', '!');
            $pregquotedprefixpattern = preg_quote(self::FIELD_NAME_PREFIX, '!');
            $patternforprefix = str_replace($qidpart, '(?P<qid>'.self::VALID_QUESTION_IDENTIFIER_PATTTERN.')',
                                            $pregquotedprefixpattern);
            $patternforprefix = str_replace($qtypepart, '(?P<qtype>'.self::VALID_QUESTION_TYPE_IDENTIFIER_PATTTERN.')',
                                            $patternforprefix);
            $matches = array();
            if (preg_match("!{$patternforprefix}qtypeid$!A", $key, $matches)) {
                $subq = $this->get_question_instance($matches['qtype'], $matches['qid']);
                $subq->get_this_form_data_from($questiondata);
            }
        }
    }

    /**
     * @param      $questionid The question id
     * @param bool $getoptions
     */
    public function load_subq_data_from_db($questionid, $getoptions = false) {
        $subquestionsdata = static::get_subq_data_from_db($questionid, $getoptions);
        $this->create_subqs_from_subq_data($subquestionsdata);
    }

    /**
     * The db operation to fetch all sub-question data from the db. For run time question instances this is run before
     * question instance data caching as it seems more straight forward to have Moodle MUC cache stdClass rather than other
     * classes.
     * @param      $questionid The question id
     * @param bool $getoptions Whether to also fetch the question options for each subq.
     * @return stdClass[]
     */
    public static function get_subq_data_from_db($questionid, $getoptions = false) {
        global $DB;
        $sql = 'SELECT q.*, qc.contextid FROM {question} q '.
            'JOIN {question_categories} qc ON q.category = qc.id ' .
            'WHERE q.parent = $1';

        // Load the questions
        if (!$subqrecs = $DB->get_records_sql($sql, array($questionid))) {
            return array();
        }
        if ($getoptions) {
            get_question_options($subqrecs);
        }
        return $subqrecs;
    }

    public function create_subqs_from_subq_data($subquestionsdata) {
        foreach ($subquestionsdata as $subquestiondata) {
            $qtypeid = qtype_combined_type_manager::translate_qtype_to_qtype_identifier($subquestiondata->qtype);
            $subq = $this->get_question_instance($qtypeid, $subquestiondata->name);
            $subq->found_in_db($subquestiondata);
        }
    }
}

class qtype_combined_combiner_for_form extends qtype_combined_combiner_base {

    /**
     * @return string the default question text when you first open the form. Also used to determine what subq form fragments
     * should be shown when you first start to create a question.
     */
    public function default_question_text() {
        return "[[1:numeric:__10__]]\n\n".
            "[[2:pmatch]]\n\n".
            "[[3:multiresponse:v]]\n\n".
            "[[4:selectmenu:1]]\n";
    }

    /**
     * Construct the part of the form for the user to fill in the details for each subq.
     * This method must determine which subqs should appear in the form based on the user submitted question text and also what
     * items have previously been in the form. We don't want to lose any data submitted without a warning
     * when the user removes a subq from the question text.
     * @param                 $questiontext
     * @param moodleform      $combinedform
     * @param MoodleQuickForm $mform
     * @param                 $repeatenabled
     */
    public function form_for_subqs($questiontext, moodleform $combinedform, MoodleQuickForm $mform, $repeatenabled) {
        $this->find_included_subqs_in_question_text($questiontext);

        foreach ($this->subqs as $subq) {
            $weightingdefault = round(1/count($this->subqs), 7);
            $weightingdefault = "$weightingdefault";

            $a = new stdClass();
            $a->qtype = $subq->type->get_identifier();
            $a->qid = $subq->get_identifier();

            if ($subq->is_in_question_text()) {
                $headerlegend = get_string('subqheader', 'qtype_combined', $a);
            } else {
                $headerlegend = get_string('subqheader_not_in_question_text', 'qtype_combined', $a);
                $headerlegend ='<span class="not_in_question_text">'.$headerlegend.'</span>';
            }

            $mform->addElement('header', $subq->field_name('subqheader'), $headerlegend);

            if (!$subq->is_in_question_text()) {
                $message = $subq->message_in_form_if_not_included_in_question_text();
                $message = '<div class="not_in_question_text">'.$message.'</div>';
                $mform->addElement('static', 'notincludedinquestiontext', '', $message);
            }

            $gradeoptions = question_bank::fraction_options();
            $mform->addElement('select', $subq->field_name('defaultmark'), get_string('weighting', 'qtype_combined'),
                               $gradeoptions);
            $mform->setDefault($subq->field_name('defaultmark'), $weightingdefault);
            $subq->add_form_fragment($combinedform, $mform, $repeatenabled);
            $mform->addElement('editor', $subq->field_name('generalfeedback'), get_string('incorrectfeedback', 'qtype_combined'),
                               array('rows' => 5), $combinedform->editoroptions);
            $mform->setType($subq->field_name('generalfeedback'), PARAM_RAW);
            $mform->addElement('hidden', $subq->field_name('qtypeid'), $subq->type->get_identifier());
            $mform->setType($subq->field_name('qtypeid'), PARAM_ALPHANUMEXT);

        }
    }

    /**
     * @param $fromform array data from form
     * @param $files array not used for now no subq type requires this.
     * @return array of errors to display in form or empty array if no errors.
     */
    public function validate_subqs_data_in_form($fromform, $files) {
        $errors = $this->validate_question_text($fromform['questiontext']['text']);
        $errors += $this->validate_subqs($fromform);
        return $errors;
    }


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
     * @param $fromform array all data from form
     * @return array of errors from subq form elements or empty array if no errors.
     */
    protected function validate_subqs($fromform) {
        $this->get_subq_data_from_form_data((object)$fromform);
        $errors = array();
        $fractionsum = 0;

        foreach ($this->subqs as $subq) {
            $errors += $subq->validate();

            $defaultmarkfieldname = $subq->field_name('defaultmark');
            $fractionsum += $fromform[$defaultmarkfieldname];
        }
        if (abs($fractionsum - 1) > 0.00001) {
            foreach ($this->subqs as $subq) {
                $errors += array($subq->field_name('defaultmark') => get_string('err_weightingsdonotaddup', 'qtype_combined'));
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
                $fieldname = $subq->field_name($property);
                $toform->{$fieldname} = $value;
            }
        }
        return $toform;
    }
}
class qtype_combined_combiner_for_saving_subqs extends qtype_combined_combiner_base {

    /**
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

class qtype_combined_combiner_for_run_time_question_instance extends qtype_combined_combiner_base {

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
     * @param $substep question_attempt_step_subquestion_adapter|null
     * @param $response array
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
     * @param qtype_combined_param_to_pass_through_to_subq_base|mixed  $params,....
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

    public function compute_final_grade($responses, $totaltries) {
        $allresponses = new qtype_combined_array_of_response_arrays_param($responses);
        foreach ($this->subqs as $subqno => $subq) {
            $subqresponses = $allresponses->for_subq($subq);
            if (is_a($subq, 'question_graded_automatically_with_countback')) {
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

}

abstract class qtype_combined_param_to_pass_through_to_subq_base {
    abstract public function __construct($alldata);

    abstract public function for_subq($subq);
}

class qtype_combined_response_array_param extends qtype_combined_param_to_pass_through_to_subq_base {
    /**
     * @var array
     */
    protected $responsearray;

    public function __construct($responsearray) {
        $this->responsearray = $responsearray;
    }

    public function for_subq($subq) {
        return $subq->get_substep(null)->filter_array($this->responsearray);
    }

}
class qtype_combined_array_of_response_arrays_param extends qtype_combined_param_to_pass_through_to_subq_base {
    /**
     * @var array
     */
    protected $responsearrays;

    public function __construct($responsearrays) {
        $this->responsearrays = $responsearrays;
    }

    /**
     * @param $subq qtype_combined_combinable_base
     * @return array of filtered response for subq
     */
    public function for_subq($subq) {
        $filtered = array();
        foreach ($this->responsearrays as $responseno => $responsearray) {
            $filtered[$responseno] = $subq->get_substep(null)->filter_array($responsearray);
        }
        return $filtered;
    }
}

class qtype_combined_step_param extends qtype_combined_param_to_pass_through_to_subq_base {
    /**
     * @var array
     */
    protected $step;

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

class qtype_combined_type_manager {

    /**
     * @var array containing qtype indentifier string => qtype_combined_combinable_type_base child classes.
     */
    protected static $combinableplugins = null;

    const FOUND_IN_COMBINABLE_DIR_OF_COMBINED = 1;

    const FOUND_IN_OTHER_QTYPE_DIR = 2;

    /**
     * Finds and loads all hook classes. And saves plugin names for use later.
     */
    protected static function find_and_load_all_combinable_qtype_hook_classes() {
        global $CFG;
        if (null === self::$combinableplugins) {
            self::$combinableplugins = array();
            $pluginselsewhere = get_plugin_list_with_file('qtype', 'combinable.php', true);
            foreach ($pluginselsewhere as $qtypename) {
                self::instantiate_type_class($qtypename, self::FOUND_IN_OTHER_QTYPE_DIR);
            }
            $pluginshere = get_list_of_plugins('question/type/combined/combinable');
            foreach ($pluginshere as $qtypename) {
                include_once($CFG->dirroot.'/question/type/combined/combinable/'.$qtypename.'/combinable.php');
                self::instantiate_type_class($qtypename, self::FOUND_IN_COMBINABLE_DIR_OF_COMBINED);
            }
        }
    }

    protected static function instantiate_type_class($qtypename, $where) {
        $classname = 'qtype_combined_combinable_type_'.$qtypename;
        $typeobj = new $classname($qtypename, $where);
        self::$combinableplugins[$typeobj->get_identifier()] = $typeobj;
    }

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
        return null;
    }

}

